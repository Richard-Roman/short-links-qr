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
];
