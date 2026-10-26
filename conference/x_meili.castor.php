<?php declare(strict_types=1);

/**
 * file: castor/showtime.castor.php
 * SymfonyCon Amsterdam 2025 — "Semantic & hybrid search with MeiliSearch"
 *
 * Slides driven by Survos\StepBundle.
 */

if (!defined('INPUT_DIR')) {
    define('INPUT_DIR', __DIR__ . '/../inputs'); // absolute path to the 'steps' Symfony app
}

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
};

/**
 * Explain the four search modes conceptually.
 */
#[\Survos\StepBundle\Metadata\Deck(
    title: 'Keyword, Facet and Semantic Searching with Meilisearch and Symfony',
    author: 'Tac Tacelosky',
    logo: 'sfcon_logo.svg',
    closing: 'THANK YOU',
    contact: [
        'SymfonyConnect' => '@tacman',
        'Slack' => '@Tac',
        'Email' => 'tacman@gmail.com',
        'Demo' => 'https://meili.survos.com',
        'Repo' => 'https://github.com/survos-sites/meili',
    ],
)]
function deck_x_meili(): void
{
}

#[AsTask(name: 'search-modes',
    description: 'Filtered, full-text, semantic, hybrid')]
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
#[AsTask(name: 'semantic', description: 'What semantic search does')]
#[Step(
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
            fade: false,
            size: 4,
            msg: [
                '💡 "cool gifts for a tech executive"',
                '🏠 "things to decorate an apartment"',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'These are ideas, not exact keywords'
        ),
        new Bash('open https://meili.survos.com/meili/search/embedder/meili_product/product', run: false)
    ]
)]
function showtime_semantic(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Cost model + meili:estimate.
 */
#[AsTask(name: 'cost', description: 'Cost and meili:estimate')]
#[Step(
    description: 'Cost can be surprisingly inexpensive',
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
 * Embedder configuration YAML.
 */
#[AsTask(name: 'embedder-config', description: 'Configuring Semantic Search is EASY')]
#[Step(

//    description: 'Configuring an embedder once',
    actions: [
        new Bullet(msg: [
            'Configure which AI model',
            'create template (data->natural language)',
            'Add to #[Meiliindex] attributes',
            'Update the settings',
        ]),
        new SplitSlide(),
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
YAML
        ),
        new Bullet(
            fade: true,
            msg: [
                'source – who does the embeddings (OpenAI here)',
                'model – quality and cost trade-off',
                'apiKey – your OpenAI key',
                'template – creates natural language text',
            ]
        ),
        new SplitSlide(),
        new DisplayCode(
            note: "product.liquid",
            path: INPUT_DIR . '/product.liquid'
        ),
    ]
)]
function showtime_embedder_config(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Apply settings and embed.
 */
#[AsTask(name: 'settings-embed',
//    description: 'Apply settings and generate embeddings'
)]
#[Step(
    description: 'Add embedders: and update settings with --embed',
    actions: [
        new DisplayCode(path: INPUT_DIR . '/php/movie-with-embedder.php'),
        new DisplayCode(
            lang: 'bash',
            note: 'One command to push settings and create embeddings',
            content: <<<'BASH'
bin/console meili:settings:update --force --embed
BASH
        ),
        new Bullet(
            style: 'callout',
            msg: 'When finished, semantic search is available'
        ),
    ]
)]
function showtime_settings_embed(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'In summary',
//    description: 'Apply settings and generate embeddings'
)]
#[Step(
    actions: [
        new Bullet(
            msg: [
                'Is Doctrine required?',
                'No, any DTO can be used for creating the index',
                'Is there security?',
                'Yes, The API key is only the surface.',
                'Public keys are used in InstantSearch',
            ]
        ),
        new SplitSlide(),
        new Bullet(
            msg: [
                'Meili is fast and free and easy',
                'Configuration can be done with attributes',
                'See your data in a whole new way',
                'survos/meili-bundle is new, feedback welcome!',
            ]
        ),
    ]
)]
function in_summary(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

