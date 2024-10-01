
Add your own `lmutil` binary to `httpd/` directory before running.

Adapt `config.php` and set the passwords in `docker-compose.yml`.
Also change the hostname *yourhost.com* in the `php.ini` configuration
file. Otherwise e-mails most likely won't work.

# Docker Compose
Run this command from the `docker` directory.

* `docker-compose up --build --force-recreate --no-deps -d`

# Setup MariaDB DB
Run these commands from the `database` directory.

1. `docker cp phplicensewatcher.maria.sql docker-mariadb-1:/app`
2. `docker exec -ti docker-mariadb-1 bash`
3. `mysql -f licenses -p < app`
