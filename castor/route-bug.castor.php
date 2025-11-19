<?php declare(strict_types=1);

const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?
const CASTOR_NAMESPACE = null;
const EA_DEMO_DIR = '../demo/route-bug-demo';

/**
 * file: castor/easyadmin.castor.php
 * Demo orchestration for "EasyAdmin in 20 Minutes"
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

use Castor\Attribute\{AsTask, AsContext};
use Castor\Context;
use function Castor\{context, io};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Action as A;
use Survos\StepBundle\Action\{
    Artifact, // writer, when adding an artifactId to bash or console isn't enough
    DisplayArtifact, // reader
    ComposerRequire,
    Env,
    Console,
    Bash,
    Bullet,
    PregReplace,
    ImportmapRequire,
    CopyFile,
    DisplayCode,
    BrowserVisit
};

#[AsTask(name: 'build', description: "Let's build a demo!")]
#[Step(
    'Meilisearch Demo',
    description: 'Every step to build a movie search site',
    bullets: [
        'Create demo project',
        'Install bundles',
        'Download movie data',
        'Generate Movie entity from downloaded data',
        'configure and create sqlite database',
        'create meilisearch movie index',
        'Import movie data to database and index'
    ],
)]
#[Step(
    description: 'create a new webapp project',
    // we are NOT actually running the actions, they are here for the slide
    actions: [
        new Bash('symfony new --webapp meili-demo --dir' . EA_DEMO_DIR),
    ]
)]

function build(): void
{
//    ea_new();
//    required_packages();
    with_symfony();
    install();
    importmap();
    ea_open();
    ea_download();
    ea_make_entity();
    ea_configure();
    ea_create_database();
    ea_load_database();
    ea_dashboard();
    facets();

    ea_open();
    io()->success('EasyAdmin demo completed.');
}

#[AsTask('packages:required', CASTOR_NAMESPACE, 'Install required packages')]
#[Step(
    title: 'with-symfony',
    description: 'Symfony bundles manage the dependencies',
    bullets: [
        'survos/meili-bundle, from recipes-contrib'
    ],
    actions: [
        new ComposerRequire(['survos/meili-bundle']),
        new ComposerRequire(['survos/command-bundle'], dev: true),
        new Bash('cat config/routes/survos_meili.yaml'),
        new Bash('cat config/routes/survos_command.yaml'),
        new Bash('bin/console debug:route'),
    ]
)]

function with_symfony(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('bundles:basic', CASTOR_NAMESPACE, 'bundles for the demo')]
#[Step(
    'Necessary for demo only',
    bullets: [
        'Tools Import the CSV data to the database',
        'Code generator (like Symfony maker-bundle)',
        'Leverages easy-admin for bundle administration',
    ],
    actions: [
        new ComposerRequire([
            'survos/import-bundle',
            'survos/ez-bundle',
            'easycorp/easyadmin-bundle',
            'openai-php/client',
            'league/csv',
            'symfony/ux-icons',
        ],
        ),
        new ComposerRequire(['survos/code-bundle'], dev: true),
        new Bash('../../mono/link .'),
        new Console('ux:icons:lock'),
//        new CopyFile(INPUT_DIR . '/config/packages/ux_icons.yaml', 'config/packages/ux_icons.yaml'),

    ]
)]
function install(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('bundles:importmap', CASTOR_NAMESPACE, 'front-end assets')]
#[Step(
    'Install from jsdeliver',
    bullets: [
        'css for angolia',
    ],
    actions: [
        new ImportmapRequire(['@tabler/core', 'bootstrap', '@meilisearch/instant-meilisearch/templates/basic_search.css']),
        new Bash('echo "import \'instantsearch.css/themes/algolia.min.css\';
import \'@meilisearch/instant-meilisearch/templates/basic_search.css\';
import \'@tabler/core/dist/css/tabler.min.css\';
import \'@tabler/core\';
" | cat - assets/app.js > temp && mv temp assets/app.js'),

    ]
)]
function importmap(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

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
        "switch to postgres + migrations later"
    ],
    actions: [
        new Env('DATABASE_URL', [
            'default' => "sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
        ], '.env.local'),
//        new Console('doctrine:schema:update', ['--force']),
        new Env('OPENAI_API_KEY', ['hidden' => true], '.env.local'),
//        new CopyFile(INPUT_DIR . '/config/packages/easy_admin.yaml', 'config/packages/easy_admin.yaml'),
//        new CopyFile(INPUT_DIR . '/config/packages/survos_meili.yaml', 'config/packages/survos_meili.yaml'),
    ]
)]
function ea_configure(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('download', null, 'Download movie data')]
#[Step(
    'Download movie data for demo',
    bullets: [
        'if movies.csv.gz is missing, fetch it via curl',
        'if movies.csv is missing, uncompress it',
        'in practice,  write a Symfony command or castor task'
    ],
    actions: [
        new Bash(commands: [
            'mkdir -p data',
            '[ -f data/movies.csv.gz ] || curl -L -o data/movies.csv.gz https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz',
            '[ -f data/movies.csv ] || gunzip data/movies.csv.gz -k'
        ]),
        new Bash('head -n 3 data/movies.csv', a: 'download/3movies.csv'),
        new DisplayArtifact('download/3movies.csv'),
    ]
)]
function ea_download(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('make-entity', CASTOR_NAMESPACE, 'Generate Movie entity from CSV')]
#[Step(
    bullets: [
        'automated version of bin/console make:entity Movie',
        'given a CSV file, generate a Doctrine entity',
        'duck-typing, basic but great for demos'
    ],
    actions: [
        new Console('code:entity', ['Movie', '--meili', '--file', 'data/movies.csv'], a: 'src/Entity/Movie.php'),
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
function ea_make_entity(): void
{
    RunStep::run(_actions_from_current_task(), context());
}


#[AsTask(name: 'create:database')]
#[Step(
    'Create/update database and meili index',
    bullets: [
        'during dev and the demo, sqlite is easy to use',
        'create the meiliindex'
    ],
    actions: [
        new Console('doctrine:schema:update', ['--force']),
        new Console('meili:settings:update', ['--force']),
    ]
)]
function ea_create_database(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'load:database')]
#[Step(
    'Load the database',
    bullets: [
        'import:entities is a simple way to get flat(easy) data from csv->doctrine',
        'doctrine post-flush listener populates the meili index',
    ],
    actions: [
        new Console('import:entities', ['Movie', '--file', 'data/movies.csv', '--limit', '500'], a: 'import.txt'),
        new DisplayArtifact('import.txt'),
    ]
)]
function ea_load_database(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('show_data', CASTOR_NAMESPACE, 'We have a searchable index!')]
#[Step(
    description: "Ways to see/search our index",
    bullets: [
        'http://127.0.0.1:7700 is included in meilisearch',
        'but not very powerful',
        'riccox is better'
    ],
    actions: [
        new BrowserVisit('/ins/0/index/movie/documents?listType=grid', 'http://127.0.0.1:24900', a: 'riccox.png'),
//        new BrowserVisit('/', 'http://127.0.0.1:24900', a: 'riccox.png'),
        new DisplayArtifact('riccox.png'),
    ],
)]
function ea_show_data(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('dashboard', CASTOR_NAMESPACE, 'Generate Dashboard + CRUD')]
#[Step(
    bullets: [
        'use EasyAdmin to browse the doctrine database',
        'Creates src/Controller/Admin/MeiliDashboardController.php',
        'Demo uses easy-admin as home page!',
        'Scans registry for entities and generates <Entity>CrudController.php',
        'Integrates with MeiliService settings dynamically'
    ],
    actions: [
        new Console('code:meili:admin', ['--path','/']),
        new Console('cache:clear'),
        new Console('cache:pool:clear', ['cache.app']),
    ]
)]
#[Step('Visit a web page',
    actions: [
        new BrowserVisit('/', 'https://ea.wip', a: 'default-home.png'),
        new DisplayArtifact('default-home.png', note: 'Default ez homepage'),
    ]
)]
function ea_dashboard(): void
{
    RunStep::run(_actions_from_current_task(), context());
}


#[AsTask('start', CASTOR_NAMESPACE, 'start server an open browser')]
#[Step(
    'Open Dashboard in browser',
    actions: [
        new Bash('symfony server:start -d'),
    ]
)]
function ea_open(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('facets', CASTOR_NAMESPACE, 'add searchable, filterable and sortable to Movie index')]
#[Step(
    actions: [
        new PregReplace(
            pattern: '|#\[MeiliIndex\]|',
            replacement: "#[MeiliIndex(\n    filterable: ['genres','director','year'],\n    searchable: ['title','description']\n)]",
            file: 'src/Entity/Movie.php',
            a: '/src/Entity/MovieWithFacets.php'
        ),
        new DisplayArtifact('/src/Entity/MovieWithFacets.php'),
        new Console('meili:settings:update', ['--force']),


    ]
)]
function facets(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('update-index', CASTOR_NAMESPACE, 'update meili index settings')]
#[Step(
    actions: [
        new Console('meili:settings:update', ['--force', '--wait'], a: 'update-index.terminal'),
        new DisplayArtifact('update-index.terminal'),
    ]
)]
#[Step(
    actions: [
        new Console('code:js:twig', ['movie'], a: 'create-twig.terminal'),
        new DisplayArtifact('create-twig.terminal'),
    ]
)]
#[Step('Search and see facets',
    actions: [
        new BrowserVisit('/instant-search/index/movie', 'https://ea.wip', a: 'search-with-facets.png'),
//        new BrowserVisit('/meili/meiliAdmin/instant-search/index/movie', 'https://ea.wip', a: 'search-with-facets.png'),
        new DisplayArtifact('search-with-facets.png', note: 'Facets on side'),
    ]
)]
function update_index(): void { RunStep::run(_actions_from_current_task(), context()); }


