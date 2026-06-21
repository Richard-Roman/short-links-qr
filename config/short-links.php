<?php

return [
    'route_prefix' => 'l',
    'throttle' => '120,1',
    'cache' => [
        'ttl' => 3600,
        'prefix' => 'short_link_redirect:',
    ],
    'tables' => [
        'short_links' => 'short_links',
        'short_link_clicks' => 'short_link_clicks',
    ],
    'generator' => [
        'length' => (int) env('SHORT_LINKS_LENGTH', 8),
        'charset' => env('SHORT_LINKS_CHARSET', 'abcdefghjkmnpqrstuvwxyz23456789'),
    ],
    'route_pattern' => env('SHORT_LINKS_ROUTE_PATTERN', '[a-hjkmnp-z2-9]{8}'),
];
