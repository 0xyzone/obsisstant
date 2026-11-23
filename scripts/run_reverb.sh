#!/bin/bash

# ==============================================================================
# Laravel Reverb Manager & Watchdog (Local Mac & cPanel Production)
# Supports: start | stop | restart | status | watchdog
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$APP_DIR/storage/logs/reverb.log"
PID_FILE="$APP_DIR/storage/framework/reverb.pid"
STOP_FILE="$APP_DIR/storage/framework/reverb.stopped"

mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/storage/framework"

PHP_BIN=""
for candidate in \
    "/Applications/MAMP/bin/php/php8.4.27/bin/php" \
    "/Applications/MAMP/bin/php/php8.3.36/bin/php" \
    "/usr/local/bin/ea-php84" \
    "/opt/cpanel/ea-php84/root/usr/bin/php" \
    "/usr/local/bin/ea-php83" \
    "/opt/cpanel/ea-php83/root/usr/bin/php" \
    "/usr/bin/ea-php84" \
    "/usr/bin/ea-php83" \
    "/usr/local/bin/php" \
    "$(command -v ea-php84 2>/dev/null)" \
    "$(command -v ea-php83 2>/dev/null)" \
    "$(command -v php 2>/dev/null)" \
    "php"
do
    if [ -n "$candidate" ] && ([ -x "$candidate" ] || command -v "$candidate" >/dev/null 2>&1); then
        PHP_VER=$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)
        PHP_SAPI=$("$candidate" -r 'echo PHP_SAPI;' 2>/dev/null || echo "")
        if [ "$PHP_VER" -ge 80300 ] && [ "$PHP_SAPI" = "cli" ]; then
            PHP_BIN="$candidate"
            break
        fi
    fi
done

read_env_var() {
    local KEY="$1"
    if [ -f "$APP_DIR/.env" ]; then
        grep -E "^${KEY}=" "$APP_DIR/.env" | tail -n 1 | cut -d'=' -f2- | tr -d '"' | tr -d "'" | tr -d '\r' | xargs
    fi
}

ENV_REVERB_HOST=$(read_env_var "REVERB_SERVER_HOST")
ENV_REVERB_PORT=$(read_env_var "REVERB_SERVER_PORT")

REVERB_BIND_HOST="${REVERB_SERVER_HOST:-${ENV_REVERB_HOST:-127.0.0.1}}"
REVERB_BIND_PORT="${REVERB_SERVER_PORT:-${ENV_REVERB_PORT:-8080}}"
ACTION="${1:-watchdog}"

case "$ACTION" in
    stop)
        touch "$STOP_FILE"
        if [ -f "$PID_FILE" ]; then
            SAVED_PID=$(cat "$PID_FILE" 2>/dev/null)
            if [ -n "$SAVED_PID" ] && kill -0 "$SAVED_PID" 2>/dev/null; then
                kill -15 "$SAVED_PID" 2>/dev/null
            fi
            rm -f "$PID_FILE"
        fi

        pkill -15 -f "$APP_DIR/artisan reverb:start" 2>/dev/null

        STOPPED=0
        for _ in {1..15}; do
            if ! pgrep -f "$APP_DIR/artisan reverb:start" >/dev/null 2>&1; then
                STOPPED=1
                break
            fi
            sleep 0.2
        done

        if [ "$STOPPED" -eq 0 ]; then
            pkill -9 -f "$APP_DIR/artisan reverb:start" 2>/dev/null
        fi

        echo "stopped"
        exit 0
        ;;

    start)
        rm -f "$STOP_FILE"
        if pgrep -f "$APP_DIR/artisan reverb:start" >/dev/null 2>&1; then
            echo "already_running"
            exit 0
        fi

        if [ -z "$PHP_BIN" ]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: No PHP >= 8.3 CLI binary found." >> "$LOG_FILE"
            echo "error:no_php_cli"
            exit 1
        fi

        cd "$APP_DIR" || exit 1
        nohup "$PHP_BIN" "$APP_DIR/artisan" reverb:start --host="$REVERB_BIND_HOST" --port="$REVERB_BIND_PORT" >> "$LOG_FILE" 2>&1 &
        NEW_PID=$!
        echo "$NEW_PID" > "$PID_FILE"

        for _ in {1..15}; do
            if pgrep -f "$APP_DIR/artisan reverb:start" >/dev/null 2>&1; then
                echo "started"
                exit 0
            fi
            sleep 0.2
        done

        echo "failed"
        exit 1
        ;;

    restart)
        "$0" stop
        sleep 0.5
        "$0" start
        ;;

    status)
        if pgrep -f "$APP_DIR/artisan reverb:start" >/dev/null 2>&1; then
            echo "running"
            exit 0
        fi
        echo "stopped"
        exit 1
        ;;

    watchdog|*)
        if [ -f "$STOP_FILE" ]; then
            exit 0
        fi
        if pgrep -f "$APP_DIR/artisan reverb:start" >/dev/null 2>&1; then
            exit 0
        fi
        if [ -z "$PHP_BIN" ]; then
            exit 1
        fi

        cd "$APP_DIR" || exit 1
        nohup "$PHP_BIN" "$APP_DIR/artisan" reverb:start --host="$REVERB_BIND_HOST" --port="$REVERB_BIND_PORT" >> "$LOG_FILE" 2>&1 &
        echo $! > "$PID_FILE"
        ;;
esac
