<?php declare(strict_types=1);

const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?
const CASTOR_NAMESPACE = null;
const EA_DEMO_DIR = '../demo/meili-demo';

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
    SplitSlide,
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

#[AsTask(name: 'agenda', description: "Presentation Agenda")]
#[Step(
    description: 'The next 30 minutes...',
    bullets: [
        'sidebar!'
    ],
    actions: [
        new Bullet(msg: [
            'What is meilisearch',
            "See Instasearch in action",
            'Low-level API',
            'PHP library API',
            'Bundle API/Attributes',
            "Let's build a demo",
        ]),
        new BrowserVisit(path: '/', host: 'https://meili.survos.com')
    ]
)]
function agenda(): void
{
}

#[AsTask('meili-overview', null,
//    'What is Meilisearch'
)]
#[Step(
    actions: [
        new Bullet(
            fade: true,
            size: 2,
            msg: [
                "An open-source, developer-friendly search engine focused on speed and relevance",
                "A lightweight alternative to Elasticsearch or Algolia with fast indexing",
                "Provides typo-tolerance, filters, facets, and semantic search out of the box",
//        "Designed for real-time search UIs with instant responses under 50ms",
//        "various Symfony bundles that encapsulate the API interactions",
//        "built-in vector store for semantic search",
            ]
        )
    ]
)]
//#[Step(
//    bullets: [
//        "An open-source, developer-friendly search engine focused on speed and relevance",
//        "A lightweight alternative to Elasticsearch or Algolia with fast indexing",
//        "Provides typo-tolerance, filters, facets, and semantic search out of the box",
//        "Designed for real-time search UIs with instant responses under 50ms",
//        "various Symfony bundles that encapsulate the API interactions",
//        "built-in vector store for semantic search",
//    ],
//)]
#[Step(
    title: 'How the API works',
    description: 'Background: Raw API calls',
    bullets: [
        "Straightfoward http calls",
        "define an index with an optional primary key (default: 'id')",
        "async -- a task is returned immediately",
    ],
    actions: [
        new Bash(
            note: 'Create the "movies" index with primary key "imdbId".',
            command: 'curl -s "http://127.0.0.1:7700/indexes" ' . "\\\n"
            . '-H "Content-Type: application/json" ' . "\\\n"
            . '--data-raw \'{"uid":"movies","primaryKey":"imdbId"}\' | jq',
        ),
        new Bullet(
//            fade: false,
            size: 1,
            msg: [
            ],
        ),
        new DisplayCode(
            lang: 'json',
            note: 'All write tasks are async, so a task object is returned',
            content: <<<'JSON'
{ 
  "taskUid": 171,
  "indexUid": "movies",
  "status": "enqueued",
  "type": "indexCreation",
  "enqueuedAt": "2025-11-13T10:45:23.072030011Z"
}
JSON,
        ),
        new Bullet(style: 'callout', fade: false, msg: 'query the task by id until status is finished')
    ],
)]
#[Step(
    title: 'Create index Symfony HttpClient)',
    actions: [
        new DisplayCode(lang: 'yaml', content: <<<'YAML'
framework:
  http_client:
    scoped_clients:
      meili.client:
        base_uri: '%env(MEILI_SERVER)%'
        headers:
          Content-Type: 'application/json'
          Accept: 'application/json'
          Authorization: 'Bearer %env(MEILI_API_KEY)%'
YAML
        ),
        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
    public function __construct(
        private readonly HttpClientInterface $meiliClient
    ) {}

    public function createIndex() {
        $response = $this->meiliClient->request('POST', '/indexes', [
            'json' => [
                'uid'        => 'movies',
                'primaryKey' => 'imdbId',
            ],
        ]);
        $task = $response->toArray();
    }
PHP
        ),
    ],
)]
#[Step(
    title: 'Configure settings',
    actions: [
        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
$response = $this->meiliClient->request('PATCH', '/indexes/movies/settings', [
    'json' => [
        'searchableAttributes' => ['title', 'overview'],
        'filterableAttributes' => ['genre', 'year'],
    ],
]
);
$task = $response->toArray();
PHP
        ),
