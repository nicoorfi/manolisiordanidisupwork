@setup
    $repository = 'git@github.com:nicoorfi/manolisiordanidisupwork.git';
    $appDirectory = '/var/www/manolisiordanidisupwork';
    $releaseDirectory = $appDirectory . '/releases';
    $sharedDirectory = $appDirectory . '/shared';
    $currentDirectory = $appDirectory . '/current';
    $newReleaseName = date('Y-m-d_H-i-s');
    $newReleasePath = $releaseDirectory . '/' . $newReleaseName;
    $user = 'nico';
    $phpBinary = '/usr/bin/php';
    $composerBinary = '/usr/local/bin/composer';
    $npmBinary = '/usr/bin/npm';
    $keepReleases = 3;
@endsetup

@servers(['production' => 'nico@35.242.239.121', 'local' => '127.0.0.1'])

@task('enable_maintenance_mode', ['on' => 'production'])
    echo "Enabling maintenance mode..."
    if [ -d {{ $currentDirectory }} ] && [ -f {{ $currentDirectory }}/artisan ]; then
        cd {{ $currentDirectory }}
        {{ $phpBinary }} artisan down --render="errors::503" --retry=60
    else
        echo "No current release found - skipping maintenance mode (first deployment)"
    fi
@endtask

@task('disable_maintenance_mode', ['on' => 'production'])
    echo "Disabling maintenance mode..."
    if [ -d {{ $currentDirectory }} ] && [ -f {{ $currentDirectory }}/artisan ]; then
        cd {{ $currentDirectory }}
        {{ $phpBinary }} artisan up
        echo "✓ Application is now live!"
    else
        echo "⚠️  Current directory not found - skipping maintenance mode disable"
    fi
@endtask

@story('deploy')
    {{-- run_tests --}}
    push_to_repository
    enable_maintenance_mode
    clone_repository
    run_composer
    update_symlinks
    run_migrations
    optimize_laravel
    cleanup_old_releases
    reload_php_fpm
    reload_octane
    disable_maintenance_mode
@endstory

@story('test')
    run_tests
@endstory

@task('run_tests', ['on' => 'local'])
    echo "🧪 Running test suite locally before deployment..."
    php artisan test
    
    if [ $? -ne 0 ]; then
        echo ""
        echo "❌ Tests failed! Deployment aborted."
        echo "   Please fix the failing tests before deploying."
        exit 1
    fi
    
    echo ""
    echo "✅ All tests passed! Proceeding with deployment..."
    echo ""
@endtask

@task('push_to_repository', ['on' => 'local'])
    echo "📤 Pushing changes to repository..."
    
    # Check if there are any uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        echo ""
        echo "❌ You have uncommitted changes! Please commit them before deploying."
        git status --short
        exit 1
    fi
    
    # Check if local branch is ahead of remote
    LOCAL=$(git rev-parse @)
    REMOTE=$(git rev-parse @{u} 2>/dev/null)
    
    if [ "$LOCAL" != "$REMOTE" ]; then
        echo "   Pushing to remote repository..."
        git push
        
        if [ $? -ne 0 ]; then
            echo ""
            echo "❌ Failed to push to repository! Deployment aborted."
            exit 1
        fi
        
        echo "   ✅ Successfully pushed to repository!"
    else
        echo "   ℹ️  Repository is already up to date."
    fi
    
    echo ""
@endtask

