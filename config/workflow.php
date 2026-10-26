<?php

return [
    'max_per_user' => (int) env('WORKFLOW_MAX_PER_USER', 50),
    'create_per_minute' => (int) env('WORKFLOW_CREATE_PER_MINUTE', 10),
    'max_nodes' => (int) env('WORKFLOW_MAX_NODES', 100),
    'max_edges' => (int) env('WORKFLOW_MAX_EDGES', 300),
    'webhook_base_url' => env('WORKFLOW_WEBHOOK_BASE_URL'),
    'http_block_private' => env('WORKFLOW_HTTP_BLOCK_PRIVATE', true),
];
