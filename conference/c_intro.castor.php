<?php 

declare(strict_types=1);

if (!defined('INPUT_DIR')) {
    define('INPUT_DIR', __DIR__ . '/../inputs'); // absolute path to the 'steps' Symfony app
}

/**
 * file: castor/showtime.castor.php
 * SymfonyCon Amsterdam 2025 — "Semantic & hybrid search with MeiliSearch"
 *
 * Slides driven by Survos\StepBundle.
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
#[AsTask(name: 'agenda', description: 'SymfonyCon Amsterdam 2025')]
#[Step(
    description: "Semantic & hybrid search with MeiliSearch",
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'See semantic and hybrid search in action',
                'Treat your Doctrine entity as the source of truth',
                'Understand embedders, cost and configuration',
                'Leave with a workflow for searching YOUR data',
            ]
        ),
    ]
)]
function showtime_agenda(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

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

/**
 * Live demo entry: open the main demo site.
 */
#[AsTask(name: 'demo', description: 'Open main demo (meili.survos.com)')]
#[Step(
    description: 'A demo of InstantSearch (one use of Meilisearch)',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Keyword search',
                'Facets and filters',
                'Semantic search',
            ]
            ),
    ]
)]
function showtime_demo(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Dataset gallery: show that multiple datasets share the same pattern.
 */
#[AsTask(name: 'datasets', description: 'Datasets on meili.survos.com')]
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
        new BrowserVisit('/', 'https://meili.survos.com/meili/search/index/meili_product', a: 'meili-demo.png'),
    ]
)]
function showtime_datasets(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Most attendees already have Doctrine entities.
 */
#[AsTask(name: 'entities-intro', description: 'You already have entities')]
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
            fade: false,
            style: 'callout',
            msg: 'Good news: you are already most of the way to MeiliSearch'
        ),
    ]
)]
function showtime_entities_intro(): void
{
    RunStep::run(_actions_from_current_task(), context());
}



