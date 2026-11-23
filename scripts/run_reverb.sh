#!/bin/bash

# ==============================================================================
# Laravel Reverb Manager & Watchdog (Local Mac & cPanel Production)
# Supports: start | stop | restart | status | watchdog | doctor
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$APP_DIR/storage/logs/reverb.log"
PID_FILE="$APP_DIR/storage/framework/reverb.pid"
STOP_FILE="$APP_DIR/storage/framework/reverb.stopped"

mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/storage/framework"

log_line() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" >> "$LOG_FILE"
}

read_env_var() {
    local KEY="$1"
    local LINE=""
    [ -f "$APP_DIR/.env" ] || return 0
    LINE=$(grep -E "^[[:space:]]*${KEY}=" "$APP_DIR/.env" | tail -n 1 || true)
    [ -n "$LINE" ] || return 0
    LINE="${LINE#*=}"
    LINE=$(printf '%s' "$LINE" | tr -d '\r' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')
    case "$LINE" in
        \"*\")
            LINE="${LINE#\"}"
            LINE="${LINE%\"}"
            ;;
        \'*\')
            LINE="${LINE#\'}"
            LINE="${LINE%\'}"
            ;;
        *)
            LINE="${LINE%%#*}"
            LINE=$(printf '%s' "$LINE" | sed -e 's/[[:space:]]*$//')
            ;;
    esac
    printf '%s' "$LINE"
}

PHP_BIN=""
validate_php_cli() {
    local BIN="$1"
    [ -n "$BIN" ] || return 1

    local RESOLVED=""
    if [ -x "$BIN" ]; then
        RESOLVED="$BIN"
    elif command -v "$BIN" >/dev/null 2>&1; then
        RESOLVED="$(command -v "$BIN" 2>/dev/null || true)"
    fi
    [ -n "$RESOLVED" ] && [ -x "$RESOLVED" ] || return 1

    local OUT=""
    OUT=$("$RESOLVED" -r 'echo PHP_SAPI . "|" . PHP_VERSION_ID;' 2>/dev/null || true)
    case "$OUT" in
        cli\|[0-9]*)
            local VER="${OUT#cli|}"
            VER="${VER%%[!0-9]*}"
            if [ -n "$VER" ] && [ "$VER" -ge 80300 ] 2>/dev/null; then
                PHP_BIN="$RESOLVED"
                return 0
            fi
            ;;
    esac
    return 1
}

ENV_PHP_BIN=$(read_env_var "REVERB_PHP_BIN")
ENV_REVERB_HOST=$(read_env_var "REVERB_SERVER_HOST")
ENV_REVERB_PORT=$(read_env_var "REVERB_SERVER_PORT")

REVERB_BIND_HOST="${REVERB_SERVER_HOST:-${ENV_REVERB_HOST:-127.0.0.1}}"
REVERB_BIND_PORT="${REVERB_SERVER_PORT:-${ENV_REVERB_PORT:-8080}}"
ACTION="${1:-watchdog}"

resolve_php_bin() {
    local candidate=""
    for candidate in \
        "$ENV_PHP_BIN" \
        "/Applications/MAMP/bin/php/php8.4.27/bin/php" \
        "/Applications/MAMP/bin/php/php8.3.36/bin/php" \
        /Applications/MAMP/bin/php/php8.*/bin/php \
        "/usr/local/bin/ea-php85" \
        "/opt/cpanel/ea-php85/root/usr/bin/php" \
        "/usr/local/bin/ea-php84" \
        "/opt/cpanel/ea-php84/root/usr/bin/php" \
        "/usr/local/bin/ea-php83" \
        "/opt/cpanel/ea-php83/root/usr/bin/php" \
        "/opt/alt/php85/usr/bin/php" \
        "/opt/alt/php84/usr/bin/php" \
        "/opt/alt/php83/usr/bin/php" \
        "/usr/bin/ea-php85" \
        "/usr/bin/ea-php84" \
        "/usr/bin/ea-php83" \
        "/usr/local/bin/php-cli" \
        "/usr/bin/php-cli" \
        "/usr/local/bin/php" \
        "/usr/bin/php8.4" \
        "/usr/bin/php8.3" \
        "/usr/bin/php" \
        "$(command -v ea-php85 2>/dev/null || true)" \
        "$(command -v ea-php84 2>/dev/null || true)" \
        "$(command -v ea-php83 2>/dev/null || true)" \
        "$(command -v php-cli 2>/dev/null || true)" \
        "$(command -v php 2>/dev/null || true)" \
        "php"
    do
        if validate_php_cli "$candidate"; then
            return 0
        fi
    done
    return 1
}

