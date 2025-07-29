# Possible Docker Configuration

This is a possible Docker configuration for the application but with NginX and MariaDB.

## Directory Structure

```
.
├── docker
│   ├── php
│   │   └── Dockerfile
│   └── nginx
│       └── default.conf
├── app
│   ├── bin
│   ├── config
│   ├── public
│   ├── src
│   ├── tests
│   ├── var
│   ├── vendor
│   ├── composer.json
├── docker-compose.yml
```

## Dockerfile

```dockerfile
FROM php:8.4-fpm

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /app

EXPOSE 9000
CMD ["php-fpm"]
```

## docker-compose.yml

```yaml
services:
  app:
    build:
        context: ./docker/php
        dockerfile: Dockerfile
    container_name: tmdb_app
    volumes:
      - .:/app
      - /app/vendor
    working_dir: /app
    environment:
      - APP_ENV=dev
    depends_on:
      - database
    networks:
      - tmdb_network

  web:
    image: nginx:alpine
    container_name: tmdb_web
    ports:
      - "8080:80"
    volumes:
      - .:/app
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - tmdb_network

  database:
    image: mariadb:11.0
    container_name: tmdb_db
    environment:
      MARIADB_ROOT_PASSWORD: root
      MARIADB_DATABASE: tmdb
      MARIADB_USER: tmdb_user
      MARIADB_PASSWORD: tmdb_pass
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - tmdb_network

volumes:
  db_data:

networks:
  tmdb_network:
    driver: bridge
```
