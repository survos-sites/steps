<?php declare(strict_types=1);

if (!defined('INPUT_DIR')) {
    define('INPUT_DIR', __DIR__ . '/../inputs'); // absolute path to the 'steps' Symfony app
}
//const INPUT_DIR = __DIR__ . '/../inputs'; // known config, php, images, etc.  urls?
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

#[AsTask(name: 'agenda', description: "The nitty gritty")]
#[Step(
//    description: 'The next 30 minutes...',
//    bullets: [
//        'sidebar!'
//    ],
    actions: [
        new Bullet(msg: [
            'What is meilisearch',
            'Low-level API',
            'PHP library API',
            'Bundle API/Attributes',
            "Command Overview",
            "Production Configuration",
        ]),
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
                "An open-source, developer-friendly search engine",
                "Simple REST interface, many client libraries",
                "Easy alternative to Elasticsearch or Algolia",
                "Typo-tolerance, keyword search, filters and semantic search out of the box",
                "FAST!  Search responses under 50ms",
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
    title: 'Lowest level: HTTP API calls',
//    description: 'Background: Raw API calls',
    actions: [
        new Bash(
            note: 'Typical API call: create an index',
            command: 'curl -s "http://127.0.0.1:7700/indexes" ' . "\\\n"
            . '-H "Content-Type: application/json" ' . "\\\n"
            . '--data-raw \'{"uid":"movies","primaryKey":"imdbId"}\' | jq',
        ),
        new Bullet(
//            fade: false,
            size: 1,
            msg: [
                "Straightfoward http calls: POST/PATCH to update, GET/POST to fetch",
                "define an index with an optional primary key (default: 'id')",
                "async -- a task is returned immediately",
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
    title: 'Create index Symfony HttpClient',
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
        new SplitSlide(),

//        new DisplayCode(
//            lang: 'PHP',
//            content: <<<'PHP'
//$response = $this->meiliClient->request('PATCH', '/indexes/movies/settings', [
//    'json' => [
//        'searchableAttributes' => ['title', 'overview'],
//        'filterableAttributes' => ['genre', 'year'],
//    ],
//]
//);
//$task = $response->toArray(); // returns async task id
//PHP
//        ),
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
    description: "add or update documents",
    actions: [
        new DisplayCode(
            note: 'add docs', fade: false,
            path: INPUT_DIR . '/php/update-docs.php',
            lang: 'PHP'
        ),
        new DisplayCode(
            lang: 'PHP',
            size: 1,
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
#[Step('Meilisearch PHP library', "Use the SDK to encapsulate the raw http calls",
    actions: [
        new DisplayCode(
            lang: 'bash',
            content: 'composer require meilisearch/meilisearch-php symfony/http-client nyholm/psr7:^1.0'
        ),
//        new CopyFile(INPUT_DIR . '/config/packages/ux_icons.yaml', 'config/packages/ux_icons.yaml'),


        new DisplayCode(
            lang: 'PHP',
            path: INPUT_DIR . '/php/add-docs-with-client.php',
        ),
    ], bullets: [
        'addDocuments()',
        'search()',
        'updateSettings()'
    ]
)]
#[Step(
    title: 'Configure settings',
    actions: [
        new Bullet(msg: [
            "configure searchable, filterable, and sortable fields",
            "settings updates are asynchronous",
            "search results change only after task completes",
        ]),
        new SplitSlide(),
        new DisplayCode(
            path: INPUT_DIR . '/php/update-settings-with-sdk.php',
        ),

    ],

)]
function sdk()
{
}

;

#[AsTask('bundles', "Attributes for almost everything")]
#[Step(
    title: 'Attributes!',
    description: "Doctrine Entity is the Source of Truth",
    actions: [
//        new Bullet([
//            'meilisearch/search-bundle: official bundle',
//            ' --     configure index with YAML, no insta-search',
//            'mezcalito/ux-search: insta-search, no index creation',
//            ' --     Symfony LiveComponent',
//            'survos/meili-bundle: PHP 8.4, attribute-based index and UI search',
//            ' --     Attributes for configuration and instasearch, js-twig templates',
//        ], size: 2),
//        new SplitSlide(),
        new DisplayCode(
            fade: true,
            note: "survos/meili-bundle, attributes applied to a doctrine entity",
            path: INPUT_DIR . '/php/movie-with-attributes.php',
        ),
    ]
)]
#[Step(
    title: 'From Attributes to Actions',
    description: "Next logical steps: configure and populate",
    actions: [
        new Bullet(
            msg: [
                'Create/update the index with settings in attributes',
                'Populate the index with existing data',
                'Populate the index with new data',
            ], size: 2),
        new SplitSlide(),
        new DisplayCode(
            note: "Update the database configuration",
            lang: 'bash',
            content: <<<'BASH'
# In Doctrine:
bin/console doctrine:schema:update --force
BASH,
        ),
        new DisplayCode(
            note: "Update the meilisearch configuration",
            lang: 'bash',
            content: <<<'BASH'
# sends the settings from the attributes PATCH /settings or updateSettings()
bin/console meili:settings:update --force [--index movie] [--reset]

BASH,
        ),
        new SplitSlide(),
        new DisplayCode(
            note: "Push EXISTING doctrine entities to Meili",
            lang: 'bash',
            content: <<<'BASH'
# iterates through the entity, normalizes and sends addDocuments()
bin/console meili:populate movie
bin/console meili:populate --all
BASH,
        ),
        new Bullet(
            size: 2,
//            header: "Automatically populate meili via postFlush() listener",
            msg: [
                'Loops through the entity table',
                'Dispatches a message to serialize and send',
                'Messages can be async using Symfony Messenger',
                'Async Messages are batched',
            ]
        ),
    ]
)]
function overview_slides()
{
}

