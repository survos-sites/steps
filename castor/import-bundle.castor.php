<?php

#[AsTask('packages:required', CASTOR_NAMESPACE, 'Install required packages')]
function required_packages(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('packages:required', CASTOR_NAMESPACE, 'Install required packages')]
#[Step(
    title: 'with-symfony',
    description: 'Symfony bundles manage the dependencies',
    actions: [
        new ComposerRequire([
            'survos/meili-bundle', 'meilisearch/meilisearch-php:dev-main']),
        new Bullet(style: 'callout',
            msg: "Dependencies are automatically installed"),
        new SplitSlide(),
        new ComposerRequire([
            'meilisearch/meilisearch-php:dev-main',
            'symfony/http-client',
            'nyholm/psr7',
            'survos/jsonl-bundle',
        ],
            run: false
        )
    ]
)]
function with_symfony(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('bundles:basic', CASTOR_NAMESPACE, 'bundles for the demo')]
#[Step(
    'Suggested Bundles',
    actions: [
        new ComposerRequire([
            'league/csv',
            'survos/import-bundle',
            'openai-php/client',
            'easycorp/easyadmin-bundle',
            'survos/ez-bundle',
            'symfony/ux-icons',
        ],
        ),
        new Bullet(msg: [
            'Tools to analyze csv/json data',
            'Tools to import to database',
            'Tools are paired to work together for fast demos',
            'OpenAI for semantic search and (optionally) code generation',
            'Leverages easy-admin for bundle administration',
        ]),
        new SplitSlide(),
        new ComposerRequire(
            description: 'Code generator (like Symfony maker-bundle)',
            packages: ['survos/code-bundle'], dev: true),
        new Bash('../../mono/link .', display: false),
        new Bash("sed -i \"s|# sync: 'sync://'|sync: 'sync://'|\" config/packages/messenger.yaml"),//        new Console('ux:icons:lock'),

    ]
)]
function install(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('bundles:importmap', CASTOR_NAMESPACE, 'front-end assets')]
#[Step(
    'Install Frontend using AssetMapper',
    bullets: [
        'css for angolia',
    ],
    actions: [
        new ImportmapRequire(['@tabler/core', 'bootstrap', '@meilisearch/instant-meilisearch/templates/basic_search.css']),
        new Bash('echo "import \'instantsearch.css/themes/algolia.min.css\';
import \'@meilisearch/instant-meilisearch/templates/basic_search.css\';
import \'@tabler/core/dist/css/tabler.min.css\';
import \'@tabler/core\';
" | cat - assets/app.js > temp && mv temp assets/app.js',
            display: false
        ),
        new DisplayCode(lang: 'js', content: <<<JS
// assets/app.js
import './bootstrap.js';
import './styles/app.css';

import '@tabler/core';
import '@tabler/core/dist/css/tabler.min.css';

import 'instantsearch.css/themes/algolia.min.css';
JS
        )
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
        new Bullet(msg: 'Visit https://platform.openai.com/api-keys'),
        new BrowserVisit('https://platform.openai.com/api-keys'),
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
        'fetch and uncompress movies.csv.gz',
        'in practice: bash script or Symfony command or castor task'
    ],
    actions: [
        new Bash(
            display: false,
            commands: [
                'mkdir -p data',
                '[ -f data/movies.csv.gz ] || curl -L -o data/movies.csv.gz https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz',
                '[ -f data/movies.csv ] || gunzip data/movies.csv.gz -k'
            ]),
        new Bash(
            description: 'Download movie data for demo',
            run: false,
            commands: [
                'mkdir -p data',
                'curl -L -o data/movies.csv.gz https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz',
                'gunzip data/movies.csv.gz -k'
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
        'bin/console make:entity Movie',
        'worked fine for years',
    ],
    actions: [
        new DisplayCode(lang: 'bash', content: <<<JS
bin/console make:entity Movie
 created: src/Entity/Movie.php
 created: src/Repository/MovieRepository.php

 Entity generated! Now let's add some fields!
 You can always add more fields later manually or by re-running this command.

 New property name (press <return> to stop adding fields):
 > title

 Field type (enter ? to see all types) [string]:
 >

 Field length [255]:
 >

 Can this field be null in the database (nullable) (yes/no) [no]:
 > no

 updated: src/Entity/Movie.php

 Add another property? Enter the property name (or press <return> to stop adding fields):
 >

JS
        )
    ]
)]
#[Step(
    bullets: [
        'automated version of bin/console make:entity Movie',
        'json:convert reads CSV/JSON, writes jsonl and field analysis',
        'bonus: light cleanup of data with event listeners',
        'then generate a Doctrine entity from the analysis',
    ],
    actions: [
        new Console('import:convert', ['data/movies.csv', '--dataset', 'movie'], a: 'stats.terminal'),
        new Console('code:entity', ['data/movie.profile.json', 'Movie', '--force'], a: 'src/Entity/Movie.php'),
        new Console('code:template', ['movie', '--twig'], a: 'templates/js/movie.html.twig'),
//        new Console('doctrine:schema:update', ['--force']),
        // creates the artifact, but doesn't display it.  Internal
        new Artifact('src/Entity/Movie.php', "Movie.php"),
        new Artifact('templates/js/movie.html.twig', "movie.html.twig"),
        // we could also display the artifact!  For testing, let's make sure they both work.
//        new DisplayCode('/src/Entity/Movie.php', lang: 'php'),
    ]
)]
#[Step("Movie Class",
    actions: [
        new DisplayArtifact('src/Entity/Movie.php'),
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
        new Console('doctrine:schema:update', ['--force'], note: 'Sqlite: create and update'),
        new Console('meili:settings:update', ['--force'], note: 'meili: create and update'),
    ]
)]
function ea_create_database(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'load:database')]
#[Step(
    'Load the database (prototype)',
    actions: [
        new DisplayCode(
            note: "boilerplace code for importing data from csv",
            lang: 'PHP', content: <<<'PHP'
$reader = new CsvReader('data/movies.csv');
foreach ($reader as $idx => $data) {
        if (!$product = $this->movieRepository->find($data->id)) {
            $movie = new Movie($data->id);
            $this->entityManager->persist($movie);
        }
        $movie->title = $data->title;
        $movie->budget = $data->budget;
        // etc.
}
$this->entityManager->flush();
PHP
        ),
        new Bullet([
                'Needs to flush periodically',
                'we may want --limit during testing',
            ]
        ),
    ]
)]
#[Step('bulk import',
    actions: [
        new Console('import:entities', ['Movie', 'data/movie.jsonl', '--limit', '500'], a: 'import.txt'),
        new Bullet([
                'import:entities is a simple way to get flat(easy) data to doctrine',
                'same internal logic that creates the Entity class',
                'tweak the data via Property Hooks/Listeners',
                'doctrine post-flush listener populates the meili index',
            ]
        ),
        new DisplayArtifact('import.txt'),
    ]
)]
function ea_load_database(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('show_data', CASTOR_NAMESPACE, 'We have data!')]
#[Step(
    description: "Ways to see/search our index",
    bullets: [
        'http://127.0.0.1:7700 is included in meilisearch',
        'but not very powerful',
        'riccox is better'
    ],
    actions: [
        new BrowserVisit('/', 'http://127.0.0.1:7700', a: 'meili.png'),
        new BrowserVisit('/ins/0/index/movie/documents?listType=grid', 'http://127.0.0.1:24900', a: 'riccox.png'),
//        new BrowserVisit('/', 'http://127.0.0.1:24900', a: 'riccox.png'),
        new DisplayArtifact('riccox.png'),
    ],
)]
function ea_show_data(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('dashboard', CASTOR_NAMESPACE, 'Generate Dashboard + CRUD')]
#[Step(
    bullets: [
        'use EasyAdmin to browse the doctrine database',
        'code:meili:admin -- Creates Dashboard controllers + crud',
        'Demo uses easy-admin as home page!',
        'Scans registry for entities and generates <Entity>CrudController.php',
        'Integrates with MeiliService settings dynamically'
    ],
    actions: [
        new Console('code:meili:admin', ['--path', '/']),
        new Console('cache:clear'),
        new Console('cache:pool:clear', ['cache.app']),
    ]
)]
#[Step('Open the EZ Meili Dashboard',
    actions: [
        new BrowserVisit('/', 'https://meili-demo.wip', a: 'default-home.png'),
        new DisplayArtifact('default-home.png', note: 'Default ez homepage'),
    ]
)]
function ea_dashboard(): void
{
    RunStep::run(_actions_from_current_task(), context());
}


//#[AsTask('start', CASTOR_NAMESPACE, 'start server and open browser')]
//#[Step(
//    'Open Dashboard in browser',
//    actions: [
//        new Bash('symfony server:start -d'),
//    ]
//)]
//function ea_open(): void
//{
//    RunStep::run(_actions_from_current_task(), context());
//}

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
function facets(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

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
function update_index(): void
{
    RunStep::run(_actions_from_current_task(), context());
}