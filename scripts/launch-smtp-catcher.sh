#!/usr/bin/env bash
# Launches a local Mailpit container for capturing outgoing dev mail.
# Idempotent: if the container is already running, this is a no-op.
#
# Web UI:  http://localhost:8082
# SMTP:    localhost:1026

set -euo pipefail

CONTAINER_NAME="mailpoet-smtp"
VOLUME_NAME="mailpoet-smtp-data"
IMAGE="axllent/mailpit"

if ! command -v docker >/dev/null 2>&1; then
  echo "  ! docker not found — skipping SMTP catcher launch"
  exit 0
fi

if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
  echo "  ✓ SMTP catcher already running at http://localhost:8082"
  exit 0
fi

# Remove any stopped container with the same name
docker rm -f "$CONTAINER_NAME" >/dev/null 2>&1 || true

# Named volume persists captured emails across container restarts
docker volume create "$VOLUME_NAME" >/dev/null

docker run -d --rm \
  --name "$CONTAINER_NAME" \
  -p 1026:1025 \
  -p 8082:8025 \
  -v "$VOLUME_NAME:/data" \
  -e MP_DATABASE=/data/mailpit.db \
  "$IMAGE" >/dev/null

echo "  ✓ SMTP catcher started at http://localhost:8082 (SMTP on 1026, emails persist in docker volume '$VOLUME_NAME')"
