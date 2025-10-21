<?php
// app/all.castor.php

use Castor\Attribute\AsTask;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\Actions\{
    Section,
    Bash,
    Console,
    Composer,
    ComposerRequire,
    ImportmapRequire,
    RequirePackage,
    Env,
    YamlWrite,
    FileWrite,
    FileCopy,
    OpenUrl,
    DisplayCode,
    BrowserVisit,
    BrowserClick,
    BrowserAssert,
    ShowClass
};

#[AsTask('0-install')]
#[Step(
    title: 'Install PHP components',
    description: 'Add Step bundle + EasyAdmin (Composer) and Tabler UI (importmap).',
//    bullets: ['Composer deps', 'Front-end deps via importmap'],
    actions: [
        new ComposerRequire(
            requires: [
                new RequirePackage('survos/step-bundle', 'Step authoring bundle'),
                new RequirePackage('easycorp/easyadmin-bundle', 'Admin UI'),
            ],
            dev: false,
        ),
        new ComposerRequire(
            requires: [
                new RequirePackage('survos/code-bundle', 'alternative maker-bundle'),
            ],
            dev: true,
        ),
    ]
)]
function a_install(): void { RunStep::run(); }

#[AsTask('0b-front-end')]
#[Step(
    title: 'Install front-end assets (AssetMapper)',
    description: 'No build step!  Thanks, Ryan',
//    bullets: ['Composer deps', 'Front-end deps via importmap'],
    actions: [
        new ImportmapRequire(
            requires: [
                new RequirePackage('@tabler/core', 'Tabler UI'),
            ],
        ),
    ]
)]
function a_installAssets(): void { RunStep::run(); }

#[AsTask('1-config')]
#[Step(
    title: 'Project configuration',
    description: 'Set environment and write Meilisearch config (consolidated).',
    actions: [
        new Env('APP_ENV', 'dev', file: '.env.local', cwd: 'demo'),
        new Env('MEILISEARCH_URL', 'http://127.0.0.1:7700', file: '.env.local', cwd: 'demo'),
        new Env('MEILISEARCH_API_KEY', 'masterKey', file: '.env.local', cwd: 'demo'),
        new YamlWrite(
            path: 'config/packages/meilisearch.yaml',
            content: <<<YAML
meilisearch:
  url: '%env(MEILISEARCH_URL)%'
  api_key: '%env(MEILISEARCH_API_KEY)%'
  prefix: 'demo_'
YAML,
            cwd: 'demo',
            note: 'Meilisearch configuration'
        ),
    ]
)]
function b_config(): void { RunStep::run(); }

#[AsTask('2-scaffold')]
#[Step(
    title: 'Scaffold code',
    description: 'Generate controller and verify basic route (route may not exist before this step).',
    bullets: ['MakerBundle scaffolding', 'Open UI'],
    actions: [
        new Console('make:controller App -n', cwd: 'demo', note: 'Generate AppController'),
        new OpenUrl('/app', note: 'Open by path'),
        new OpenUrl('app_app', note: 'Open by route name (may be missing before scaffold)'),
    ]
)]
function c_scaffold(): void { RunStep::run(); }

#[AsTask('3-files')]
#[Step(
    title: 'Write & copy files',
    description: 'Demonstrate file writes and copies.',
    actions: [
        new FileWrite('README.demo.md', "# Demo\n\nThis is a generated file.\n", cwd: 'demo'),
        new FileCopy('README.demo.md', 'var/README.demo.copy.md', cwd: 'demo', note: 'Copy into var/'),
    ]
)]
function d_files(): void { RunStep::run(); }

#[AsTask('4-commands')]
#[Step(
    title: 'Run commands',
    description: 'Show bash and composer single-command rendering.',
    actions: [
        new Bash('symfony server:stop || true', cwd: 'demo', note: 'Ensure clean slate'),
        new Bash('symfony server:start -d', cwd: 'demo', note: 'Start server'),
        new Composer('require nyholm/psr7:^1.0', cwd: 'demo', note: 'Composer single command')
    ]
)]
function e_commands(): void { RunStep::run(); }

#[AsTask('5-display')]
#[Step(
    title: 'Display a code snippet',
    description: 'Reference a snippet via markers (executor extracts later).',
    actions: [
        new DisplayCode(
            target: 'src/Controller/AppController.php',
            markerStart: 'demo:start',
            markerEnd: 'demo:end',
            lang: 'php',
            cwd: 'demo',
            note: 'Show only the interesting lines'
        ),
    ]
)]
function f_display(): void { RunStep::run(); }

#[AsTask('6-show-class')]
#[Step(
    title: 'Show class declarations and method signatures',
    description: 'Reflect a class to show its declaration and selected methods.',
    actions: [
        new ShowClass(\App\Controller\AppController::class, declarations: true, methods: ['__construct', 'index'])
    ]
)]
function g_show_class(): void { RunStep::run(); }

#[AsTask('7-browser')]
#[Step(
    title: 'Browser-style actions',
    description: 'End-user flow rendered as prose lines.',
    actions: [
        new BrowserVisit('/app'),
        new BrowserClick('New Task'),
        new BrowserAssert('#title', contains: 'Hello'),
    ]
)]
function h_browser(): void { RunStep::run(); }
