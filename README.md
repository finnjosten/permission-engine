# Setup Composer
To use it add

```JSON
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/finnjosten/permission-engine"
        }
    ],
```

to your composer.json, then add it to the required section like so
```JSON
"finnjosten/permission-engine": "dev-main",
```
or with
```BASH
composer require finnjosten/permission-engine:dev-main
```

# Setup Laravel / System
Make sure you also have some kind of service that proccess your laravel queue, for example systemctl.  
A simple example of a systemctl setup would look like this:
```
[Unit]
Description={APP NAME} Queue Worker
After=network.target

[Service]
# Same user as your Supervisor config
User={APP USER}
Group={APP USER GROUP}

# Restart policy (same effect as Supervisor’s autostart/autorestart)
Restart=always
RestartSec=3

# Ensure the working directory is the project root
WorkingDirectory={APP ROOT}

# Match your Supervisor command
ExecStart=/usr/bin/php {APP ROOT}/artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600

# Log output (Supervisor wrote to queue-worker.log)
StandardOutput=append:{APP ROOT}/storage/logs/queue-worker.log
StandardError=append:{APP ROOT}/queue-worker-error.log

# Optional: handle graceful stops (Laravel listens for SIGTERM)
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target

```

# Commands
Roles can be looked up by name (case-insensitive) or id. Unknown permission names are rejected and nothing is changed, run `permission:refresh` first to discover new ones. The root wildcards `*` and `!*` are always accepted.  
Every command asks for missing arguments interactively, and also works with `--no-interaction` when all arguments are given.

| Command | Description |
|---|---|
| `permission:refresh` | Scan `app/` and `routes/` for `permission()` / `->can()` calls and sync them to the database |
| `role:create {name?} {--permission=*}` | Create a role, optionally with permissions |
| `role:permissions {role?} {--add=*} {--remove=*} {--sync}` | Add or remove permissions on a role, without flags it opens an interactive loop |
| `role:list` | List all roles with their user count and permissions |
| `user:role {user?} {role?}` | Assign a role to a user (by email or id), use `none` to remove it |

```BASH
php artisan permission:refresh
php artisan role:create Editor --permission="resources.*" --permission="!resources.delete"
php artisan role:permissions Editor --add="posts.*" --remove="!resources.delete"
php artisan role:permissions Editor --sync --add="posts.*"   # replace all permissions
php artisan role:list
php artisan user:role john@example.com Editor
php artisan user:role john@example.com none
```