@task('setup_ssh', ['on' => 'production'])
    echo "Setting up SSH for GitHub access..."
    
    # Create .ssh directory if it doesn't exist
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    
    # Add GitHub to known_hosts if not already present
    if ! grep -q "github.com" ~/.ssh/known_hosts 2>/dev/null; then
        echo "Adding GitHub to known_hosts..."
        ssh-keyscan -t ed25519 github.com >> ~/.ssh/known_hosts 2>/dev/null
        chmod 600 ~/.ssh/known_hosts
        echo "✓ GitHub added to known_hosts"
    else
        echo "ℹ️  GitHub already in known_hosts"
    fi
    
    # Configure SSH config to use deploy key for GitHub if it exists
    if [ -f ~/.ssh/deploy_key ]; then
        echo "Configuring SSH to use deploy key for GitHub..."
        
        # Create or update SSH config
        if [ ! -f ~/.ssh/config ]; then
            touch ~/.ssh/config
            chmod 600 ~/.ssh/config
        fi
        
        # Check if GitHub config already exists
        if ! grep -q "Host github.com" ~/.ssh/config 2>/dev/null; then
            echo "" >> ~/.ssh/config
            echo "Host github.com" >> ~/.ssh/config
            echo "    HostName github.com" >> ~/.ssh/config
            echo "    User git" >> ~/.ssh/config
            echo "    IdentityFile ~/.ssh/deploy_key" >> ~/.ssh/config
            echo "    StrictHostKeyChecking accept-new" >> ~/.ssh/config
            echo "✓ SSH config updated to use deploy key"
        else
            echo "ℹ️  GitHub config already exists in SSH config"
        fi
    else
        echo "⚠️  Deploy key not found at ~/.ssh/deploy_key"
        echo "   Generate it with: ssh-keygen -t ed25519 -C 'deploy@manolisiordanidisupwork' -f ~/.ssh/deploy_key"
    fi
    
    echo "✓ SSH setup complete"
@endtask

@task('clone_repository', ['on' => 'production'])
    echo "Cloning repository..."
    echo "Creating directory structure..."
    [ -d {{ $appDirectory }} ] || mkdir -p {{ $appDirectory }}
    [ -d {{ $releaseDirectory }} ] || mkdir -p {{ $releaseDirectory }}
    [ -d {{ $sharedDirectory }} ] || mkdir -p {{ $sharedDirectory }}

    echo "Cloning repository to {{ $newReleasePath }}..."
    
    # Git will automatically use SSH config if configured
    # The setup_ssh task configures ~/.ssh/config to use deploy_key
    git clone --depth 1 {{ $repository }} {{ $newReleasePath }}

    cd {{ $newReleasePath }}
    git reset --hard HEAD
    echo "✓ Repository cloned successfully"
@endtask

@task('run_composer', ['on' => 'production'])
    echo "Installing composer dependencies..."
    cd {{ $newReleasePath }}
    {{ $composerBinary }} install --no-dev --no-interaction --prefer-dist --optimize-autoloader
@endtask

@task('update_symlinks', ['on' => 'production'])
    echo "Linking storage and .env file..."

    # Ensure shared directory exists
    [ -d {{ $sharedDirectory }} ] || mkdir -p {{ $sharedDirectory }}

    # Remove the storage directory and link to shared storage
    rm -rf {{ $newReleasePath }}/storage
    ln -nfs {{ $sharedDirectory }}/storage {{ $newReleasePath }}/storage

    # Create storage directories if they don't exist
    mkdir -p {{ $sharedDirectory }}/storage/app/public
    mkdir -p {{ $sharedDirectory }}/storage/framework/cache
    mkdir -p {{ $sharedDirectory }}/storage/framework/sessions
    mkdir -p {{ $sharedDirectory }}/storage/framework/views
    mkdir -p {{ $sharedDirectory }}/storage/logs

    # Create .env file if it doesn't exist (copy from example)
    if [ ! -f {{ $sharedDirectory }}/.env ]; then
        echo "Creating .env file from .env.example..."
        if [ -f {{ $newReleasePath }}/.env.example ]; then
            cp {{ $newReleasePath }}/.env.example {{ $sharedDirectory }}/.env
        else
            touch {{ $sharedDirectory }}/.env
        fi
    fi

    # Link .env file
    ln -nfs {{ $sharedDirectory }}/.env {{ $newReleasePath }}/.env

    # Update current symlink
    ln -nfs {{ $newReleasePath }} {{ $currentDirectory }}

    # Generate APP_KEY if missing
    cd {{ $newReleasePath }}
    if [ -f {{ $sharedDirectory }}/.env ] && ! grep -q "APP_KEY=base64:" {{ $sharedDirectory }}/.env 2>/dev/null; then
        echo "Generating application key..."
        {{ $phpBinary }} artisan key:generate --force
    fi

    echo "✓ Symlinks created successfully"

    {{-- # Set permissions
    chgrp -R www-data {{ $newReleasePath }}
    chmod -R 775 {{ $newReleasePath }}/bootstrap/cache
    chmod -R 775 {{ $sharedDirectory }}/storage --}}
