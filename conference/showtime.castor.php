<?php declare(strict_types=1);

/**
 * file: castor/showtime.castor.php
 * SymfonyCon Amsterdam 2025 — "Semantic & hybrid search with MeiliSearch"
 *
 * Slides driven by Survos\StepBundle.
 */

const INPUT_DIR        = __DIR__ . '/../inputs';
const CASTOR_NAMESPACE = null;

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
    Artifact,
    DisplayArtifact,
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

/**
 * Helper that SurvosStepBundle usually provides. If you already have this
 * in a common file, you can remove this copy.
 *
 * @return array<int, object>
 */
function _actions_from_current_taskXX(): array
{
    return RunStep::getActionsFromCurrentTask();
}

/**
 * High-level agenda (today's journey).
 */
#[AsTask(name: 'showtime:agenda', description: 'SymfonyCon Amsterdam 2025 — talk outline')]
#[Step(
    description: "SymfonyCon Amsterdam 2025 · Semantic & hybrid search with MeiliSearch",
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'See semantic and hybrid search in action',
                'Treat your Doctrine entity as the source of truth',
                'Understand embedders, cost and configuration',
                'Leave with a mental model, not just commands',
            ]
        ),
    ]
)]
function showtime_agenda(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Live demo entry: open the main demo site.
 */
#[AsTask(name: 'showtime:demo', description: 'Open main demo (meili.survos.com)')]
#[Step(
    description: 'First: quick demo',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Keyword search',
                'Facets and filters',
                'Semantic search',
                'Hybrid slider (keyword ↔ semantic)',
            ]
            ),
        new BrowserVisit('/', 'https://dummy.survos.com/search/index/dtdemo_product', a: 'meili-demo.png'),
