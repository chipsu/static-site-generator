FROM composer:2.5 AS build
WORKDIR /build
ADD ./composer.* ./
RUN composer install
ADD ./*.php ./
ADD templates/ ./templates

FROM php:8.2-cli
WORKDIR /app
COPY --from=build /build .
CMD ["php", "build.php"]
