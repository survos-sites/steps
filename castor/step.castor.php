<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',      // bundle-level
    __DIR__ . '/../../vendor/autoload.php',   // one level up
    __DIR__ . '/../../../vendor/autoload.php' // monorepo root
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

use Castor\Attribute\AsTask;
use function Castor\{context, io};
use Survos\StepBundle\Util\ArtifactWriter;


#[AsTask(name: 'artifact:controller', description: 'Save AppController as an artifact for slides')]
function artifact_controller(): void
{
    $projectDir = context()->workingDirectory ?? getcwd();
    $writer = new ArtifactWriter($projectDir);

    $slide = 'barcode:controller';
    $src   = "{$projectDir}/src/Controller/AppController.php";

    if (!is_file($src)) {
        io()->warning("Missing {$src}");
        return;
    }

    $code = file_get_contents($src);
    $writer->save($slide, 'AppController.php', $code, 'code/php');

    io()->success("Saved artifact → public/artifacts/{$slide}/AppController.php");
}
