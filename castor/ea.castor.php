<?php declare(strict_types=1);

const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?
const CASTOR_NAMESPACE = null;

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



#[AsTask('artifact:create', CASTOR_NAMESPACE, 'create ls and tree artifacts, display tree')]
#[Step(
    bullets: [
        'list',
        'display'
    ],
    actions: [
        new Bash('ls -lh', a: 'easy-artifacts/ls.terminal'),
        new Bash('tree --gitignore', a: 'easy-artifacts/tree.terminal'),
        new DisplayArtifact('easy-artifacts/tree.terminal'),
    ]
)]
function ea_artifact(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('artifact:display', CASTOR_NAMESPACE,  'display ls.terminal artifact')]
#[Step(
    actions: [
        new DisplayArtifact('easy-artifacts/ls.terminal'),
    ]
)]
function display_artifact(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('ea:new', null, 'Create Symfony project using the Symfony CLI')]
#[Step(
    bullets: [
        '--webapp install common packages (sy:new!), this is just for the slide',
        'run castor sy:new to execute this commmand!'
    ],
)]
function ea_new(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('install-meili', null, 'Install meilisearch')]
#[Step(
    actions: [
        new Bash('docker run -it --rm  -p 7700:7700 \
  -v $(pwd)/meili_data:/meili_data \
  getmeili/meilisearch:v1.16'),
    ],
    bullets: [
        'run with docker',
        'install via docker-composer.yml',
        'free trial on meilisearch'
    ],
)]
function install_meili(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('packages:required', CASTOR_NAMESPACE, 'Install required packages')]
#[Step(
    bullets: [
        'without Symfony, install meilisearch PHP SDK',
        'low-level calls',
    ],
    actions: [ new ComposerRequire([
        'meilisearch/meilisearch-php',
        'symfony/http-client',
        'nyholm/psr7',
    ]) ]
)]
function required_packages(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('packages:required', CASTOR_NAMESPACE, 'Install required packages')]
#[Step(
    title: 'with-symfony',
    description: 'Symfony bundles manage the dependencies',
    bullets: [
        'meilisearch/search-bundle: official bundle from meilisearch',
        'https://github.com/Mezcalito/ux-search, YAML, readonly',
        'survos/meili-bundle: PHP 8.4, attribute-based'
    ],
    actions: [ new ComposerRequire([
        'survos/meili-bundle'
    ])
        ]
)]
function with_symfony(): void { RunStep::run(_actions_from_current_task(), context()); }
#[AsTask('bundles:basic', CASTOR_NAMESPACE, 'bundles for the demo')]
#[Step(
    'Install demo bundles',
    bullets: [
        'Tools Import the CSV data to the database',
        'Code generator (like Symfony maker-bundle',
    ],
    actions: [
        new ComposerRequire([
        'survos/import-bundle',
        'survos/ez-bundle',
        'easycorp/easyadmin-bundle',
        'league/csv',
        'symfony/ux-icons',
    ],
    ),
        new ComposerRequire(['survos/code-bundle'], dev: true)

    ]
)]
function install(): void { RunStep::run(_actions_from_current_task(), context()); }

