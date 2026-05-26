#! /bin/bash

## Para todos os containers e remover os volumes
docker compose down -v

## Subir todos os containers
docker compose up -d

## Rodar migrations e seeders
docker compose exec app php artisan migrate:fresh --seed