//        new DisplayArtifact('meili-demo.png', note: 'Screenshot from meili.survos.com (optional)')
    ]
)]
function showtime_demo(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Dataset gallery: show that multiple datasets share the same pattern.
 */
#[AsTask(name: 'showtime:datasets', description: 'Datasets on meili.survos.com')]
#[Step(
    description: 'Many datasets, one approach',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                '🎬 Movies · ratings, genres, year',
                '🍷 Wines · region, type, price, quality',
                '🚗 Cars · make, model, specs',
                '🏛 WCMA · artworks and metadata',
                '🦸 Marvel · characters and comics',
                '🛒 Products · price, stock, tags, brand',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Each dataset uses the same pattern: Doctrine entity → Meili index → search UI'
        ),
    ]
)]
function showtime_datasets(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Most attendees already have Doctrine entities.
 */
#[AsTask(name: 'showtime:entities-intro', description: 'You already have entities')]
#[Step(
    description: 'What you probably already have',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'A real database with useful data',
                'Doctrine entities describing that data',
                'Possibly ApiPlatform resources on top',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Good news: you are already most of the way to MeiliSearch'
        ),
    ]
)]
function showtime_entities_intro(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Entity as single source of truth: ApiPlatform + MeiliIndex + groups/fields.
 */
#[AsTask(name: 'showtime:entity-hero', description: 'Entity as source of truth')]
#[Step(
    description: 'One entity describing API, filters, index and semantic behaviour',
    actions: [
        new DisplayCode(
            lang: 'PHP',
            note: 'Product entity with ApiPlatform filters and Meili index config',
            content: <<<'PHP'
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['product.read', 'product.details'],
            ]
        ),
        new GetCollection(
            normalizationContext: [
                'groups' => ['product.read'],
            ]
        ),
    ],
    normalizationContext: ['groups' => ['product.read', 'product.details', 'rp']],
)]
#[ApiFilter(OrderFilter::class, properties: ['price', 'stock', 'rating'])]
#[ApiFilter(FacetsFieldSearchFilter::class,
    properties: ['category', 'tags', 'rating', 'stock', 'price'],
    arguments: ['searchParameterName' => 'facet_filter']
)]
#[ApiFilter(RangeFilter::class, properties: ['rating', 'stock', 'price'])]
#[MeiliIndex(
    primaryKey: 'sku',
    persisted: new Fields(
        fields: ['sku', 'stock', 'price', 'title', 'brand'],
        groups: ['product.read', 'product.details', 'product.searchable']
    ),
    displayed: ['*'],
    filterable: new Fields(
        fields: ['category', 'tags', 'rating', 'price', 'brand'],
    ),
    sortable: ['price', 'rating'],
    searchable: new Fields(
        groups: ['product.searchable']
    ),
    embedders: ['product'],
)]
class Product
{
    // ...
}
PHP
        ),
        new Bullet(
            fade: true,
            msg: 'ApiResource, filters and MeiliIndex live on one class: the Product entity'
        ),
    ]
)]
function showtime_entity_hero(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Serialization groups and relations.
 */
#[AsTask(name: 'showtime:groups', description: 'Real-world relationships and groups')]
#[Step(
    description: 'Real apps use serialization groups to control Meili payload',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Demos can serialize everything without trouble',
                'Real apps have relations, circular references and sensitive fields',
                'We rarely want to expose every field to Meili',
            ]
        ),
        new SplitSlide(),
        new DisplayCode(
            lang: 'PHP',
            note: 'Using groups to control what Meili sees',
            content: <<<'PHP'
#[Groups(['product.read', 'product.searchable'])]
public string $title;

#[MeiliIndex(
    persisted: new Fields(groups: ['product.read']),
    searchable: new Fields(groups: ['product.searchable']),
)]
PHP
        ),
        new Bullet(
            style: 'callout',
            msg: 'You can restrict by fields, by groups, or mix both'
        ),
    ]
)]
function showtime_groups(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Explain the four search modes conceptually.
 */
#[AsTask(name: 'showtime:search-modes', description: 'Filtered, full-text, semantic, hybrid')]
#[Step(
    description: 'Four flavours of search',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Filtered search · boolean filters on structured fields (category, price, year)',
                'Full-text search · typo-tolerant keyword search',
                'Semantic search · match by meaning, not just shared words',
                'Hybrid search · blend keyword and semantic scores',
            ]
        ),
    ]
)]
function showtime_search_modes(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Semantic search intuition – embeddings and examples.
 */
#[AsTask(name: 'showtime:semantic', description: 'What semantic search does')]
#[Step(
    description: 'What semantic search actually does',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Take text (title, description, brand, etc.)',
                'Turn it into an embedding (a vector of numbers)',
                'Similar meaning → vectors close together in space',
                'Queries are also embedded; we search by distance',
            ]
        ),
        new SplitSlide(),
        new Bullet(
            fade: true,
            msg: [
                '💡 "cool gifts for a tech executive"',
                '🏠 "things to decorate an apartment"',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'These are ideas, not exact keywords'
        ),
    ]
)]
function showtime_semantic(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Embedder configuration YAML.
 */
#[AsTask(name: 'showtime:embedder-config', description: 'Configuring an embedder')]
#[Step(
    description: 'Configuring an embedder once',
    actions: [
        new DisplayCode(
            lang: 'yaml',
            note: 'Bundle configuration for an OpenAI-based embedder',
            content: <<<'YAML'
survos_meili:
    host: '%env(default::MEILI_SERVER)%'
    apiKey: '%env(default::MEILI_API_KEY)%'
    searchKey: '%env(default::MEILI_SEARCH_KEY)%'
    embedders:
        product:
            source: 'openAi'
            model: 'text-embedding-3-large'
            apiKey: '%env(OPENAI_API_KEY)%'
            template: 'templates/liquid/product.liquid'
            examples:
                - 'cool gifts for a tech executive'
                - 'things to decorate an apartment'
YAML
        ),
        new Bullet(
            fade: true,
            msg: [
                'source – who does the embeddings (OpenAI here)',
                'model – quality and cost trade-off',
                'apiKey – your OpenAI key',
                'template – what text is embedded per record',
                'examples – give the model a feel for typical queries',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'We skip Liquid template generation details here; that is tutorial material'
        ),
    ]
)]
function showtime_embedder_config(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Cost model + meili:estimate.
 */
#[AsTask(name: 'showtime:cost', description: 'Cost and meili:estimate')]
#[Step(
    description: 'Cost is usually tiny',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'text-embedding-3-small ≈ $0.02 per 1M tokens',
                'text-embedding-3-large ≈ $0.13 per 1M tokens',
                '10,000 products × 100 tokens ≈ 1M tokens',
                'Indexing a full catalog can be just a few cents',
            ]
        ),
        new DisplayCode(
            lang: 'bash',
            note: 'Estimate token usage and cost before you embed',
            content: <<<'BASH'
bin/console meili:estimate Product
# Estimates token usage and approximate $ cost
BASH
        ),
    ]
)]
function showtime_cost(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Apply settings and embed.
 */
#[AsTask(name: 'showtime:settings-embed', description: 'Apply settings and generate embeddings')]
#[Step(
    description: 'Apply settings and generate embeddings',
    actions: [
        new DisplayCode(
            lang: 'bash',
            note: 'One command to push settings and create embeddings',
            content: <<<'BASH'
bin/console meili:settings:update --force --embed
BASH
        ),
        new Bullet(
            fade: true,
            msg: [
                'Reads #[MeiliIndex] and #[MeiliField] metadata from your entity',
                'Calls Meili settings APIs (searchable, filterable, sortable, etc.)',
                'Iterates entities to generate embeddings with the configured embedder',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'After this, your index is ready for semantic and hybrid search'
        ),
    ]
)]
function showtime_settings_embed(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Hybrid search explanation.
 */
#[AsTask(name: 'showtime:hybrid', description: 'Hybrid search in practice')]
#[Step(
    description: 'Hybrid search: best of both worlds',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Keyword score – exact matches, filters, precision',
                'Semantic score – intent and related concepts',
                'Hybrid: score = (1 − α) · keyword + α · semantic',
            ]
        ),
        new SplitSlide(),
        new Bullet(
            fade: true,
            msg: [
                '◀️ keyword-only · precise',
                '🔀 balanced hybrid',
                '▶️ semantic-only · exploratory',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'In the UI this becomes a simple slider between keyword and semantic'
        ),
    ]
)]
function showtime_hybrid(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Symfony Messenger and async indexing.
 */
#[AsTask(name: 'showtime:messenger', description: 'Indexing at scale with Messenger')]
#[Step(
    description: 'Indexing at scale with Symfony Messenger',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Indexing large datasets should be asynchronous',
                'Meili itself is async: writes return tasks',
                'On the Symfony side, Messenger is the natural fit',
            ]
        ),
        new SplitSlide(),
        new Bullet(
            fade: true,
            msg: [
                'Doctrine postFlush or a domain event',
                'Dispatch a message with the entity id',
                'Worker loads the entity, normalizes, and calls Meili',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Same pattern works for full re-indexes and embedding refreshes'
        ),
    ]
)]
function showtime_messenger(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Optional: CSV/JSON → JSONL → entity.
 */
#[AsTask(name: 'showtime:import-pipeline', description: 'Optional import pipeline')]
#[Step(
    description: 'Optional: from files to entities',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'If your data starts as CSV/JSON/ZIP, there is a pipeline:',
                'json:convert – CSV/JSON → JSONL + profile',
                'json:entity – profile → Doctrine entity with sensible types',
                'Then treat the entity like any other for Meili and the API',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'The tutorial walks through these steps in detail; the talk focuses on entities and semantics'
        ),
    ]
)]
function showtime_import_pipeline(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * EasyAdmin-based Meili dashboard.
 */
#[AsTask(name: 'showtime:dashboard', description: 'Bonus: EasyAdmin Meili dashboard')]
#[Step(
    description: 'Bonus: an instant admin dashboard',
    actions: [
        new DisplayCode(
            lang: 'bash',
            note: 'Generate an EasyAdmin dashboard wired to Meili',
            content: <<<'BASH'
bin/console code:meili:admin --path=/
BASH
        ),
        new Bullet(
            fade: true,
            msg: [
                'Generates EasyAdmin dashboard and CRUD controllers',
                'Uses Meili metadata to drive filters and search fields',
                'Produces a convenient developer cockpit for your index',
            ]
        ),
        new BrowserVisit('/', 'http://localhost:24900/ins/0', a: 'riccox-admin.png'),
        new DisplayArtifact('riccox-admin.png', note: 'Riccox Meili admin (optional)'),
    ]
)]
function showtime_dashboard(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Bundle status and vendor-neutral pattern.
 */
#[AsTask(name: 'showtime:bundle-status', description: 'Bundle status and pattern')]
#[Step(
    description: 'About the bundle and the pattern',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Bundle is used in production on my own sites',
                'Still under active development; APIs may evolve',
                'Core idea is stable: define your index with attributes on the entity',
                'Drive Meili settings from entities, not scattered YAML',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'You can reuse the same metadata pattern with other Meili/Symfony bundles'
        ),
    ]
)]
function showtime_bundle_status(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Tutorial slide: step-by-step details available.
 */
#[AsTask(name: 'showtime:tutorial', description: 'Tutorial and repo')]
#[Step(
    description: 'Tutorial: every step documented',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Commands to load each dataset into Meili',
                'CSV/JSON → JSONL → entity generation',
                'Index settings, semantic and hybrid configuration',
                'EasyAdmin dashboard and demo scripts',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Slides and code are available in the GitHub repo and tutorial page'
        ),
    ]
)]
function showtime_tutorial(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Recap.
 */
#[AsTask(name: 'showtime:recap', description: 'Recap slide')]
#[Step(
    description: 'Recap',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Your Doctrine entity can describe DB schema, API, Meili index and semantics',
                'Semantic and hybrid search are practical, cheap and powerful',
                'Attributes give a straightforward way to configure all of this',
                'The tutorial and repo cover the step-by-step details',
            ]
        ),
    ]
)]
function showtime_recap(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