;


#[AsTask('background sync')]
#[Step(description: "Keeping doctrine/meili in sync",
    actions: [
        new Bullet(
//            header: "Automatically populate meili via postFlush() listener",
            msg: [
                'Doctrine dispatches a postFlush event',
                'Listener dispatches a message with just the changed fields',
                'Messages can be async using Symfony Messenger',
                'Async Messages are batched',
            ]
        ),
    ]
)]
function postFlush() {}

#[AsTask('recap')]
#[Step('Recap: Basics',
actions: [
    new Bullet(
        msg: [
            'Put MeiliIndex attributes in Doctrine Entity',
            'Update the meili index',
            'populate the meili index with existing data',
            "If data is simple, we're done!"
        ]
    )
    ]
)]
function recap() {}

#[AsTask('advanced')]
#[Step('Real World Considerations',
    actions: [
        new DisplayCode(
            path: INPUT_DIR . '/php/movie-with-attributes.php'
        ),
        new SplitSlide(),
        new DisplayCode(
            path: INPUT_DIR . '/php/movie-with-persisted.php'
        ),
        new Bullet(
            size: 2,
            msg: [
                "Problem: circular issues when serializing",
                "Solution: persisted: ['every','field'] (boo)",
                'use Symfony Serializer #[Group] ',
            ]
        ),
        new SplitSlide("This rings a bell"),
        new Bullet(
            msg: [
                'searchable, sortable, filterable',
                'use Symfony Serializer #[Group]',
                'Where have I heard this before?',
                'ApiPlatform!',
                'Build a world-class API with Attributes.  Details at 11:45 tomorrow!',
            ]
        ),
    ]
)]
function realWorldConsiderations() {}


#[AsTask('integrations')]
#[Step(
    actions: [
        new DisplayCode(
            fade: false,
            note: "can share configuration with ApiPlatform",
            path: INPUT_DIR . '/php/movie-with-api-platform.php',
        ),
        new Bullet(
            style: "callout",
            msg: [
                'Api Platform does not have built-in text search'
            ]
        ),
        new SplitSlide("With Groups"),
        new DisplayCode(
            fade: false,
            note: "can share configuration with ApiPlatform",
            path: INPUT_DIR . '/php/product-with-groups.php',
        ),
    ]
)]
function meili_overview(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
//
//#[AsTask('install-meili', null, 'Prerequisite: A meilisearch instance')]
//#[Step(
//    actions: [
//        new Bullet([
//            'run with docker',
//            'install via docker-composer.yml',
//            'free trial on meilisearch'
//        ])
//    ]
//)]
//function prepare_launch(): void
//{
//}

;

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
        new Bash(
            'docker run -d \
  --name meilisearch-ui \
  --restart on-failure:5 \
  -e SINGLETON_MODE=true \
  -e SINGLETON_HOST=http://localhost:7700 \
  -e SINGLETON_API_KEY=your-api-key \
  -p 24900:24900 \
  --network meili_network \
riccoxie/meilisearch-ui:latest',
            run: false,
            note: "run with docker"
        ),
    ],
)]
function install_meili(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

//#[AsTask('docker-config', null, 'configure docker.yml')]
//#[Step(
//    description: "docker + riccox dashboard",
//    actions: [
//        new DisplayCode(lang: 'yaml', content: <<<'YAML'
//services:
//    meilisearch:
//        image: getmeili/meilisearch:v1.24
//        container_name: meilisearch
//        volumes: [./.docker/meili-1.24/:/meili_data/]
//        environment:
//            MEILI_NO_ANALYTICS: true
//            MEILI_HTTP_ALLOWED_ORIGINS: '["*"]'
//        networks: [meili_network]
//        ports: ["7700:7700"]
//    meilisearch-ui:
//        image: riccoxie/meilisearch-ui:latest
//        container_name: meilisearch-ui
//        restart: on-failure:5
//        environment:
//            SINGLETON_MODE: true
//            SINGLETON_HOST: http://localhost:7700
//            SINGLETON_API_KEY: your-api-key
//        ports: ["24900:24900"]
//        depends_on: [meilisearch]
//        networks: [meili_network]
//    networks: [meili_network: [driver: bridge]]
//YAML
//        )])]
//function configure_docker(): void
//{
//    RunStep::run(_actions_from_current_task(), context());
//}

#[AsTask(name: 'build', description: "Add to your project")]
#[Step(
//    description: 'create a new webapp project',
    actions: [
        new Bullet([
            'Install bundles and front-end assets',
            'configure API keys as needed',
            'Add MeiliIndex attributes',
            'create with meili:settings:update',
            'populate with meili:populate',
            'Configure EasyAdmin-based Meili Dashboard',
        ]),
        new Bash('bin/console code:meili:admin --path=/',
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




