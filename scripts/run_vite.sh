#!/bin/bash

# ==============================================================================
# Vite Dev Hot-Reload Watcher (Local Development Only)
# Supports: start | stop | restart | status
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$APP_DIR/storage/logs/vite.log"
PID_FILE="$APP_DIR/storage/framework/vite.pid"

mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/storage/framework"

NODE_BIN="$(command -v node 2>/dev/null || echo /usr/local/bin/node)"
VITE_BIN="$APP_DIR/node_modules/.bin/vite"
ACTION="${1:-status}"

is_running() {
    if [ -f "$PID_FILE" ]; then
        local pid
        pid=$(cat "$PID_FILE" 2>/dev/null)
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
    fi

    pgrep -f "$APP_DIR/node_modules/.bin/vite" >/dev/null 2>&1 || pgrep -f "$APP_DIR/node_modules/vite/bin/vite.js" >/dev/null 2>&1
}

case "$ACTION" in
    stop)
        if [ -f "$PID_FILE" ]; then
            SAVED_PID=$(cat "$PID_FILE" 2>/dev/null)
            if [ -n "$SAVED_PID" ] && kill -0 "$SAVED_PID" 2>/dev/null; then
                kill -15 "$SAVED_PID" 2>/dev/null
            fi
            rm -f "$PID_FILE"
        fi
        pkill -15 -f "$APP_DIR/node_modules/.bin/vite" 2>/dev/null
        pkill -15 -f "$APP_DIR/node_modules/vite/bin/vite.js" 2>/dev/null
        rm -f "$APP_DIR/public/hot"
        echo "stopped"
        exit 0
        ;;

    start)
        if is_running; then
            echo "already_running"
            exit 0
        fi

        if [ ! -x "$VITE_BIN" ]; then
            echo "error:vite_not_installed"
            exit 1
        fi

        cd "$APP_DIR" || exit 1
        rm -f "$APP_DIR/public/hot"

        if command -v setsid >/dev/null 2>&1; then
            setsid nohup "$NODE_BIN" "$APP_DIR/node_modules/vite/bin/vite.js" </dev/null >> "$LOG_FILE" 2>&1 &
        else
            nohup "$NODE_BIN" "$APP_DIR/node_modules/vite/bin/vite.js" </dev/null >> "$LOG_FILE" 2>&1 &
        fi

        NEW_PID=$!
        echo "$NEW_PID" > "$PID_FILE"

        for _ in {1..25}; do
            if [ -f "$APP_DIR/public/hot" ] && kill -0 "$NEW_PID" 2>/dev/null; then
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
        sleep 0.4
        "$0" start
        ;;

    status)
        if is_running; then
            echo "running"
            exit 0
        fi
        echo "stopped"
        exit 1
        ;;
esac
