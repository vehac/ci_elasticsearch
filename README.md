# CodeIgniter
Docker - CodeIgniter 3.1.13 (PHP 7.4) - MariaDB - Elasticsearch 7.17.3

## Inicio
- En la ruta `docker/php` se encuentra el archivo `init.sh` donde se asigna permisos a la carpeta `cache`
- Se agrega el archivo `.htaccess` donde se coloca regla para omitir el `index.php` de las url's
- Se modifica el archivo `application/config/config.php` para permitir tener una url base en `['base_url']`, omitir el `index.php` en las url's en `['index_page']` y permitir cargar la carpeta `vendor` en `['composer_autoload']`
- Se modifica el archivo `application/config/config.php` para guardar las sesiones, esto en la variable `['sess_save_path']`; 
- Se modifica el archivo `application/config/autoload.php` para agregar el helper `url` en `['helper']`
- Se modifica el archivo `application/config/autoload.php` para agregar las librerías `session` y `form_validation` en `['libraries']`
- Se modifica el archivo `application/config/constants.php` para agregar el host de elasticsearch en la variable `HOST_ELASTICSEARCH`
```bash
defined('HOST_ELASTICSEARCH')      OR define('HOST_ELASTICSEARCH', 'http://13.24.22.20:9200');
```
-  Se modifica el archivo `application/config/database.php` donde se agrega las credenciales para conectarse a la BD

- Antes de iniciar docker ejecutar desde consola el siguiente comando para que el contenedor de elasticsearch pueda iniciar ([referencia](https://www.elastic.co/guide/en/elasticsearch/reference/7.17/docker.html#docker-prod-prerequisites)):
```bash
sudo sysctl -w vm.max_map_count=262144 
```

## Docker
- Para la primera vez que se inicia el proyecto con docker o se cambie los archivos de docker ejecutar:
```bash
sudo docker-compose up --build -d
```
- En las siguientes oportunidades ejecutar:

Para iniciar:
```bash
sudo docker-compose start
```
Para detener:
```bash
sudo docker-compose stop
```
- Para ingresar al contenedor ejecutar:
```bash
sudo docker-compose exec webserver bash
```
- Instalar las dependencias con composer, para ello, dentro del contenedor con php ejecutar:
```bash
composer install
```
- Para ver el proyecto desde un navegador:

Sin virtualhost:
```bash
http://localhost:8483
```
Con virtualhost:

Si se usa Linux, agregar en /etc/hosts de la pc host la siguiente linea:
```bash
13.24.22.19    local.elasticsearch.com
```
## MariaDB
- Luego de iniciar docker, loguearse en contenedor con mariadb y luego cargar la data del archivo `docker/my_db.sql` en la BD `my_db` con `SOURCE <ruta_de_my_db.sql>`
```bash
mysql -u root -p -h 13.24.22.18
3*DB6ci9
use my_db;
SOURCE /var/www/html/ci_elasticsearch/docker/my_db.sql
```
- Luego de iniciar el contenedor con php (webserver) y luego de cargar la data del archivo `docker/my_db.sql` en la BD `my_db`, ingresar al contenedor de php y dentro ejecutar los siguientes comandos para crear el índice `es_articles` y agregarle datos a este índice:
```bash
php index.php cron/es_articles create_index_es_articles
```
```bash
php index.php cron/es_articles add_documents_index_es_articles
```
## Urls
```bash
http://localhost:8483
http://localhost:8483/home/create
http://localhost:8483/home/search
http://localhost:8483/home/edit/{id}
http://localhost:8483/home/delete/{id}
```