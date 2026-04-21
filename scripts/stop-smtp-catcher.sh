#!/usr/bin/env bash
# Stops the local Mailpit SMTP catcher container started by launch-smtp-catcher.sh.
# No-op if the container isn't running.

set -euo pipefail

CONTAINER_NAME="mailpoet-smtp"

if ! command -v docker >/dev/null 2>&1; then
  exit 0
fi

if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
  docker stop "$CONTAINER_NAME" >/dev/null 2>&1 || true
  echo "  ✓ SMTP catcher stopped"
fi