get_running_pid() {
    if [ -f "$PID_FILE" ]; then
        local SAVED_PID=""
        SAVED_PID=$(tr -d '[:space:]' < "$PID_FILE" 2>/dev/null || true)
        case "$SAVED_PID" in
            ''|*[!0-9]*) ;;
            *)
                if kill -0 "$SAVED_PID" 2>/dev/null; then
                    if command -v ps >/dev/null 2>&1; then
                        local CMD=""
                        CMD=$(ps -p "$SAVED_PID" -o args= 2>/dev/null || true)
                        case "$CMD" in
                            *"reverb:start"*)
                                printf '%s' "$SAVED_PID"
                                return 0
                                ;;
                        esac
                    else
                        printf '%s' "$SAVED_PID"
                        return 0
                    fi
                fi
                ;;
        esac
    fi

    if command -v pgrep >/dev/null 2>&1; then
        local FOUND=""
        FOUND=$(pgrep -f "$APP_DIR/artisan reverb:start" 2>/dev/null | head -n 1 || true)
        if [ -n "$FOUND" ]; then
            printf '%s' "$FOUND"
            return 0
        fi
    fi

    if command -v ps >/dev/null 2>&1; then
        local FOUND_PS=""
        FOUND_PS=$(ps -eo pid=,args= 2>/dev/null | grep -F "$APP_DIR/artisan reverb:start" | grep -v grep | head -n 1 | awk '{print $1}' || true)
        if [ -n "$FOUND_PS" ]; then
            printf '%s' "$FOUND_PS"
            return 0
        fi
    fi

    return 1
}

probe_port() {
    [ -n "$PHP_BIN" ] || return 2
    "$PHP_BIN" -r '
        $h = $argv[1] === "0.0.0.0" || $argv[1] === "::" ? "127.0.0.1" : $argv[1];
        $p = (int) $argv[2];
        $s = @stream_socket_client("tcp://{$h}:{$p}", $e, $m, 0.5);
        if (is_resource($s)) { fclose($s); exit(0); }
        exit(1);
    ' "$REVERB_BIND_HOST" "$REVERB_BIND_PORT" 2>/dev/null
}

start_reverb_process() {
    if ! resolve_php_bin; then
        log_line "ERROR: No PHP >= 8.3 CLI binary found. Set REVERB_PHP_BIN in .env if your PHP CLI is in a custom path."
        return 1
    fi

    if [ ! -f "$APP_DIR/artisan" ]; then
        log_line "ERROR: Missing artisan file at $APP_DIR/artisan."
        return 1
    fi

    cd "$APP_DIR" || {
        log_line "ERROR: Failed to change directory to $APP_DIR."
        return 1
    }

    log_line "Starting Reverb using $PHP_BIN on ${REVERB_BIND_HOST}:${REVERB_BIND_PORT}..."
    nohup "$PHP_BIN" "$APP_DIR/artisan" reverb:start --host="$REVERB_BIND_HOST" --port="$REVERB_BIND_PORT" </dev/null >> "$LOG_FILE" 2>&1 &
    local NEW_PID=$!
    echo "$NEW_PID" > "$PID_FILE"
    disown "$NEW_PID" 2>/dev/null || true

    sleep 0.4
    if ! kill -0 "$NEW_PID" 2>/dev/null; then
        rm -f "$PID_FILE"
        log_line "ERROR: Reverb process ($NEW_PID) exited immediately after start. Check earlier output in storage/logs/reverb.log."
        return 1
    fi

    sleep 0.6
    if ! kill -0 "$NEW_PID" 2>/dev/null; then
        rm -f "$PID_FILE"
        log_line "ERROR: Reverb process ($NEW_PID) crashed shortly after startup (e.g. port ${REVERB_BIND_PORT} already in use or config error)."
        return 1
    fi

    return 0
}

