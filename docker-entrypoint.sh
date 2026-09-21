#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force

# Runs the AI-analysis job (and anything else queued) in the background so
# the web process (below) never blocks a request on it. --tries/--backoff
# survive the FastAPI service's own cold starts on Render's free tier.
php artisan queue:work --sleep=3 --tries=3 --backoff=10 --max-time=3600 &

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
