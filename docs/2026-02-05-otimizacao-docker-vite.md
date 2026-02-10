# Otimização de Performance Docker e Configuração do Vite

**Data:** 2026-02-05

## Problema

A primeira requisição ao projeto levava **2.2 minutos** para renderizar no browser após `docker compose up`.

## Causas Identificadas

1. **MySQL sem health check** — O container do app iniciava antes do MySQL estar pronto para aceitar conexões. O `depends_on` sem health check só garante que o container existe, não que o serviço está disponível.

2. **Session e Cache usando banco de dados** — `SESSION_DRIVER=database` e `CACHE_STORE=database` faziam toda requisição depender do MySQL, inclusive na inicialização.

3. **Sem OPcache** — O PHP recompilava todos os arquivos do framework a cada request (centenas de arquivos no vendor).

4. **Sem caches do Laravel** — Config, rotas e views Blade eram reprocessados a cada requisição.

5. **Vite não configurado para Docker** — O dev server não estava acessível de fora do container.

## Alterações Realizadas

### docker-compose.yml

- Adicionado `healthcheck` no serviço MySQL com `mysqladmin ping`
- `depends_on` do app usa `condition: service_healthy`
- Criado serviço **vite** dedicado (container separado) com porta 5173 exposta
- O serviço vite faz `npm install` automaticamente se `node_modules` não existir

### docker/php/Dockerfile

- Adicionado `opcache` na lista de extensões PHP
- Adicionado `COPY opcache.ini` para configuração do OPcache
- Adicionado `COPY entrypoint.sh` com script de inicialização
- Definido `CMD ["entrypoint.sh"]`

### docker/php/opcache.ini (novo)

Configuração otimizada do OPcache:
- 128MB de memória
- 10.000 arquivos acelerados
- Validação de timestamps ativa (necessário em dev)

### docker/php/entrypoint.sh (novo)

Script de inicialização que executa automaticamente:
- `npm install` (se `node_modules` não existir)
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- Inicia PHP-FPM

### .env

- `SESSION_DRIVER`: database → **file**
- `CACHE_STORE`: database → **file**

### vite.config.js

- `host: '0.0.0.0'` para escutar em todas as interfaces dentro do Docker
- `port: 5173` explícito
- `hmr.host: 'localhost'` para Hot Module Replacement funcionar no browser do host

## Arquitetura Final dos Containers

```
docker compose up -d
├── efomento-mysql   (porta 3306) — MySQL 8.0 com health check
├── efomento-app     (porta 9000) — PHP-FPM + OPcache + caches Laravel
├── efomento-vite    (porta 5173) — Vite dev server com HMR
└── efomento-nginx   (porta 8080) — Proxy reverso
```

## Resultado

| Métrica         | Antes    | Depois  |
|-----------------|----------|---------|
| Primeiro request | 2.2 min | ~0.15s  |
| Requests seguintes | ~1-2s | ~0.02s |
