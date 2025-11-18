<?php
// File: castor/bunny.castor.php

use Castor\Attribute\{AsTask, AsContext};
use Castor\Context;
use Survos\StepBundle\Runtime\RunStep;
use function Castor\{variable, context};

// call the runner class directly

use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\Actions\{
    OpenUrl,
};
use Survos\StepBundle\Action\{
    YamlWrite,
    Env,
    Bullet,
    Bash,
    Console,
    ComposerRequire
};


/**
 * 1) Install bundles and libraries
 */
#[AsTask(name: 'create', description: 'Symfony new with webapp')]
#[Step(
    title: 'symfony new --webapp',
    bullets: [
        'Symfony new',
        'tweak docker-compose.yml',
    ],
)]
function create(): void {
    // let's require that the project be created separately, using castor symfony:new
    // this is just for the slide
    RunStep::run(_actions_from_current_task(), context());
}


/**
 * 1) Install bundles and libraries
 */
#[AsTask(name: 'install', description: 'Install Bunny bundle (and optional admin table helper)')]
#[Step(
    title: 'Install bundles & libraries',
    description: 'Add Survos Bunny bundle (and optional Datatables helper).',
    actions: [
        new ComposerRequire(
            packages: ['survos/bunny-bundle','survos/simple-datatables-bundle'],
            dev: false,
        ),
        new Bullet(
            msg: [
                'Composer deps inside bunny-demo',
                'You can skip the optional helper if you prefer'
            ]
        ),
    ]
)]
function install(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * 2) Configure API key (env)
 *
 * You can either:
 *  - run `bin/console bunny:config <api-key> >> .env.local`, or
 *  - write BUNNY_API_KEY to .env.local and run `bin/console bunny:config` (it will read from env).
 */
#[AsTask(name: 'config', description: 'Capture API key and dump Bunny config')]
#[Step(
    title: 'Configure Bunny API key',
    description: 'Write the main API key to .env.local and dump zone config.',
    actions: [
        // Write a placeholder key; edit .env.local later to set the real one.
        // @todo: prompt
        new Env('BUNNY_API_KEY', ['hidden' => true], '.env.local'),
        // Dump config (reads BUNNY_API_KEY from env/local)
        new Console('bunny:config',postfix: ' >> .env.local',  note: 'Append env var suggestions to .env.local'),
        new Bullet(msg:
            [
                'Get the main API key from https://dash.bunny.net/account/api-key',
                'Command can filter/limit zones in the future via --filter (TODO)',
                'Generated YAML references env vars; edit .env.local to set final values'
            ]
        )
    ]
)]
function config(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * 3) Optional: write example bundle config (if you want a starting file immediately)
 */
#[AsTask(name: '3-write-config', description: 'Write an example survos_bunny.yaml referencing env')]
#[Step(
    title: 'Write example config (optional)',
    description: 'Creates config/packages/survos_bunny.yaml pointing to env vars.',
    actions: [
        new YamlWrite(
            path: 'config/packages/survos_bunny.yaml',
            content: <<<YAML
survos_bunny:
  # Main (read-only) API key, typically used for listing zones etc.
  api_key: '%env(BUNNY_API_KEY)%'

  # Storage zones can be configured here or loaded via the bunny:config command.
  # Example of one zone (fill from .env.local and Bunny dashboard):
  zones:
    example_zone:
      id: '%env(int:BUNNY_EXAMPLE_ZONE_ID)%'
      name: '%env(BUNNY_EXAMPLE_ZONE_NAME)%'
      storage:
        hostname: '%env(BUNNY_EXAMPLE_STORAGE_HOST)%'
        username: '%env(BUNNY_EXAMPLE_STORAGE_USER)%'
        password: '%env(BUNNY_EXAMPLE_STORAGE_PASS)%'
YAML,
            note: 'Example bundle config referencing env vars'
        ),
    ]
)]
function step3_write_config(): void
{
    RunStep::run();
}

/**
 * 4) Verify CLI access
 */
#[AsTask(name: '4-cli', description: 'Run bunny:list to verify access')]
#[Step(
    title: 'Verify CLI',
    description: 'List bunny resources via CLI to verify the API key and config.',
    actions: [
        new Console('bunny:list', cwd: 'bunny-demo'),
    ]
)]
function step4_cli(): void
{
    RunStep::run();
}

/**
 * 5) Start server & open admin
 */
#[AsTask(name: 'browse', description: 'Start Symfony server and open Bunny admin')]
#[Step(
    title: 'Browse admin',
    description: 'Start local server and open the Bunny admin routes.',
    bullets: [
        'You can secure /bunny routes in security.yaml as needed',
        'Admin route may vary by version; adjust below if necessary'
    ],
    actions: [
//        new Bash('symfony server:start -d', cwd: 'bunny-demo', note: 'Start local server in background'),
        new OpenUrl('/bunny/zones', note: 'Open zones browser'),
    ]
)]
function browse(): void { RunStep::run(_actions_from_current_task(), context()); }
