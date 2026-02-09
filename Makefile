DC := docker compose

.PHONY: help up down start stop restart build logs ps shell db-shell redis-shell install migrate fresh seed test queue-logs scheduler-logs chown

help: ## Show this help menu
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Start containers in detached mode
	$(DC) up -d

down: ## Stop and remove containers, networks, images, and volumes
	$(DC) down

start: ## Start existing containers
	$(DC) start

stop: ## Stop running containers
	$(DC) stop

restart: ## Restart all services
	$(DC) restart

build: ## Build or rebuild services
	$(DC) build

rebuild: ## Force rebuild of services
	$(DC) up -d --build --force-recreate

logs: ## View output from containers (follow mode)
	$(DC) logs -f

ps: ## List containers
	$(DC) ps

shell: ## Access the shell of the main app container
	$(DC) exec app bash

db-shell: ## Access the PostgreSQL database shell
	$(DC) exec postgres psql -U $${DB_USERNAME:-postgres} -d $${DB_DATABASE:-email_campaign}

redis-shell: ## Access the Redis shell
	$(DC) exec redis redis-cli

install: ## Run composer install and npm install inside the container
	$(DC) exec app composer install
	$(DC) exec app npm install

migrate: ## Run database migrations
	$(DC) exec app php artisan migrate

fresh: ## Wipe database and run migrations with seed
	$(DC) exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(DC) exec app php artisan db:seed

test: ## Run all PHPUnit tests
	$(DC) exec app php artisan test

test-unit: ## Run Unit tests
	$(DC) exec app php artisan test tests/Unit

test-feature: ## Run Feature tests
	$(DC) exec app php artisan test tests/Feature

queue-logs: ## Watch queue logs
	$(DC) logs -f queue

scheduler-logs: ## Watch scheduler logs
	$(DC) logs -f scheduler

chown: ## Fix file permissions for www-data user
	$(DC) exec app chown -R www-data:www-data /var/www/html
