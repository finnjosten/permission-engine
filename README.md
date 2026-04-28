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