@endtask


@task('run_migrations', ['on' => 'production'])
    echo "Running migrations..."
    cd {{ $newReleasePath }}
    if [ -f artisan ]; then
        {{ $phpBinary }} artisan migrate --force
        echo "✓ Migrations completed"
    else
        echo "⚠️  Artisan file not found - skipping migrations"
    fi
@endtask

@task('optimize_laravel', ['on' => 'production'])
    echo "Optimizing Laravel..."
    cd {{ $newReleasePath }}
    if [ -f artisan ]; then
        {{ $phpBinary }} artisan config:cache
        {{ $phpBinary }} artisan route:cache
        {{ $phpBinary }} artisan view:cache
        {{ $phpBinary }} artisan optimize
        echo "✓ Laravel optimized"
    else
        echo "⚠️  Artisan file not found - skipping optimization"
    fi
@endtask

@task('cleanup_old_releases', ['on' => 'production'])
    echo "Cleaning up old releases..."
    if [ -d {{ $releaseDirectory }} ]; then
        cd {{ $releaseDirectory }}
        RELEASE_COUNT=$(ls -dt {{ $releaseDirectory }}/* 2>/dev/null | wc -l)
        if [ "$RELEASE_COUNT" -gt {{ $keepReleases }} ]; then
            ls -dt {{ $releaseDirectory }}/* | tail -n +{{ $keepReleases + 1 }} | xargs -d "\n" rm -rf
            echo "✓ Cleaned up old releases"
        else
            echo "ℹ️  No old releases to clean up (keeping $RELEASE_COUNT releases)"
        fi
    else
        echo "ℹ️  Release directory doesn't exist yet - skipping cleanup"
    fi
@endtask

@task('reload_php_fpm', ['on' => 'production'])
    echo "Reloading PHP-FPM..."
    sudo systemctl reload php8.3-fpm || sudo systemctl reload php8.2-fpm || echo "PHP-FPM reload skipped - service may not be configured"
@endtask

@task('reload_octane', ['on' => 'production'])
    echo "Reloading Laravel Octane..."
    if [ -d {{ $currentDirectory }} ] && [ -f {{ $currentDirectory }}/artisan ]; then
        cd {{ $currentDirectory }}
        
        # Try graceful reload first (best for zero-downtime)
        if {{ $phpBinary }} artisan octane:reload 2>/dev/null; then
            echo "✓ Octane reloaded gracefully"
        else
            # Fallback: restart via supervisor if configured
            if sudo systemctl is-active --quiet octane 2>/dev/null; then
                echo "Restarting Octane via systemd..."
                sudo systemctl restart octane
                echo "✓ Octane restarted via systemd"
            elif sudo supervisorctl status octane 2>/dev/null | grep -q RUNNING; then
                echo "Restarting Octane via supervisor..."
                sudo supervisorctl restart octane
                echo "✓ Octane restarted via supervisor"
            else
                echo "⚠️  Octane not found running - may need manual start"
                echo "   Start with: cd {{ $currentDirectory }} && {{ $phpBinary }} artisan octane:start"
            fi
        fi
    else
        echo "⚠️  Current directory not found - skipping Octane reload"
    fi
@endtask
