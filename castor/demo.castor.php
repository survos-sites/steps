<?php
// castor.php (project root or wherever you run castor from)

use Castor\Attribute\AsTask;
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Action\{Bash, Console, ComposerRequire, Env};
require __DIR__ . '/../vendor/autoload.php';

#[AsTask(name: '1-install', description: 'Install StepBundle and EasyAdmin')]
#[Step(
    title: 'Install bundles',
    description: "we'll do them all now to get it out of the way.  Can we put markdown here?",
    bullets: ['SurvosStepBundle', 'EasyAdmin'],
    actions: [
        new ComposerRequire(
            dev: false,
            packages: [
                'easycorp/easyadmin-bundle'
            ]
        )
    ],
)]
function install_bundles(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: '2-env', description: 'Set local env')]
#[Step(
    title: 'Configure environment',
    bullets: ['Development mode', 'Meilisearch defaults'],
    actions: [
        new Env('MEILISEARCH_URL', ['default' => 'http://127.0.0.1:7700'], file: '.env.local', cwd: 'demo'),
        new Env('MEILISEARCH_API_KEY', ['default' => 'masterKey'], file: '.env.local', cwd: 'demo'),
        new Console('cache:clear', note: 'Pick up env changes', cwd: 'demo'),
    ],
)]
function set_env(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: '3-run', description: 'Run the app ' . __FILE__ . '/' . __METHOD__)]
#[Step(
    title: 'Run the app',
    actions: [
        new Bash('symfony server:start -d', cwd: 'demo'),
        new Bash('symfony open:local', cwd: 'demo'),
    ],
)]
function run_app(): void { RunStep::run(_actions_from_current_task(), context()); }

