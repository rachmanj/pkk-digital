#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

IMAGE_NAME="${IMAGE_NAME:-pkk-digital-app:latest}"
VM_USER="${VM_USER:-pkkadmin}"
VM_HOST="${VM_HOST:-103.59.95.229}"
REMOTE_DIR="${REMOTE_DIR:-pkk-digital}"
ARCHIVE_NAME="${ARCHIVE_NAME:-pkk-digital-app.tar.gz}"

cd "${REPO_ROOT}"

echo "==> Build image ${IMAGE_NAME}"
docker build -t "${IMAGE_NAME}" -f docker/production/Dockerfile .

echo "==> Simpan image ke ${ARCHIVE_NAME}"
docker save "${IMAGE_NAME}" | gzip > "${ARCHIVE_NAME}"

echo "==> Unggah artefak ke ${VM_USER}@${VM_HOST}:~/${REMOTE_DIR}/"
ssh "${VM_USER}@${VM_HOST}" "mkdir -p ~/${REMOTE_DIR}/docker/production"
scp "${ARCHIVE_NAME}" "${REPO_ROOT}/docker-compose.prod.yml" \
    "${VM_USER}@${VM_HOST}:~/${REMOTE_DIR}/"
scp "${REPO_ROOT}/docker/production/mysql.cnf" \
    "${VM_USER}@${VM_HOST}:~/${REMOTE_DIR}/docker/production/"

echo "==> Muat image dan jalankan stack di VM"
ssh "${VM_USER}@${VM_HOST}" bash -s <<EOF
set -euo pipefail
cd ~/${REMOTE_DIR}
gunzip -c ${ARCHIVE_NAME} | docker load
docker compose -f docker-compose.prod.yml up -d --no-build --wait
docker compose -f docker-compose.prod.yml ps
EOF

echo "==> Deploy selesai."
