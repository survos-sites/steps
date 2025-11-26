<?php

$task = $this->meiliClient->index('movies')->updateSettings([
    'searchableAttributes' => ['title', 'overview'],
    'filterableAttributes' => ['genre', 'year'],
    'sortableAttributes' => ['budget'],
]);

// version 1 style
$this->meiliClient->waitForTask($task['taskUid']);