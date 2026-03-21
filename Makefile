# Evolve Academy — convenience targets for Docker-based workflow.
# Requires: Docker + Docker Compose v2

.PHONY: help up down build logs test test-backend test-frontend test-integration seed clean

help:
	@echo "Targets:"
	@echo "  make up          — docker compose up -d --build"
	@echo "  make down        — docker compose down"
	@echo "  make build       — rebuild images"
	@echo "  make logs        — follow container logs"
	@echo "  make test        — full suite (backend + frontend + integration)"
	@echo "  make test-backend / test-frontend / test-integration — run one layer"
	@echo "  make seed        — insert 30 demo rows + mock CVs (needs stack up)"
	@echo "  make clean       — stop and remove volumes (wipes DB + uploads)"

up:
	docker compose up -d --build

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f

test:
	bash run-tests.sh

test-backend:
	docker build -t evolve-backend-test -f backend/Dockerfile.test backend/
	docker run --rm evolve-backend-test

test-frontend:
	docker build -t evolve-frontend-test -f frontend/Dockerfile.test frontend/
	docker run --rm evolve-frontend-test

test-integration:
	bash tests/integration.sh

seed:
	docker compose run --rm -v "$(CURDIR)/scripts/seed.php:/tmp/seed.php:ro" backend php /tmp/seed.php

clean:
	docker compose down -v
