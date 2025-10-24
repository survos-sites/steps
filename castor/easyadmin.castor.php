<?php declare(strict_types=1);

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
use Survos\StepBundle\Step\RunStep;
use Survos\StepBundle\Action\{
    ComposerRequire,
    Console,
    Bash,
    CopyFile,
    DisplayCode,
    BrowserVisit
};

const INPUT_DIR = __DIR__ . '/../inputs';
const DEMO_DIR  = '../demos/easyadmin';

#[AsContext(default: true, name: 'sf')]
function ctx_ea(): Context { return new Context(workingDirectory: DEMO_DIR); }

#[AsTask(name: 'ea:demo', description: 'EasyAdmin 20-minute Symfony demo')]
#[Step(
    'EasyAdmin Demo',
    description: 'Create a Symfony app with EasyAdmin',
    bullets: [
        'Create project',
        'Install EasyAdmin',
        'Generate Task entity + dashboard',
        'Configure YAML files'
    ]
)]
function ea_demo(): void
{
    ea_new();
    ea_install();
    ea_make_entity();
    ea_configure();
    ea_dashboard();
    ea_open();
    io()->success('EasyAdmin demo completed.');
}

#[AsTask(name: 'ea:new')]
#[Step(
    'Create Symfony project',
    actions: [ new Bash('symfony new --webapp --dir=.') ]
)]
function ea_new(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:install')]
#[Step(
    'Install EasyAdmin bundle',
    actions: [ new ComposerRequire(['easycorp/easyadmin-bundle']) ]
)]
function ea_install(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:make-entity')]
#[Step(
    'Generate Task entity + migrate',
    actions: [
//        new Console('make:entity', ['Task']),
//        new Console('make:migration'),
//        new Console('doctrine:migrations:migrate'),
        new CopyFile(INPUT_DIR . '/src/Entity/Task.php', 'src/Entity/Task.php'),
        new DisplayCode('src/Entity/Task.php', lang: 'php')
    ]
)]
function ea_make_entity(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:configure')]
#[Step(
    'Configure EasyAdmin & Meili',
    actions: [
        new CopyFile(INPUT_DIR . '/config/packages/easy_admin.yaml', 'config/packages/easy_admin.yaml'),
        new CopyFile(INPUT_DIR . '/config/packages/meili.yaml', 'config/packages/meili.yaml'),
        new DisplayCode('config/packages/easy_admin.yaml', lang: 'yaml'),
        new DisplayCode('config/packages/meili.yaml', lang: 'yaml')
    ]
)]
function ea_configure(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:dashboard')]
#[Step(
    'Generate Dashboard + CRUD',
    actions: [
        new CopyFile(INPUT_DIR . '/src/Controller/Admin/DashboardController.php', 'src/Controller/Admin/DashboardController.php'),
        new DisplayCode('src/Controller/Admin/DashboardController.php', lang: 'php'),
        new Console('make:admin:crud', ['Task'])
    ]
)]
function ea_dashboard(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:open')]
#[Step(
    'Open Dashboard in browser',
    actions: [
        new Bash('symfony server:start -d'),
        new BrowserVisit('/admin', host: 'http://127.0.0.1:8000')
    ]
)]
function ea_open(): void { RunStep::run(_actions_from_current_task(), context()); }

// -----------------------------------------------------------------------------
// 6) Generate Entity from CSV (slide only — uses your generator tool)
// -----------------------------------------------------------------------------
#[AsTask(name: 'ea:entity-from-csv', description: 'Demonstrate CSV-to-Entity generator')]
#[Step(
    'Generate entity from CSV file',
    description: 'Use the Survos code generator to inspect a CSV and create an Entity.',
    bullets: [
        'Reads CSV header → determines field types',
        'Creates Doctrine entity + repository',
        'Demonstrates how automation accelerates scaffolding'
    ],
    actions: [
        new Console('code:entity', ['Movie', '--file=data/movies.csv']),
        new DisplayCode('src/Entity/Movie.php', lang: 'php', note: 'Generated from CSV')
    ]
)]
function ea_entity_from_csv(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 7) Generate MeiliDashboard + CRUD (code:meili:admin)
// -----------------------------------------------------------------------------
#[AsTask(name: 'ea:meili-admin', description: 'Generate MeiliDashboardController & CRUD controllers')]
#[Step(
    'Generate MeiliDashboardController',
    description: 'Scans MeiliService registry and generates dashboard + CRUDs.',
    bullets: [
        'Uses nikic/php-generator to write PHP source files',
        'Creates src/Controller/Admin/MeiliDashboardController.php',
        'Scans registry for entities and generates <Entity>CrudController.php',
        'Integrates with MeiliService settings dynamically'
    ],
    actions: [
        new Console('code:meili:admin'),
        new DisplayCode('src/Controller/Admin/MeiliDashboardController.php', lang: 'php', note: 'Generated dashboard controller')
    ]
)]
function ea_meili_admin(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
