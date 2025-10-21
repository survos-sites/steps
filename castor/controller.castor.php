<?php
// File: castor/controller.castor.php

use Castor\Attribute\AsTask;
use function Survos\StepBundle\Runtime\run_step;

use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\Actions\{
    Console,
    OpenUrl,
    ComposerRequire,
    ImportmapRequire,
    RequirePackage,
    ShowClass
};

/**
 * 0) Install deps (Composer + Importmap)
 */
#[AsTask(name: '0-install', description: 'Install PHP and front-end dependencies')]
#[Step(
    title: 'Install dependencies',
    description: 'Add Step authoring + EasyAdmin (PHP) and Tabler (front-end).',
    bullets: [
        'composer packages (left-justified bash block)',
        'importmap packages'
    ],
    actions: [
        new ComposerRequire(
            requires: [
                new RequirePackage('survos/step-bundle', 'Step authoring bundle'),
                new RequirePackage('easycorp/easyadmin-bundle', 'Easy Administration, PHP based'),
            ],
            dev: false,
            cwd: 'demo',
            note: 'Install PHP packages'
        ),
        new ImportmapRequire(
            requires: [
                new RequirePackage('reveal.js', 'Presentation engine (ESM)'),
                new RequirePackage('@tabler/core', 'Tabler UI'),
            ],
            cwd: 'demo',
            note: 'Install front-end packages via importmap'
        ),
    ]
)]
function install_dependencies3(): void
{
    // Autoload is already handled by root castor.php; infer caller via backtrace
    run_step();
}

/**
 * 1) Make a Controller (App) non-interactively
 */
#[AsTask(name: '1-make-controller', description: 'Scaffold controller via MakerBundle')]
#[Step(
    title: 'Make a Controller',
    description: 'Use MakerBundle to scaffold AppController quickly.',
    actions: [
        new Console('make:controller App -n', cwd: 'demo', note: 'Generate AppController + template'),
    ]
)]
function make_controller5(): void
{
    run_step();
}

/**
 * 2) Show the generated AppController (reflection-based)
 * - If the class does not exist yet, ShowClass produces a friendly warning.
 * - Slideshow renders as code; CLI echoes file contents.
 */
#[AsTask(name: '2-show-controller', description: 'Show the generated AppController source')]
#[Step(
    title: 'Show AppController',
    description: 'Display the generated controller code via reflection.',
    actions: [
        new ShowClass(\App\Controller\AppController::class),
    ]
)]
function show_controller(): void
{
    run_step();
}

/**
 * 3) Verify UI (open by path and route)
 */
#[AsTask(name: '3-verify-ui', description: 'Open the App page after generation')]
#[Step(
    title: 'Verify the UI',
    description: 'Open the generated page by path or route name.',
    bullets: [
        'Open by absolute path or by route name'
    ],
    actions: [
        new OpenUrl('/app', note: 'Open the generated page by path'),
        new OpenUrl('app_app', note: 'Open by route name'),
    ]
)]
function verify_ui9(): void
{
    run_step();
}
