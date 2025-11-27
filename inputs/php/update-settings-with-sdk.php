<?php

$task = $this->meiliClient->index('movies')->updateSettings([
    'searchableAttributes' => ['title', 'overview'],
    'filterableAttributes' => ['genre', 'year'],
    'sortableAttributes' => ['budget'],
]);

$this->meiliClient->waitForTask($task['taskUid']);