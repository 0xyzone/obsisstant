#!/bin/bash

# ==============================================================================
# Obsisstant Studio Relay Agent Watchdog (Local Mac & Self-Hosted Rigs)
# Supports: start | stop | restart | status | watchdog
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
AGENT_DIR="$APP_DIR/obs-agent"
LOG_FILE="$APP_DIR/storage/logs/obs-agent.log"
PID_FILE="$APP_DIR/storage/framework/obs-agent.pid"
STOP_FILE="$APP_DIR/storage/framework/obs-agent.stopped"

mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/storage/framework"

NODE_BIN=""
for candidate in \
    "/usr/local/bin/node" \
    "/opt/homebrew/bin/node" \
    "$(command -v node 2>/dev/null)" \
    "node"
do
    if [ -n "$candidate" ] && ([ -x "$candidate" ] || command -v "$candidate" >/dev/null 2>&1); then
        NODE_BIN="$candidate"
        break
    fi
done

# Prefer OBS_LOCAL_CA_CERT from root .env so system NODE_EXTRA_CA_CERTS overrides don't drop local Root CA
CA_CERT=""
if [ -f "$APP_DIR/.env" ]; then
    ENV_CA=$(grep -E '^OBS_LOCAL_CA_CERT=' "$APP_DIR/.env" | tail -n 1 | cut -d'=' -f2- | tr -d '"' | tr -d "'" | tr -d '\r')
    if [ -n "$ENV_CA" ] && [ -f "$ENV_CA" ]; then
        CA_CERT="$ENV_CA"
    fi
fi
if [ -z "$CA_CERT" ] && [ -n "${NODE_EXTRA_CA_CERTS:-}" ]; then
    CA_CERT="$NODE_EXTRA_CA_CERTS"
fi

ACTION="${1:-watchdog}"

is_running() {
    if [ -f "$PID_FILE" ]; then
        SAVED_PID=$(cat "$PID_FILE" 2>/dev/null)
        if [ -n "$SAVED_PID" ] && kill -0 "$SAVED_PID" 2>/dev/null; then
            return 0
        fi
    fi

    pgrep -f "$AGENT_DIR/src/index.js" >/dev/null 2>&1
}

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

        pkill -15 -f "$AGENT_DIR/src/index.js" 2>/dev/null

        STOPPED=0
        for _ in {1..15}; do
            if ! pgrep -f "$AGENT_DIR/src/index.js" >/dev/null 2>&1; then
                STOPPED=1
                break
            fi
            sleep 0.2
        done

        if [ "$STOPPED" -eq 0 ]; then
            pkill -9 -f "$AGENT_DIR/src/index.js" 2>/dev/null
        fi

        echo "stopped"
        exit 0
        ;;

    start)
        rm -f "$STOP_FILE"
        if is_running; then
            echo "already_running"
            exit 0
        fi

        if [ -z "$NODE_BIN" ]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Node.js binary not found." >> "$LOG_FILE"
            echo "error:no_node"
            exit 1
        fi

        if [ ! -f "$AGENT_DIR/.env" ]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: obs-agent/.env missing." >> "$LOG_FILE"
            echo "error:missing_env"
            exit 1
        fi

        cd "$AGENT_DIR" || exit 1
        if [ -n "$CA_CERT" ]; then
            NODE_EXTRA_CA_CERTS="$CA_CERT" nohup "$NODE_BIN" "$AGENT_DIR/src/index.js" >> "$LOG_FILE" 2>&1 &
        else
            nohup "$NODE_BIN" "$AGENT_DIR/src/index.js" >> "$LOG_FILE" 2>&1 &
        fi
        NEW_PID=$!
        echo "$NEW_PID" > "$PID_FILE"
        sleep 1

        if kill -0 "$NEW_PID" 2>/dev/null; then
            echo "started"
            exit 0
        else
            echo "error:failed_to_start"
            exit 1
        fi
        ;;

    restart)
        "$0" stop >/dev/null
        sleep 0.3
        "$0" start
        exit $?
        ;;

    status)
        if is_running; then
            echo "running"
            exit 0
        else
            echo "stopped"
            exit 1
        fi
        ;;

    watchdog)
        if [ -f "$STOP_FILE" ]; then
            exit 0
        fi
        if is_running; then
            exit 0
        fi
        "$0" start
        exit $?
        ;;

    *)
        echo "Usage: $0 {start|stop|restart|status|watchdog}"
        exit 1
        ;;
esac
