.DEFAULT_GOAL := help
COMPOSE := docker compose

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Levanta todo el stack (build + arranque en segundo plano)
	$(COMPOSE) up -d --build

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

test: ## Ejecuta la batería de tests (placeholder hasta F2/F3)
	@echo "TODO: tests backend (F2) + frontend (F3)"

lint: ## Ejecuta linters y análisis estático (placeholder hasta F2/F3)
	@echo "TODO: PHPStan 9 / CS-Fixer (F2) + ESLint / Prettier (F3)"

seed: ## Carga datos de demostración (placeholder hasta F6)
	@echo "TODO: seed de datos demo (F6)"

.PHONY: help up down down-v ps logs sh test lint seed
