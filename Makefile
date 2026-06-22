.DEFAULT_GOAL := help
COMPOSE := docker compose

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Levanta el stack (build + deps backend/frontend + arranque en segundo plano)
	$(COMPOSE) build
	$(COMPOSE) run --rm --no-deps api composer install --no-interaction
	$(COMPOSE) run --rm --no-deps web npm install
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

# ── Backend ──
test-api: ## Backend: smoke determinista (Unit + Functional), sin servicios externos
	$(COMPOSE) run --rm --no-deps api vendor/bin/phpunit --testsuite Unit,Functional

test-api-cov: ## Backend: tests con informe de cobertura (PCOV)
	$(COMPOSE) run --rm --no-deps api vendor/bin/phpunit --testsuite Unit,Functional --coverage-text

test-integration: ## Backend: tests de integración (requiere el stack arriba: make up)
	$(COMPOSE) exec -T api vendor/bin/phpunit --testsuite Integration

lint-api: ## Backend: PHPStan 9 + CS-Fixer + Rector + Deptrac + composer audit
	$(COMPOSE) run --rm --no-deps api sh -lc 'vendor/bin/phpstan analyse --no-progress --memory-limit=1G && vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none && vendor/bin/rector process --dry-run --no-progress-bar && vendor/bin/deptrac analyse --no-progress && composer audit'

lint-fix-api: ## Backend: autofix de estilo (CS-Fixer) y modernización (Rector)
	$(COMPOSE) run --rm --no-deps api sh -lc 'vendor/bin/php-cs-fixer fix --show-progress=none && vendor/bin/rector process --no-progress-bar'

# ── Frontend ──
web-install: ## Frontend: instala las dependencias npm
	$(COMPOSE) run --rm --no-deps web npm install

test-web: ## Frontend: tests (Vitest + Testing Library)
	$(COMPOSE) run --rm --no-deps web npm run test

lint-web: ## Frontend: TypeScript strict + ESLint + Prettier check
	$(COMPOSE) run --rm --no-deps web sh -lc 'npm run typecheck && npm run lint && npm run format:check'

lint-fix-web: ## Frontend: autofix de formato (Prettier)
	$(COMPOSE) run --rm --no-deps web npm run format

storybook: ## Frontend: arranca Storybook (http://localhost:6006)
	$(COMPOSE) run --rm --no-deps -p 6006:6006 web npm run storybook

build-web: ## Frontend: build de producción (tsc + Vite)
	$(COMPOSE) run --rm --no-deps web npm run build

# ── Agregados (backend + frontend) ──
test: test-api test-web ## Ejecuta los tests de backend y frontend

lint: lint-api lint-web ## Ejecuta el análisis estático de backend y frontend

lint-fix: lint-fix-api lint-fix-web ## Aplica autofix de backend y frontend

seed: ## Carga datos de demostración (placeholder hasta F6)
	@echo "TODO: seed de datos demo (F6)"

.PHONY: help up down down-v ps logs sh api-install jwt-keys web-install test-api test-api-cov test-integration lint-api lint-fix-api test-web lint-web lint-fix-web storybook build-web test lint lint-fix seed