case "$ACTION" in
    stop)
        touch "$STOP_FILE"
        if [ -f "$PID_FILE" ]; then
            SAVED_PID=$(tr -d '[:space:]' < "$PID_FILE" 2>/dev/null || true)
            if [ -n "$SAVED_PID" ] && kill -0 "$SAVED_PID" 2>/dev/null; then
                kill -15 "$SAVED_PID" 2>/dev/null || true
            fi
            rm -f "$PID_FILE"
        fi

        if command -v pkill >/dev/null 2>&1; then
            pkill -15 -f "$APP_DIR/artisan reverb:start" 2>/dev/null || true
        fi

        STOPPED=0
        for _ in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do
            if ! get_running_pid >/dev/null 2>&1; then
                STOPPED=1
                break
            fi
            sleep 0.2
        done

        if [ "$STOPPED" -eq 0 ] && command -v pkill >/dev/null 2>&1; then
            pkill -9 -f "$APP_DIR/artisan reverb:start" 2>/dev/null || true
        fi

        echo "stopped"
        exit 0
        ;;

    start)
        rm -f "$STOP_FILE"
        if get_running_pid >/dev/null 2>&1; then
            echo "already_running"
            exit 0
        fi

        if start_reverb_process; then
            echo "started"
            exit 0
        fi

        echo "failed"
        tail -n 15 "$LOG_FILE" 2>/dev/null || true
        exit 1
        ;;

    restart)
        bash "$0" stop
        sleep 0.5
        bash "$0" start
        ;;

    status)
        if RUNNING_PID=$(get_running_pid); then
            echo "running (pid: $RUNNING_PID, target: ${REVERB_BIND_HOST}:${REVERB_BIND_PORT})"
            exit 0
        fi
        echo "stopped"
        exit 1
        ;;

    doctor)
        echo "=== Obsisstant Reverb Diagnostics ==="
        echo "APP_DIR: $APP_DIR"
        echo "STOP_FILE present: $([ -f "$STOP_FILE" ] && echo 'YES (delete or run start to resume)' || echo 'no')"
        if resolve_php_bin; then
            echo "PHP CLI: $PHP_BIN ($($PHP_BIN -r 'echo PHP_VERSION . " (" . PHP_SAPI . ") ext: pcntl=" . (extension_loaded("pcntl") ? "yes" : "no") . ", sockets=" . (extension_loaded("sockets") ? "yes" : "no") . ", openssl=" . (extension_loaded("openssl") ? "yes" : "no");' 2>/dev/null))"
        else
            echo "PHP CLI: NOT FOUND (need CLI >= 8.3; set REVERB_PHP_BIN in .env)"
        fi
        echo ".env BROADCAST_CONNECTION: $(read_env_var 'BROADCAST_CONNECTION')"
        echo ".env REVERB_APP_ID set: $([ -n "$(read_env_var 'REVERB_APP_ID')" ] && echo 'yes' || echo 'NO')"
        echo ".env REVERB_APP_KEY set: $([ -n "$(read_env_var 'REVERB_APP_KEY')" ] && echo 'yes' || echo 'NO')"
        echo ".env REVERB_HOST/PORT/SCHEME: $(read_env_var 'REVERB_HOST'):$(read_env_var 'REVERB_PORT') ($(read_env_var 'REVERB_SCHEME'))"
        echo "Bind target: ${REVERB_BIND_HOST}:${REVERB_BIND_PORT}"
        if RUNNING_PID=$(get_running_pid); then
            echo "Reverb process: RUNNING (pid $RUNNING_PID)"
        else
            echo "Reverb process: NOT RUNNING"
        fi
        if probe_port; then
            echo "TCP port ${REVERB_BIND_HOST}:${REVERB_BIND_PORT}: OPEN"
        else
            echo "TCP port ${REVERB_BIND_HOST}:${REVERB_BIND_PORT}: CLOSED"
        fi
        echo "--- Last 15 lines of storage/logs/reverb.log ---"
        tail -n 15 "$LOG_FILE" 2>/dev/null || echo "(no log yet)"
        exit 0
        ;;

    watchdog|*)
        if [ -f "$STOP_FILE" ]; then
            exit 0
        fi
        if get_running_pid >/dev/null 2>&1; then
            exit 0
        fi
        start_reverb_process
        ;;
esac
