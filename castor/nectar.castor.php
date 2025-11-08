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

#[AsTask('purchase', CASTOR_NAMESPACE, 'purchase license')]
#[Step(
    bullets: [
        'nectar theme does ...',
        'Purchase it at theme forest and download to ~/Downloads',
    ]
)]
function purchase(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('install', CASTOR_NAMESPACE, 'install nectar theme in public/nectar')]
#[Step(
    actions: [
        new Bash('unzip ~/Downloads/themeforest-a7leii4Y-nectar-mobile-web-app-kit.zip -d public'),
    ]
)]
function install(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('confirm', CASTOR_NAMESPACE,  'confirm installation worked')]
#[Step(
    actions: [
        // note: this is relative to the demo directory!  Not the steps directory
        new Bash('ls public/nectar/classic/www/ -lh', a: 'nectar/ls--public'),
        new DisplayArtifact('nectar/ls--public'),
        new Bash('symfony open:local --path=/nectar/classic/www/index.html'),
    ]
)]
function confirm(): void { RunStep::run(_actions_from_current_task(), context()); }


