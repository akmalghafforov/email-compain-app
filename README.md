# Email Campaign Management System

A full-stack application for creating, managing, and sending email campaigns to subscribers. Built with Laravel 12, Nuxt 3, and Docker.

## Tech Stack

**Backend:** Laravel 12 (PHP 8.4), PostgreSQL 16, Redis 7
**Frontend:** Nuxt 3 (Vue 3), Tailwind CSS 4
**Infrastructure:** Docker & Docker Compose
**Testing:** PHPUnit 11 (SQLite in-memory)

## Features

- **Campaign Management** — Create, edit, schedule, and dispatch email campaigns
- **Template Engines** — Blade, Twig, Markdown, and MJML support with variable interpolation
- **Subscriber Management** — Manage subscribers with status tracking and metadata
- **Multi-Channel Delivery** — SMTP, SendGrid, and Mailgun email senders
- **Queue-Based Processing** — Reliable email delivery via Redis job queues with automatic retry (3 attempts)
- **Delivery Tracking** — Track opens (pixel-based), clicks, and delivery failures
- **Campaign Statistics** — Real-time sent count, open rate, and click metrics

## Project Structure

```
├── app/
│   ├── Http/Controllers/Api/    # Campaign, Template, Subscriber controllers
│   ├── Models/                  # Eloquent models
│   ├── Services/
│   │   ├── Delivery/            # DeliveryTracker (logs and stats)
│   │   ├── EmailSenders/        # SMTP, SendGrid, Mailgun senders
│   │   └── Template/            # Template rendering engines
│   ├── Jobs/                    # SendCampaignEmailJob
│   ├── Repositories/            # Eloquent repository implementations
│   ├── Contracts/               # Repository and sender interfaces
│   ├── DTOs/                    # Data Transfer Objects
│   └── Enums/                   # Status and type enumerations
├── frontend/
│   ├── pages/                   # Dashboard, campaigns, subscribers
│   ├── components/              # Reusable Vue components
│   ├── composables/             # API and pagination composables
│   └── server/                  # API proxy middleware
├── database/
│   ├── migrations/              # Schema migrations
│   ├── factories/               # Model factories
│   └── seeders/                 # Database seeders
├── tests/
│   ├── Feature/                 # API integration tests
│   └── Unit/                    # Service and provider tests
├── docker/                      # Docker configuration
├── docker-compose.yml           # Multi-container orchestration
├── Dockerfile                   # PHP-FPM image
└── Makefile                     # Development commands
```

## Getting Started

### Prerequisites

- Docker & Docker Compose

### Setup

```bash
# Copy environment file
cp .env.example .env

# Build and start all services
make build
make up

# Run database migrations
make migrate

# (Optional) Seed the database
make seed
```

### Accessing the Application

| Service      | URL                        |
|--------------|----------------------------|
| Frontend     | http://localhost:3000       |
| Backend API  | http://localhost:8000/api   |
| PostgreSQL   | localhost:5432              |
| Redis        | localhost:6379              |

## API Endpoints

### Campaigns

| Method | Endpoint                        | Description                |
|--------|---------------------------------|----------------------------|
| GET    | `/api/campaigns`                | List campaigns (paginated) |
| POST   | `/api/campaigns`                | Create a campaign          |
| GET    | `/api/campaigns/{id}`           | Get campaign details       |
| PUT    | `/api/campaigns/{id}`           | Update a campaign          |
| POST   | `/api/campaigns/{id}/dispatch`  | Dispatch a campaign        |
| GET    | `/api/campaigns/{id}/stats`     | Get campaign statistics    |

### Templates

| Method | Endpoint          | Description                |
|--------|-------------------|----------------------------|
| GET    | `/api/templates`  | List templates (paginated) |
| POST   | `/api/templates`  | Create a template          |

### Subscribers

| Method | Endpoint            | Description                  |
|--------|---------------------|------------------------------|
| GET    | `/api/subscribers`  | List subscribers (paginated) |

### Tracking

| Method | Endpoint                  | Description        |
|--------|---------------------------|--------------------|
| GET    | `/t/{trackingId}/open`    | Track email opens  |
| GET    | `/t/{trackingId}/click`   | Track link clicks  |

## Make Commands

```bash
make up              # Start containers
make down            # Stop containers
make restart         # Restart all services
make build           # Build/rebuild images
make migrate         # Run database migrations
make fresh           # Reset database and seed
make seed            # Run seeders
make test            # Run all tests
make test-unit       # Run unit tests only
make test-feature    # Run feature tests only
make shell           # Access app container bash
make db-shell        # Access PostgreSQL shell
make redis-shell     # Access Redis CLI
make logs            # View all logs
make queue-logs      # View queue worker logs
make scheduler-logs  # View scheduler logs
```

## Environment Configuration

Key variables in `.env`:

| Variable            | Default   | Description                                  |
|---------------------|-----------|----------------------------------------------|
| `APP_ENV`           | `local`   | Application environment                      |
| `APP_DEBUG`         | `true`    | Debug mode                                   |
| `DB_CONNECTION`     | `pgsql`   | Database driver                              |
| `QUEUE_CONNECTION`  | `redis`   | Job queue driver                             |
| `CACHE_STORE`       | `redis`   | Cache driver                                 |
| `MAIL_MAILER`       | `log`     | Email driver (log, smtp, sendgrid, mailgun)  |

## Testing

Tests use SQLite in-memory database for fast execution.

```bash
make test            # Run all tests
make test-unit       # Unit tests only
make test-feature    # Feature/integration tests only
```

**Unit tests** cover template engines, delivery tracker, email senders, and service providers.
**Feature tests** cover API endpoints for campaigns, templates, and subscribers.

## Docker Services

| Service     | Description                    |
|-------------|--------------------------------|
| `app`       | Laravel (PHP-FPM) on port 8000 |
| `frontend`  | Nuxt dev server on port 3000   |
| `postgres`  | PostgreSQL 16 database         |
| `redis`     | Redis 7 (cache + queue)        |
| `queue`     | Background job worker          |
| `scheduler` | Laravel task scheduler         |
