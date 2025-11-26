<?php

// get client
$response = $this->meiliClient->request('POST', '/indexes/movies/documents', [
    'json' => [
        [
            'id'       => 1,
            'title'    => 'Pony Movie',
            'overview' => 'A movie about a pony',
            'genre'    => 'family',
            'year'     => 2024,
        ],
    ],
]);

$task = $response->toArray();