//        new Bullet(msg: [
//            "configure searchable, filterable, and sortable fields",
//            "settings updates are asynchronous",
//            "search results change only after task completes",
//        ]),
    ],

)]
#[Step(
    description: "add or update documents",
    actions: [
        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
$response = $this->meiliClient->request('POST', '/indexes/movies/documents', [
    'json' => [
        [
            'id'       => 1,
            'title'    => 'Pony Movie',
            'overview' => 'A movie about a pony',
            'genre'    => 'family',
            'year'     => 2024,
        ],
    ],
]);

$task = $response->toArray();
PHP
        ),
        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
$response = $this->meiliClient->request('POST', '/indexes/movies/search', [
    'json' => [
        'q'        => 'pony',
        'limit'    => 20,
        'offset'   => 0,
        'filter'   => ['genre = "family"'],
    ],
]);

$results = $response->toArray();
PHP
        ),
    ],
    bullets: [
        "send documents as JSON",
        "must contain a primary key field",
        "ingestion is asynchronous (tasks)",
        "documents become searchable after indexing finishes",
    ]

)]
#[Step('Meilisearch PHP library', "Forget raw curl/api, we have a PHP library...",
    actions: [
        new DisplayCode(
            lang: 'bash',
            content: 'composer require meilisearch/meilisearch-php symfony/http-client nyholm/psr7:^1.0'
        ),

        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
$client = new Client('http://127.0.0.1:7700', 'masterKey');
// index is an ENDPOINT service
$index = $client->index('movies');

$documents = [
    ['id' => 4,  'title' => 'Mad Max: Fury Road', 'genres' => ['Adventure, Science Fiction']],
    ['id' => 5,  'title' => 'Moana', 'genres' => ['Fantasy, Action']],
    ['id' => 6,  'title' => 'Philadelphia', 'year' => 1997, 'genres' => ['Drama']],
];

$task = $index->addDocuments($documents);
$task->wait(); // optional, wait until async finished
PHP,
        ),
    ], bullets: [
        'addDocuments()',
        'search()',
        'updateSettings()'
    ]
)]
#[Step("Use a bundle to encapculate the server interactions",
    actions: [
        new Bullet([
            'meilisearch/search-bundle: official bundle, YAML, no UI search',
            'mezcalito/ux-search: YAML, no index creation',
            'survos/meili-bundle: PHP 8.4, attribute-based index and UI search'
        ], size: 2),
        new DisplayCode(
            lang: 'yaml',
            content: <<<'YAML'
# config/packages/meili_search.yaml
meili_search:
    indexes:
        movies:
            primary_key: id
            settings:
                searchableAttributes: ['title', 'overview']
                filterableAttributes: ['genre', 'year']
                sortableAttributes: ['year']
YAML
        ),
        new DisplayCode(
            fade: true,
            note: "survos/meili-bundle, attributes applied to a doctrine entity",
            lang: 'PHP',
            content: <<<'PHP'
#[MeiliIndex(
    primaryKey: 'imdbId',
    searchable: ['title','overview'],
    filterable: ['year', 'budget', 'genres'],
    sortable: ['year', 'budget'],
)]
#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
PHP,
        ),
        new SplitSlide(),
        new DisplayCode(
            fade: true,
            note: "can share configuration with ApiPlatform",
            lang: 'PHP',
            content: <<<'PHP'
#[ApiFilter(filterClass: SearchFilter::class, properties: self::FILTERABLE_FIELDS)]
#[ApiFilter(filterClass: SearchFilter::class, properties: self::SEARCHABLE_FIELDS)]
#[ApiFilter(filterClass: OrderFilter::class, properties: self::SORTABLE_FIELDS)]
#[MeiliIndex(
    primaryKey: 'code',
    filterable: self::FILTERABLE_FIELDS,
    sortable: self::SORTABLE_FIELDS,
    searchable: self::SEARCHABLE_FIELDS,
)]
final class Wine
{
    public const FILTERABLE_FIELDS = ['year', 'type', 'domain', 'quantity', 'price', 'quality'];
    public const SORTABLE_FIELDS = ['year', 'quantity', 'price', 'quality'];
    public const SEARCHABLE_FIELDS = [];
PHP,
        ),
    ]
)]
#[Step('Populating a Meilisearch Index',
    actions: [
        new DisplayCode(
            note: "Like doctrine:schema:update --force with sqlite",
            lang: 'bash',
            content: <<<'BASH'
# Create a sqlite database during testing
bin/console doctrine:schema:update --force
# OR use migrations
bin/console make:migration && bin/console doctrine:migrations:migrate --force

# sends the settings from the attributes calls updateSettings()
bin/console meili:settings:update --force [--index movie] [--reset]


BASH,
        ),
        new SplitSlide(),
        new DisplayCode(
            note: "Push EXISTING doctrine entities to Meili",
            lang: 'bash',
            content: <<<'BASH'
# iterates through the entity, normalizes and sends addDocuments()
bin/console meili:populate App\\Entity\\Movie

BASH,
        ),
        new DisplayCode(
            note: "Automatically populate meili via postFlush() listener",
            lang: 'bash',
            content: <<<'BASH'
# Create your own import app (or UI)
bin/console app:import-movies --file movies.csv

# survos/import-bundle used in the demo
bin/console import:entities Movie data/movie.jsonl

# add data via the web, api, other service, whatever
BASH,
        ),
    ]
)]
function meili_overview(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('install-meili', null, 'Prerequisite: A meilisearch instance')]
#[Step(
    actions: [
        new Bullet([
            'run with docker',
            'install via docker-composer.yml',
            'free trial on meilisearch'
        ])
    ]
)]

