#!/bin/bash
# HTTP integration tests against the running stack (frontend nginx, PHP API, optional phpMyAdmin).
# Uses curl to assert status codes, headers, CSRF, validation, honeypot, CORS, and route hardening.

set -euo pipefail

# ── ANSI colors for pass/fail lines ───────────────────────────
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# ── Counters and base URLs (overridable via env) ─────────────
PASS=0
FAIL=0
API="http://localhost:${API_PORT:-80}"
FRONTEND="http://localhost:${FRONTEND_PORT:-8080}"

# Asserts that `actual` output contains substring `expected`; bumps PASS/FAIL.
assert() {
  local desc="$1"
  local expected="$2"
  local actual="$3"

  if [[ "$actual" == *"$expected"* ]]; then
    echo -e "  ${GREEN}✓${NC} $desc"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc"
    echo -e "    Expected: ${expected}"
    echo -e "    Got:      $(echo "$actual" | head -3)"
    FAIL=$((FAIL + 1))
  fi
}

# Asserts HTTP status code for a URL and optional method (default GET).
assert_status() {
  local desc="$1"
  local expected="$2"
  local url="$3"
  local method="${4:-GET}"

  local status
  status=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url" 2>/dev/null) || true

  if [[ "$status" == "$expected" ]]; then
    echo -e "  ${GREEN}✓${NC} $desc (HTTP $status)"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc (expected $expected, got $status)"
    FAIL=$((FAIL + 1))
  fi
}

# Fetches a CSRF token from the API JSON endpoint (with short delay for readiness).
get_csrf() {
  sleep 0.5
  local resp
  resp=$(curl -s "$API/api/csrf-token" 2>/dev/null) || true
  echo "$resp" | grep -o '"token":"[^"]*"' | cut -d'"' -f4 || true
}

# ── Wait for services ────────────────────────────────────────
# Polls frontend until it responds or times out after 30s.
echo -e "\n${YELLOW}Esperando a que los servicios estén listos...${NC}"
for i in $(seq 1 30); do
  if curl -s "$FRONTEND" > /dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} Servicios listos (${i}s)"
    break
  fi
  if [[ $i -eq 30 ]]; then
    echo -e "  ${RED}✗${NC} Timeout esperando servicios"
    exit 1
  fi
  sleep 1
done

# ── Frontend ──────────────────────────────────────────────────
# Smoke test: HTML shell, app mount, assets, and static 200s.
echo -e "\n${YELLOW}═══ Frontend ═══${NC}"

response=$(curl -s "$FRONTEND")
assert "Frontend sirve HTML" "<!DOCTYPE html>" "$response"
assert "Frontend contiene app div" 'id="app"' "$response"
assert "Frontend carga app.js" "src=\"/src/app.js\"" "$response"

assert_status "Frontend devuelve 200" "200" "$FRONTEND"
assert_status "Frontend CSS devuelve 200" "200" "$FRONTEND/styles.css"
assert_status "Frontend JS devuelve 200" "200" "$FRONTEND/src/app.js"
assert_status "Logo SVG devuelve 200" "200" "$FRONTEND/assets/logo-evolve.svg"

sleep 1

# ── Security Headers ─────────────────────────────────────────
# nginx / app security headers and API CORS exposure for CSRF.
echo -e "\n${YELLOW}═══ Security Headers ═══${NC}"

headers=$(curl -s -I "$FRONTEND")
assert "X-Content-Type-Options present" "nosniff" "$headers"
assert "X-Frame-Options present" "DENY" "$headers"
assert "Referrer-Policy present" "strict-origin-when-cross-origin" "$headers"
assert "server_tokens off" "nginx" "$headers"

sleep 0.5
api_headers=$(curl -s -D - -o /dev/null "$API/api/csrf-token")
assert "API CORS header present" "Access-Control-Allow-Origin" "$api_headers"
assert "API allows CSRF header" "X-CSRF-Token" "$api_headers"

sleep 1

# ── CSRF Token Endpoint ──────────────────────────────────────
# JSON token endpoint returns a non-empty token string.
echo -e "\n${YELLOW}═══ CSRF Token ═══${NC}"

csrf_response=$(curl -s "$API/api/csrf-token")
assert "CSRF endpoint returns JSON" "token" "$csrf_response"

