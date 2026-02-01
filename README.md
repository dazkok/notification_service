# Notification Service (Symfony + Docker)

A notification microservice that accepts requests via API, queues them, applies throttling, logs delivery attempts, and
sends messages through multiple providers with failover support.

## Running the Project

Build and start containers:

```
docker compose build --pull --no-cache
docker compose up -d
```

Docker automatically starts:

- PHP API container
- Messenger worker (queue consumer)
- Redis (queue)
- Database
- Mailer (SES relay)

Example startup output:

```
docker compose up -d
✔ Network notification_service_default Created
✔ Container notification_service-mailer-1 Started
✔ Container notification_service-redis-1 Started
✔ Container notification_service-database-1 Started
✔ Container notification_service-php-1 Started
✔ Container notification_service-messenger-worker-1 Started
```

## Environment Configuration (AWS SES)

Email sending is implemented using AWS SES.

Add to .env.local (see .env example):

```
AWS_FROM_EMAIL=your-email@gmail.com
AWS_EMAIL_ACCESS_KEY=EXAMPLE
AWS_EMAIL_SECRET_KEY=secret-key
AWS_REGION=eu-north-1
```

## Channel Configuration

You can enable or disable communication channels and manage provider priority in config/services.yaml. Providers are
injected using tagged iterators.

```
parameters:
    # Toggle global channels here
    enabled_channels: [ 'sms', 'email' ]
```

## Throttling (Rate Limiting)

Limits are defined in config/packages/rate_limiter.yaml. Currently configured to allow a specific number of
notifications per user per hour to prevent spam.

```
parameters:
    notification_rate_limit: 30
```

## Testing

The project includes a suite of tests to validate the DTO validation, provider logic, and queue management.

```
docker compose exec php php bin/phpunit
```

## API Endpoint:

```
POST /notifications
```

Request body:

```
{
    "userId": "1",
    "channels": ["sms", "email"],
    "scheduledDate": null, //2026-03-01 16:12:00
    "content": {
        "subject": "Hello!",
        "body": "Your verification code is 12345, ...",
        "phone": "+48733985973",
        "email": "dazkok@gmail.com"
    }
}
```

Validation Rules

- userId — required integer
- channels — required, at least one
- subject and body — required
- phone — required if channel = sms
- email — required if channel = email
- Channel validation is driven by ENUM logic

## Processing Flow (Architecture Overview)

1. Controller maps request to DTO and validates input
2. DTO validation checks required fields based on channel ENUM
3. Throttling is applied per user using Symfony RateLimiter
4. Notifications are persisted in DB (NotificationLog)
5. Jobs are dispatched to Redis queue with optional delay
6. Messenger worker consumes jobs
7. Providers are resolved dynamically per channel
8. Failover occurs automatically if a provider fails
9. Message status is updated (pending → sent / failed)

## Channel & Provider Abstraction

Providers implement a common interface:

- Supports multiple channels (email, SMS, push) and open for extension
- Multiple providers per channel supported
- Automatic failover if a provider fails

Implemented providers:

- AWS SES Email Provider
- Fake Secondary Email Provider (failover demo)
- Fake SMS Provider

HTML rendering is supported when provider implements HtmlCapableInterface.

## Retry Failed Notifications CLI

Dry-run retry (no execution)

```
docker compose exec php php bin/console app:retry --dry-run
```

Retry failed notifications

```
docker compose exec php php bin/console app:retry
```

Show command help

```
docker compose exec php php bin/console app:retry --help
```

## Features Implemented

- Multi-channel notifications
- Provider abstraction + failover
- Queue-based async processing (Redis + Messenger)
- Scheduled delivery support
- Per-user throttling
- Persistent delivery logs
- Template-based HTML email rendering
- Config-driven channel behavior
- Docker-ready out of the box
- Testable and extensible architecture

## Notes

Fake providers are included for demo and failover testing

Focus placed on reliability, extensibility, and production-like behavior

#

#

#

#

#

#

#

#

# Symfony Docker ORIGINAL README

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up --wait` to set up and start a fresh Symfony project
4. Open `https://localhost` in your favorite web browser
   and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Features

- Production, development and CI ready
- Just 1 service by default
- Blazing-fast performance thanks to [the worker mode of FrankenPHP](https://frankenphp.dev/docs/worker/)
- [Installation of extra Docker Compose services](docs/extra-services.md) with Symfony Flex
- Automatic HTTPS (in dev and prod)
- HTTP/3 and [Early Hints](https://symfony.com/blog/new-in-symfony-6-3-early-hints) support
- Real-time messaging thanks to a built-in [Mercure hub](https://symfony.com/doc/current/mercure.html)
- [Vulcain](https://vulcain.rocks) support
- Native [XDebug](docs/xdebug.md) integration
- Super-readable configuration

**Enjoy!**

## Docs

1. [Options available](docs/options.md)
2. [Using Symfony Docker with an existing project](docs/existing-project.md)
3. [Support for extra services](docs/extra-services.md)
4. [Deploying in production](docs/production.md)
5. [Debugging with Xdebug](docs/xdebug.md)
6. [TLS Certificates](docs/tls.md)
7. [Using MySQL instead of PostgreSQL](docs/mysql.md)
8. [Using Alpine Linux instead of Debian](docs/alpine.md)
9. [Using a Makefile](docs/makefile.md)
10. [Updating the template](docs/updating.md)
11. [Troubleshooting](docs/troubleshooting.md)

## License

Symfony Docker is available under the MIT License.

## Credits

Created by [Kévin Dunglas](https://dunglas.dev), co-maintained by [Maxime Helias](https://twitter.com/maxhelias) and
sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).
