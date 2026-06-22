.DEFAULT_GOAL := help
COMPOSE := docker compose

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Levanta todo el stack (build + deps backend + arranque en segundo plano)
	$(COMPOSE) build
	$(COMPOSE) run --rm --no-deps api composer install --no-interaction
	$(COMPOSE) up -d

down: ## Detiene y elimina los contenedores
	$(COMPOSE) down

down-v: ## Detiene los contenedores y borra los volúmenes (reset total)
	$(COMPOSE) down -v

ps: ## Estado de los servicios
	$(COMPOSE) ps

logs: ## Logs en vivo (uso: make logs s=api)
	$(COMPOSE) logs -f $(s)

sh: ## Abre una shell en un servicio (uso: make sh s=api)
	$(COMPOSE) exec $(s) sh

api-install: ## Instala las dependencias Composer del backend (contra el volumen montado)
	$(COMPOSE) run --rm --no-deps api composer install --no-interaction

jwt-keys: ## Genera el par de claves JWT RS256 del backend (config/jwt, no versionado)
	$(COMPOSE) run --rm --no-deps api php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

test: ## Tests backend: smoke determinista (Unit + Functional), sin servicios externos
	$(COMPOSE) run --rm --no-deps api vendor/bin/phpunit --testsuite Unit,Functional

test-cov: ## Tests backend con informe de cobertura (PCOV)
	$(COMPOSE) run --rm --no-deps api vendor/bin/phpunit --testsuite Unit,Functional --coverage-text

test-integration: ## Tests de integración del backend (requiere el stack arriba: make up)
	$(COMPOSE) exec -T api vendor/bin/phpunit --testsuite Integration

lint: ## Análisis estático backend: PHPStan 9 + CS-Fixer + Rector + Deptrac + composer audit
	$(COMPOSE) run --rm --no-deps api sh -lc 'vendor/bin/phpstan analyse --no-progress --memory-limit=1G && vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none && vendor/bin/rector process --dry-run --no-progress-bar && vendor/bin/deptrac analyse --no-progress && composer audit'

lint-fix: ## Autofix backend: aplica CS-Fixer (estilo) y Rector (modernización)
	$(COMPOSE) run --rm --no-deps api sh -lc 'vendor/bin/php-cs-fixer fix --show-progress=none && vendor/bin/rector process --no-progress-bar'

seed: ## Carga datos de demostración (placeholder hasta F6)
	@echo "TODO: seed de datos demo (F6)"

.PHONY: help up down down-v ps logs sh api-install jwt-keys test test-cov test-integration lint lint-fix seed
