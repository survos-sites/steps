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
use Survos\MeiliBundle\Metadata\MeiliIndex;
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

#[AsTask('meili-overview', null, 'What is Meilisearch')]
#[Step(
    bullets: [
        "An open-source, developer-friendly search engine focused on speed and relevance",
        "A lightweight alternative to Elasticsearch or Algolia with fast indexing",
        "Provides typo-tolerance, filters, facets, and semantic search out of the box",
        "Designed for real-time search UIs with instant responses under 50ms",
        "various Symfony bundles that encapsulate the API interactions",
        "built-in vector store for semantic search",
    ],
)]
#[Step(
    title: 'How the API works',
    description: 'Background: Raw API calls',
    actions: [
        new Bash(
            note: 'Create the "movies" index with primary key "imdbId".',
            command: 'curl -s "http://127.0.0.1:7700/indexes" ' . "\\\n"
            . '-H "Content-Type: application/json" ' . "\\\n"
            . '--data-raw \'{"uid":"movies","primaryKey":"imdbId"}\' | jq',
        ),
//        new Bullet(
//            fade: false,
//            msg: [
//            "define an index with an optional primary key (default: 'id')",
//            ],
//        ),
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
    title: 'create-index (Symfony HttpClient)',
    description: 'Still raw, but with HttpClientInterface',
    actions: [
        new DisplayCode(lang: 'yaml', content: <<<'YAML'
framework:
  http_client:
    scoped_clients:
      meili.client:
        base_uri: 'http://127.0.0.1:7700'
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
    title: 'Configure without PHP library',
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
        new Bullet(msg: [
            "configure searchable, filterable, and sortable fields",
            "settings updates are asynchronous",
            "search results change only after task completes",
        ]
        )
    ],

)]
#[Step(
    title: 'add-documents',
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
        new Bullet([
    "send documents as JSON",
    "must contain a primary key field",
    "ingestion is asynchronous (tasks)",
    "documents become searchable after indexing finishes",
]),

    ],
)]
#[Step('Meilisearch PHP library', "Fortunately, we have a PHP library...",
    actions: [
        new DisplayCode(
            lang: 'bash',
            content: 'composer require meilisearch/meilisearch-php symfony/http-client nyholm/psr7:^1.0'
        ),

        new DisplayCode(
            lang: 'PHP',
            content: <<<'PHP'
$client = new Client('http://127.0.0.1:7700', 'masterKey');
// get the apiEndpoint!
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
        new Bullet(msg: [
            'if id already exists, data will be _updated_',
            'Genre and year are filterable, not searchable',
        ])
    ]
)]
#[Step('Even better, we have bundles!',
    bullets: [
        'meilisearch/search-bundle: official bundle from meilisearch',
        'https://github.com/Mezcalito/ux-search, YAML, readonly',
        'survos/meili-bundle: PHP 8.4, attribute-based'
    ],
    actions: [
        new DisplayCode(
            note: "survos/meili-bundle, attributes applied to a doctrine entity",
            lang: 'PHP',
            content: <<<'PHP'
#[MeiliIndex(
    primaryKey: 'imdbId',
    searchable: ['title','overview'],
    filterable: ['year', 'budget', 'genres'],
    sortable: ['year', 'budget'],
)]
PHP,
        ),
        new DisplayCode(
            lang: 'bash',
            content: <<<'BASH'
bin/console meili:settings:update --force
bin/console meili:populate
BASH,
        ),
    ]
    )]
function meili_overview(): void
{
    RunStep::run(_actions_from_current_task(), context());
}


#[AsTask('install-meili', null, 'Install meilisearch')]
#[Step(
    actions: [
        new Bash('docker run -it --rm  -p 7700:7700 \
  -v $(pwd)/meili_data:/meili_data \
  getmeili/meilisearch:latest'),
    ],
    bullets: [
        'run with docker',
        'install via docker-composer.yml',
        'free trial on meilisearch'
    ],
)]
function install_meili(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('docker-config', null, 'configure docker.yml')]
#[Step(
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

    ea_open();
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
    bullets: [
        'meilisearch/search-bundle: official bundle from meilisearch',
        'https://github.com/Mezcalito/ux-search, YAML, readonly',
        'survos/meili-bundle: PHP 8.4, attribute-based'
    ],
    actions: [
        new ComposerRequire(['survos/meili-bundle']),
            new Bullet(style: 'callout', msg: "Automatically installed dependencies"),
            new ComposerRequire([
                    'meilisearch/meilisearch-php',
                    'symfony/http-client',
                    'nyholm/psr7',
                ]
            )
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
//        new Console('doctrine:schema:update', ['--force']),
        // creates the artifact, but doesn't display it.  Internal
        new Artifact('src/Entity/Movie.php', "Movie.php"),

        new DisplayArtifact('src/Entity/Movie.php'),
        // we could also display the artifact!  For testing, let's make sure they both work.
//        new DisplayCode('/src/Entity/Movie.php', lang: 'php'),
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
    'Load the database',
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
PHP),
        new Bullet([
            'Needs to flush periodically',
            'we may want --limit during testing',
        ]
        ),
        new Console('import:entities', ['Movie', '--file', 'data/movies.csv', '--limit', '500'], a: 'import.txt'),
        new Bullet([
                'import:entities is a simple way to get flat(easy) data from csv->doctrine',
                'same internal logic that creates the Entity class',
                'tweak the entity class using Property Hooks',
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

#[AsTask('show_data', CASTOR_NAMESPACE, 'We have data~')]
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