//#[AsTask('bundles:dev', CASTOR_NAMESPACE, 'Install dev bundles')]
//#[Step(
//    actions: [
//        new ComposerRequire(['survos/code-bundle'], dev: true)
//    ]
//)]
//function ea_install_dev(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('configure', CASTOR_NAMESPACE, 'Configure EasyAdmin & Meili')]
#[Step(
    'Setup Env vars (after bundle install)',
    bullets: [
        "Use sqlite during early dev",
        "swtich to postgres + migrations later"
    ],
    actions: [
        new Env('DATABASE_URL', [
            'default' => "sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
        ],'.env.local'),
        new Console('doctrine:schema:update', ['--force']),
        new Env('OPEN_AI_KEY', ['hidden' => true], '.env.local'),
//        new CopyFile(INPUT_DIR . '/config/packages/easy_admin.yaml', 'config/packages/easy_admin.yaml'),
//        new CopyFile(INPUT_DIR . '/config/packages/survos_meili.yaml', 'config/packages/survos_meili.yaml'),
    ]
)]
function ea_configure(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('download', null, 'Download movie data')]
#[Step(
    'Download movie data',
    bullets: [
        'fetch and uncompress via bash',
        'or write a Symfony command or castor task'
    ],
    actions: [
        new Bash(commands: [
            'mkdir -p data',
            'wget -O data/movies.csv.gz https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz',
            'gunzip data/movies.csv.gz'
        ]),
        new Bash('head -n 3 data/movies.csv', a: 'download/3movies.csv'),
        new DisplayArtifact('download/3movies.csv'),
    ]
)]
function ea_download(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('make-entity', CASTOR_NAMESPACE, 'Generate Movie entity from CSV')]
#[Step(
    bullets: [
        'given a CSV file, generate a Doctrine entity',
        'duck-typing, pretty basic (but good for demos!)'
    ],
    actions: [
        new Console('code:entity', ['Movie', '--meili', '--file',  'data/movies.csv'], a: 'src/Entity/Movie.php'),
        // creates the artifact, but doesn't display it.  Internal
        new Artifact('src/Entity/Movie.php', "Movie.php"),

        new DisplayArtifact('src/Entity/Movie.php'),
        // we could also display the artifact!  For testing, let's make sure they both work.
        new DisplayCode('/src/Entity/Movie.php', lang: 'php'),
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


#[AsTask(name: 'ea:create:database')]
#[Step(
    'Create/update the database',
    bullets: [
        'during dev and the demo, sqlite is easy to use'
    ],
    actions: [
        new Console('doctrine:schema:update', ['--force' ]),
        new Console('meili:schema:update', ['--force' ]),
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
        new Console('import:entities', ['Movie', '--file', 'data/movies.csv' ], a: 'import.txt'),
        new DisplayArtifact('import.txt'),
    ]
)]
function ea_load_database(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('dashboard', CASTOR_NAMESPACE, 'Generate Dashboard + CRUD')]
#[Step(
    bullets: [
        'use EasyAdmin to browse the entity',
        'use bash, 7700 or riccox to see meili',
        'Creates src/Controller/Admin/MeiliDashboardController.php',
        'Scans registry for entities and generates <Entity>CrudController.php',
        'Integrates with MeiliService settings dynamically'
    ],
    actions: [
        new Console('code:meili:admin'),
        new Console('cache:clear'),
        new Console('cache:pool:clear', ['cache.app']),
        new Bash('symfony open:local --path=/ez-meili'),
        new BrowserVisit('/ez-meili', host: 'http://ea.wip', a: 'dashboard.png'),

    ]
)]
#[Step('Visit a web page',
    actions: [
        new BrowserVisit('/', 'https://ea.wip', a: 'default-home.png'),
        new DisplayArtifact('default-home.png', note: 'Default ez homepage'),
    ]
)]

function ea_dashboard(): void { RunStep::run(_actions_from_current_task(), context()); }


#[AsTask('open', CASTOR_NAMESPACE, 'start server an open browser')]
#[Step(
    'Open Dashboard in browser',
    actions: [
        new Bash('symfony server:start -d'),
    ]
)]
function ea_open(): void { RunStep::run(_actions_from_current_task(), context()); }


// -----------------------------------------------------------------------------
// 7) Generate MeiliDashboard + CRUD (code:meili:admin)
// -----------------------------------------------------------------------------

#[AsTask(name: 'ea:demo', description: 'EasyAdmin 20-minute Symfony demo')]
#[Step(
    'EasyAdmin Demo',
    description: 'Create a Slideshow for easyadmin',
    bullets: [
        'We are in ' . __METHOD__,
        'Create demo project',
        'Install EasyAdmin',
        'Generate Task entity + dashboard',
        'Configure YAML files'
    ]
)]
function ea_demo(): void
{
//    ea_new();
//    required_packages();
    with_symfony();
    install();
    ea_download();
    ea_make_entity();
    ea_configure();
    ea_create_database();
    ea_load_database();
    ea_dashboard();
    ea_open();
    io()->success('EasyAdmin demo completed.');
}

