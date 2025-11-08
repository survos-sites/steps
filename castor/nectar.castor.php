<?php declare(strict_types=1);

const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?
const CASTOR_NAMESPACE = 'nectar';

/**
 * file: castor/easyadmin.castor.php
 * Demo orchestration for "EasyAdmin in 20 Minutes"
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) { if (is_file($autoload)) { require_once $autoload; break; } }

use Castor\Attribute\{AsTask, AsContext};
use Castor\Context;
use function Castor\{context, io};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Action\{
    Artifact, // writer, when adding an artifactId to bash or console isn't enough
    DisplayArtifact, // reader
    ComposerRequire,
    Env,
    Console,
    Bash,
    CopyFile,
    DisplayCode,
    BrowserVisit
};

#[AsTask('install', CASTOR_NAMESPACE, 'install nectar theme in public/nectar')]
#[Step(
    actions: [
        new Bash('wget nectar.zip -O nectar.zip'),
        new Bash('unzip nectar.zip'),
    ]
)]
function install(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('confirm', CASTOR_NAMESPACE,  'confirm installation worked')]
#[Step(
    actions: [
        // note: this is relative to the demo directory!  Not the steps directory
        new Bash('symfony open:local --path=/nectar/index.html'),
    ]
)]
function confirm(): void { RunStep::run(_actions_from_current_task(), context()); }


