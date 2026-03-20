#!/bin/bash
set -euo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

EXIT_CODE=0

echo -e "\n${CYAN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Evolve Academy — Suite de Tests Completa   ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}\n"

# ── 1. Backend Unit Tests (PHPUnit) ──────────────────────────
echo -e "${YELLOW}▶ [1/3] Backend — PHPUnit${NC}\n"

docker build -t evolve-backend-test -f backend/Dockerfile.test backend/ 2>/dev/null

if docker run --rm evolve-backend-test; then
  echo -e "\n${GREEN}✓ Backend tests passed${NC}\n"
else
  echo -e "\n${RED}✗ Backend tests failed${NC}\n"
  EXIT_CODE=1
fi

# ── 2. Frontend Unit Tests (Vitest) ──────────────────────────
echo -e "${YELLOW}▶ [2/3] Frontend — Vitest${NC}\n"

docker build -t evolve-frontend-test -f frontend/Dockerfile.test frontend/ 2>/dev/null

if docker run --rm evolve-frontend-test; then
  echo -e "\n${GREEN}✓ Frontend tests passed${NC}\n"
else
  echo -e "\n${RED}✗ Frontend tests failed${NC}\n"
  EXIT_CODE=1
fi

# ── 3. Integration Tests ─────────────────────────────────────
echo -e "${YELLOW}▶ [3/3] Integración — API + Security${NC}\n"

COMPOSE_RUNNING=$(docker compose ps --status running -q 2>/dev/null | wc -l)

if [[ "$COMPOSE_RUNNING" -lt 3 ]]; then
  echo -e "  Levantando servicios..."
  docker compose up -d --build 2>/dev/null
  echo -e "  Esperando a que estén listos..."
  sleep 10
fi

if bash tests/integration.sh; then
  echo -e "${GREEN}✓ Integration tests passed${NC}\n"
else
  echo -e "${RED}✗ Integration tests failed${NC}\n"
  EXIT_CODE=1
fi

# ── Summary ───────────────────────────────────────────────────
echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
if [[ $EXIT_CODE -eq 0 ]]; then
  echo -e "${CYAN}║${NC}   ${GREEN}✓ Todos los tests han pasado${NC}               ${CYAN}║${NC}"
else
  echo -e "${CYAN}║${NC}   ${RED}✗ Algunos tests han fallado${NC}                ${CYAN}║${NC}"
fi
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}\n"

exit $EXIT_CODE
