
#!/bin/sh
set -eu

mkdir -p /data/storage /data/uploads

if [ ! -L /app/storage ]; then
  if [ -d /app/storage ]; then
    cp -n /app/storage/* /data/storage/ 2>/dev/null || true
    rm -rf /app/storage
  fi
  ln -s /data/storage /app/storage
fi

if [ ! -L /app/public/uploads ]; then
  if [ -d /app/public/uploads ]; then
    rm -rf /app/public/uploads
  fi
  ln -s /data/uploads /app/public/uploads
fi

exec php -S "0.0.0.0:${PORT:-8080}" -t /app/public /app/router.php