function prepare_launch(): void {};

#[AsTask('install-meili', null, 'Install meilisearch')]
#[Step(
    actions: [
        new Bash(
            'docker run -it --rm  -p 7700:7700 \
  -v $(pwd)/meili_data:/meili_data \
  getmeili/meilisearch:latest',
            run: false,
            note: "run with docker"
        ),
    ],
)]
function install_meili(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('docker-config', null, 'configure docker.yml')]
#[Step(
    description: "docker + riccox dashboard",
    actions: [
        new DisplayCode(lang: 'yaml', content: <<<'YAML'
services:
    meilisearch:
        image: getmeili/meilisearch:v1.24
        container_name: meilisearch
        volumes: [./.docker/meili-1.24/:/meili_data/]
        environment:
            MEILI_NO_ANALYTICS: true
            MEILI_HTTP_ALLOWED_ORIGINS: '["*"]'
        networks: [meili_network]
        ports: ["7700:7700"]
    meilisearch-ui:
        image: riccoxie/meilisearch-ui:latest
        container_name: meilisearch-ui
        restart: on-failure:5
        environment:
            SINGLETON_MODE: true
            SINGLETON_HOST: http://localhost:7700
            SINGLETON_API_KEY: your-api-key
        ports: ["24900:24900"]
        depends_on: [meilisearch]
        networks: [meili_network]
    networks: [meili_network: [driver: bridge]]
YAML
        )])]
function configure_docker(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'build', description: "Let's build a demo!")]
#[Step(
    description: 'create a new webapp project',
    // we are NOT actually running the actions, they are here for the slide
    actions: [
        new Bullet([
            'Create demo project with symfony:new --webapp',
            'Install bundles',
            'Download data',
            'create <em>Movie</em> entity from downloaded data',
            'configure and create sqlite database',
            'create meilisearch movie index',
            'Import movie data to database and index',
            'Configure EasyAdmin-based Meili Dashboard',
        ]),
        new Bash('symfony new --webapp meili-demo --dir' . EA_DEMO_DIR,
            run: false,
        ),
    ]
)]
function ea_demo(): void
{
//    ea_new();
//    required_packages();
    with_symfony();
    install();
    importmap(); // bundles:importmap
    ea_download(); // download
    ea_make_entity(); // make-entity
    ea_configure(); // configure
    ea_create_database(); // create:database
    ea_load_database(); // load:database
    ea_dashboard();
    facets();
    io()->success('Meili demo completed.');
}

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
//        new CopyFile(INPUT_DIR . '/config/packages/ux_icons.yaml', 'config/packages/ux_icons.yaml'),

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


