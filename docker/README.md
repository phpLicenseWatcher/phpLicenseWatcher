
Add your own `lmutil` binary to `httpd/` folder before running.

Also adapt `config.php` and set the passwords in `docker-compose.yml`.

# docker compose

docker-compose up --build --force-recreate --no-deps -d


# Setup MySQL DB

docker cp phplicensewatcher.maria.sql docker_mariadb_1:/app
docker exec -ti docker_mariadb_1 bash
mysql -f licenses -p < app

