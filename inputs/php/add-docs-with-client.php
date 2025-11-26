<?php

$client = new Client('http://127.0.0.1:7700', 'masterKey');
// index is an ENDPOINT service
$index = $client->index('movies');
$task = $index->addDocuments([
    ['id' => 4,  'title' => 'Mad Max: Fury Road', 'genres' => ['Adventure, Science Fiction']],
    ['id' => 5,  'title' => 'Moana', 'genres' => ['Fantasy, Action']],
    ['id' => 6,  'title' => 'Philadelphia', 'year' => 1997, 'genres' => ['Drama']],
]);
$task->wait(); // optional, wait until async finished
