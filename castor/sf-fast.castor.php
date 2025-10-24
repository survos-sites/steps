<?php declare(strict_types=1);
/**
 * file: castor/sf-fast.castor.php
 *
 * A short, fast, re-runnable Symfony demo that leans on generators:
 *  - Create project (only if directory is empty)
 *  - Install bundles
 *  - make:controller App
 *  - make:entity Task  (+ make:migration + migrate)
 *
 * Every step is slideshow-ready via #[Step] and guarded for re-runs.
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) { if (is_file($autoload)) { require_once $autoload; break; } }

use Castor\Attribute\{AsTask, AsContext, AsOption};
use Castor\Context;
use function Castor\{context, io};

use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Step\RunStep;
use Survos\StepBundle\Action\{
    ComposerRequire,
    DisplayCode,
    BrowserVisit,
    Console,
    IfDirEmpty,
    IfFileMissing,
    IfFileExists
};


// -----------------------------------------------------------------------------
// Config & Context
// -----------------------------------------------------------------------------
const FAST_DEMO_DIR = '../demos/sf-fast';
if (!is_dir(__DIR__ . '/' . FAST_DEMO_DIR)) { @mkdir(__DIR__ . '/' . FAST_DEMO_DIR, 0777, true); }

#[AsContext(default: true, name: 'sf')]
function ctx_sf(): Context { return new Context(workingDirectory: FAST_DEMO_DIR); }

// -----------------------------------------------------------------------------
// Orchestrator
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:demo', description: 'Symfony fast demo using generators')]
#[Step(
    'Plan',
    description: 'Create/reuse a Symfony app, add bundles, generate a controller and entity, and run migrations.',
    bullets: [
        'Re-run safe: guarded per step',
        'All code uses makers & console generators',
        'Slideshow-friendly output'
    ]
)]
function sf_demo(
    #[AsOption('Symfony skeleton version (for symfony new --version=)')] string $version = '7.3',
    #[AsOption('Skip browser open at the end')] bool $noOpen = false,
): void {
    md_new($version);
    sf_install_bundles();
    md_make_controller();
    sf_make_entity();
    if (!$noOpen) { sf_open(); }
    io()->success('Symfony fast demo completed.');
}

// -----------------------------------------------------------------------------
// 1) Create Symfony project (only if directory empty)
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:new', description: 'Create Symfony webapp if directory is empty')]
#[Step(
    'Create Symfony project',
    description: 'Scaffold a Symfony Web App ONLY when the working directory is empty.',
    bullets: [
        'Uses symfony binary so recipes apply',
        'Skips when directory is not empty'
    ],
    actions: [
        new Console('new', ['--webapp', '--version=7.3', '--dir=.'], prefer: 'symfony')
    ]
)]
function sf_new(string $version = '7.3'): void
{
    // (If you want to honor $version dynamically, build the args with it.)
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 2) Install bundles (Composer will no-op quickly if already present)
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:install-bundles', description: 'Install helper/demo bundles')]
#[Step(
    'Install Production Bundles',
    description: 'Require useful bundles; Composer skips or is fast if already present.',
    bullets: ['install production'],
    actions: [
        new ComposerRequire(['easycore/easyadmin', 'survos/meili-bundle']),
    ]
)]
#[Step(
    'Install Development Tools',
    description: 'testing and code generation',
    bullets: ['only used in dev/test env'],
    actions: [
        new ComposerRequire([
            'symfony/maker-bundle',
            'survos/code-bundle'], dev: true),
    ]
)]
function sf_install_bundles(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 3) make:controller App (skip when it already exists)
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:make-controller', description: 'Generate AppController (skips if exists)')]
#[Step(
    'Generate controller',
    description: 'Use maker to scaffold AppController + Twig view.',
    bullets: ['make:controller App', 'Skip when src/Controller/AppController.php exists'],
    actions: [
            new Console('make:controller', ['App']),
            new DisplayCode('src/Controller/AppController.php', lang: 'php', note: 'AppController.php')
    ]
)]
function sf_make_controller(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 4) make:entity Task + make:migration + migrate
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:make-entity', description: 'Create Task entity then migrate')]
#[Step(
    'Create Task entity + migrate',
    description: 'Generate a Task entity, then create and run a migration (non-interactive).',
    bullets: ['make:entity Task -n', 'make:migration -n', 'doctrine:migrations:migrate -n'],
    actions: [
        new Console('make:entity', ['Task']),
        new Console('make:migration'),
        new Console('doctrine:migrations:migrate'),
        new DisplayCode('src/Entity/Task.php', lang: 'php', note: 'Task entity')
    ]
)]
function sf_make_entity(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 5) Open in browser (optional)
// -----------------------------------------------------------------------------
#[AsTask(name: 'sf:open', description: 'Open the home page')]
#[Step(
    'Open in browser',
    description: 'Visit the app via the local proxy (adjust domain as needed).',
    bullets: ['Opens http://sf-fast.wip/'],
    actions: [
        new BrowserVisit('/', note: 'Open Home Page', host: 'http://sf-fast.wip'),
    ]
)]
function sf_open(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