token=$(echo "$csrf_response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
if [[ -n "$token" ]]; then
  echo -e "  ${GREEN}✓${NC} CSRF token received (${#token} chars)"
  PASS=$((PASS + 1))
else
  echo -e "  ${RED}✗${NC} CSRF token is empty"
  FAIL=$((FAIL + 1))
fi

sleep 1

# ── API Validation ────────────────────────────────────────────
# Wrong Content-Type → 415; empty multipart → 422 with field errors in body.
echo -e "\n${YELLOW}═══ API Validation ═══${NC}"

# POST without Content-Type multipart/form-data
csrf_ct=$(get_csrf)
ct_status=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $csrf_ct" \
  -H "Origin: http://localhost:8080" \
  -d '{}' \
  "$API/api/submit" 2>/dev/null) || true

if [[ "$ct_status" == "415" ]]; then
  echo -e "  ${GREEN}✓${NC} Rejects non-multipart POST (HTTP $ct_status)"
  PASS=$((PASS + 1))
else
  echo -e "  ${RED}✗${NC} Rejects non-multipart POST (expected 415, got $ct_status)"
  FAIL=$((FAIL + 1))
fi

sleep 1

# POST with empty form data (validation errors)
csrf_val=$(get_csrf)
validation_response=$(curl -s -w "\n%{http_code}" -X POST \
  -H "X-CSRF-Token: $csrf_val" \
  -H "Origin: http://localhost:8080" \
  -F "firstName=" \
  -F "website=" \
  "$API/api/submit" 2>/dev/null)

status=$(echo "$validation_response" | tail -1)
body=$(echo "$validation_response" | head -n -1)

assert "Empty form returns 422 validation error" "422" "$status"
assert "Validation response contains fields" "fields" "$body"

sleep 1

# ── Honeypot ──────────────────────────────────────────────────
# Hidden website field filled → fake success (spam trap).
echo -e "\n${YELLOW}═══ Honeypot ═══${NC}"

csrf_hp=$(get_csrf)
honeypot_response=$(curl -s -X POST \
  -H "X-CSRF-Token: $csrf_hp" \
  -H "Origin: http://localhost:8080" \
  -F "firstName=Bot" \
  -F "website=http://spam.com" \
  "$API/api/submit" 2>/dev/null)

assert "Honeypot returns fake success" "Solicitud recibida correctamente" "$honeypot_response"

sleep 1

# ── Method Restrictions ───────────────────────────────────────
# Submit route only allows POST (and OPTIONS); others → 405.
echo -e "\n${YELLOW}═══ Method Restrictions ═══${NC}"

assert_status "PUT method returns 405" "405" "$API/api/submit" "PUT"
assert_status "DELETE method returns 405" "405" "$API/api/submit" "DELETE"
assert_status "PATCH method returns 405" "405" "$API/api/submit" "PATCH"

sleep 0.5

# ── Route Protection ─────────────────────────────────────────
# Unknown API paths and sensitive dotfiles return 404.
echo -e "\n${YELLOW}═══ Route Protection ═══${NC}"

assert_status "Unknown API route returns 404" "404" "$API/api/nonexistent"
assert_status ".env not accessible" "404" "$API/.env"
assert_status ".git not accessible" "404" "$API/.git/config"

sleep 0.5

# ── OPTIONS (CORS Preflight) ─────────────────────────────────
# Browser preflight for POST from frontend origin → 204.
echo -e "\n${YELLOW}═══ CORS Preflight ═══${NC}"

options_response=$(curl -s -o /dev/null -w "%{http_code}" -X OPTIONS \
  -H "Origin: http://localhost:8080" \
  -H "Access-Control-Request-Method: POST" \
  "$API/api/submit")

assert "OPTIONS returns 204" "204" "$options_response"

sleep 0.5

# ── phpMyAdmin ────────────────────────────────────────────────
# Optional DB admin UI reachable on PMA_PORT (default 8081).
echo -e "\n${YELLOW}═══ phpMyAdmin ═══${NC}"

pma_port="${PMA_PORT:-8081}"
assert_status "phpMyAdmin accessible" "200" "http://localhost:$pma_port"

# ── Summary ───────────────────────────────────────────────────
# Exit 1 if any assertion failed; otherwise 0.
echo -e "\n${YELLOW}═══════════════════════════════════════${NC}"
TOTAL=$((PASS + FAIL))
echo -e "  Total:  $TOTAL"
echo -e "  ${GREEN}Passed: $PASS${NC}"
if [[ $FAIL -gt 0 ]]; then
  echo -e "  ${RED}Failed: $FAIL${NC}"
  echo -e "${YELLOW}═══════════════════════════════════════${NC}\n"
  exit 1
else
  echo -e "  Failed: 0"
  echo -e "${YELLOW}═══════════════════════════════════════${NC}\n"
  exit 0
fi
