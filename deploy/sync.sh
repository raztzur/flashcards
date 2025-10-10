#!/usr/bin/env bash
set -euo pipefail

# Flashcards deploy script
# Usage:
#   SERVER=161.35.21.130 USER=root DEST=/var/www/flashcards ./deploy/sync.sh
# Optional:
#   DRY=1 SERVER=... USER=... DEST=... ./deploy/sync.sh  # Dry-run

SERVER=${SERVER:-}
USER=${USER:-root}
DEST=${DEST:-}
DRY=${DRY:-0}

if [[ -z "$SERVER" || -z "$DEST" ]]; then
  echo "Usage: SERVER=<ip> USER=<ssh-user> DEST=</remote/path> $0"
  echo "Example: SERVER=161.35.21.130 USER=root DEST=/var/www/flashcards $0"
  exit 1
fi

echo "🚀 Deploying to $USER@$SERVER:$DEST"

RSYNC_FLAGS=( -avz --delete --checksum )
EXCLUDES=(
  "--exclude=.git/"
  "--exclude=.github/"
  "--exclude=node_modules/"
  "--exclude=content/"           # keep server content intact
  "--exclude=site/cache/"        # don't upload local cache
  "--exclude=site/sessions/"     # don't upload local sessions
  "--exclude=media/.jobs/"       # internal Kirby jobs folder
  "--exclude=.DS_Store" "--exclude=*.DS_Store"
)

if [[ "$DRY" == "1" ]]; then
  RSYNC_FLAGS+=( --dry-run )
  echo "(Dry run)"
fi

echo "📦 Syncing files (excluding content/)..."
rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" ./ "$USER@$SERVER:$DEST/"

echo "🧹 Clearing Kirby cache on server..."
ssh "$USER@$SERVER" "rm -rf '$DEST/site/cache/'* || true"

echo "🔐 Fixing permissions (non-fatal if groups/users differ)..."
ssh "$USER@$SERVER" "chmod -R 755 '$DEST' || true"

echo "✅ Deploy complete."
