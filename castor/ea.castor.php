<?php declare(strict_types=1);

const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?


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


//#[AsTask(name: 'ea:demo', description: 'EasyAdmin 20-minute Symfony demo')]
//#[Step(
//    'EasyAdmin Demo',
//    description: 'Create a Slideshow for easyadmin',
//    bullets: [
//        'We are in ' . __METHOD__,
//        'Create demo project',
//        'Install EasyAdmin',
//        'Generate Task entity + dashboard',
//        'Configure YAML files'
//    ]
//)]
//function ea_demo(): void
//{
//    ea_new();
//    ea_install();
//    ea_make_entity();
//    ea_configure();
//    ea_dashboard();
//    ea_open();
//    io()->success('EasyAdmin demo completed.');
//}

#[AsTask('create', namespace: 'ea', description: 'test artifact')]
#[Step(
    actions: [
        new Bash('ls -lh', a: 'easy-artifacts/ls.terminal'),
        new Bash('tree --gitignore', a: 'easy-artifacts/tree.terminal'),
        new DisplayArtifact('easy-artifacts/tree.terminal'),
    ]
)]
function ea_artifact(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('display', namespace: 'ea', description: 'test artifact')]
#[Step(
    actions: [
        new DisplayArtifact('easy-artifacts/ls.terminal'),
    ]
)]
function display_artifact(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('ea:new', 'Create Symfony project using the Symfony CLI')]
#[Step(
    bullets: [
        '--webapp install common packages (sy:new!), this is just for the slide'
    ],
)]
function ea_new(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'install')]
#[Step(
    'Install EasyAdmin bundle',
    actions: [ new ComposerRequire([
        'survos/meili-bundle',
        'survos/import-bundle',
        'survos/ez-bundle',
        'symfony/ux-icons',
        'league/csv',
        'easycorp/easyadmin-bundle',
    ]) ]
)]
function install(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:install-dev')]
#[Step(
    'Install dev bundle',
    actions: [ new ComposerRequire(
        [
        'survos/code-bundle'
    ], dev: true) ]
)]
function ea_install_dev(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:download')]
#[Step(
    'Download movie data',
    actions: [
        new Bash('mkdir -p data'),
        new Bash('wget -O data/movies.csv.gz https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz'),
        new Bash('gunzip data/movies.csv.gz'),
        new Bash('head -n 3 data/movies.csv', a: 'download/3movies.csv'),
        new DisplayArtifact('download/3movies.csv'),
    ]
)]
function ea_download(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:make-entity')]
#[Step(
    'Generate Movie entity',
    actions: [
        new Console('code:entity', ['Movie', '--meili', '--file',  'data/movies.csv']),
        new DisplayCode('/src/Entity/Movie.php', lang: 'php'),
        new Artifact('src/Entity/Movie.php', "Movie.php")
    ]
)]
//#[Step(
//    'migrate',
//    actions: [
//        new Console('make:migration'),
//        new Console('doctrine:migrations:migrate'),
//        new CopyFile(INPUT_DIR . '/src/Entity/Task.php', 'src/Entity/Task.php'),
//    ]
//)]
function ea_make_entity(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:configure')]
#[Step(
    'Configure EasyAdmin & Meili',
    actions: [
        new Env('DATABASE_URL', [
            'default' => "sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
        ],'.env.local'),
        new Env('OPEN_AI_KEY', ['hidden' => true], '.env.local'),
//        new CopyFile(INPUT_DIR . '/config/packages/easy_admin.yaml', 'config/packages/easy_admin.yaml'),
//        new CopyFile(INPUT_DIR . '/config/packages/survos_meili.yaml', 'config/packages/survos_meili.yaml'),
        new DisplayCode('config/packages/easy_admin.yaml', lang: 'yaml'),
        new DisplayCode('config/packages/survos_meili.yaml', lang: 'yaml'),
        new DisplayCode('.env.local', lang: 'env'),
    ]
)]
function ea_configure(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:create:database')]
#[Step(
    'Create the database',
    bullets: [
        'during dev and the demo, sqlite is easy to use'
    ],
    actions: [
        new Console('doctrine:schema:update', ['--force' ]),
    ]
)]
function ea_create_database(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:load:database')]
#[Step(
    'Load the database',
    bullets: [
        'import:entities is a simple way to get flat(easy) data from csv->doctrine'
    ],
    actions: [
        new Console('import:entities', ['Movie', '--file', 'data/movies.csv' ]),
    ]
)]
function ea_load_database(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:dashboard')]
#[Step(
    'Generate Dashboard + CRUD',
    actions: [
        new Console('code:meili:admin'),
        new Console('cache:clear'),
//        new Console('make:admin:dashboard'),
//        new Console('make:admin:crud', ['Movie']),
    ]
)]
function ea_dashboard(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'ea:open')]
#[Step(
    'Open Dashboard in browser',
    actions: [
        new Bash('symfony server:start -d'),
        new Bash('symfony open:local --path=/admin'),
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
