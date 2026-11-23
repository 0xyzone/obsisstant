<?php

return [
    'relay_token' => env('OBS_RELAY_TOKEN'),
    'local_agent_enabled' => (bool) env('OBS_LOCAL_AGENT_ENABLED', false),
    'local_ca_cert' => env('OBS_LOCAL_CA_CERT'),
    'media_storage_path' => env('OBS_MEDIA_STORAGE_PATH', '~/Pictures/Obsisstant'),
];
