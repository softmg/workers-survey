# Workers Backend Stack

## Core runtime

- PHP `8.*`
- Laravel `10`
- Composer for dependency management

## Key backend packages

- `encore/laravel-admin` — admin panel layer in the source Workers backend
- `php-open-source-saver/jwt-auth` — JWT-based API authentication
- `maatwebsite/excel` — spreadsheet export/import workflows
- `denis660/laravel-centrifugo` and `pusher/pusher-php-server` — realtime event delivery integration
- `barryvdh/laravel-dompdf` — PDF generation

## Infrastructure (Docker Compose)

- MySQL for main application data
- Dedicated MySQL test database container
- Redis for cache/queue-related workloads
- PHP runtime container and Supervisor worker container
- Nginx as web entrypoint
- Selenium standalone Chrome for browser tests
- Mailpit for local SMTP capture and email preview
- Centrifugo for realtime transport

## Frontend/build toolchain

- Laravel Mix `6` (`laravel-mix`)
- Sass compilation (`sass`)
- NPM scripts for assets: `dev`, `watch`, `production`

## Local developer workflow

Makefile targets orchestrate Docker and Laravel operations for daily development:

- Environment bootstrap: `init`, `up`, `build`
- Database lifecycle: `migrate`, `db_seed`, `wipe`, `refresh`
- Quality and tests: `pint`, `prepare_tests`, `tests`
- Maintenance: `optimize`, `cache-clear`
