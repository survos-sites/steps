<?php declare(strict_types=1);
/**
 * file: castor/meili.castor.php
 *
 * A short, fast, re-runnable Symfony demo using meili that leans on generators:
 *  - Create project (only if directory is empty)
 *  - Install bundles
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
use function Castor\{context, io, fs};

use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Step\RunStep;
use Survos\StepBundle\Action\{
    ComposerRequire,
    DisplayCode,
    BrowserVisit,
    Console,
    Bash,
    IfDirEmpty,
    IfFileMissing,
    IfFileExists
};

// -----------------------------------------------------------------------------
// Config & Context
// -----------------------------------------------------------------------------
const MEILI_DEMO_DIR = '../demos/meili';
if (!is_dir(__DIR__ . '/' . MEILI_DEMO_DIR)) { @mkdir(__DIR__ . '/' . MEILI_DEMO_DIR, 0777, true); }

#[AsContext(default: true, name: 'sf')]
function ctx_meili(): Context { return new Context(workingDirectory: MEILI_DEMO_DIR); }

// -----------------------------------------------------------------------------
// Orchestrator
// -----------------------------------------------------------------------------
#[AsTask(name: 'md:demo', description: 'Symfony Amsterdam Conference')]
#[Step(
    'Symfony + Meilisearch',
    description: 'Create a Symfony application using Meilisearch',
    bullets: [
        'makers & console generators',
        'leverage easyadmin',
        'fast, beautiful UI',
    ]
)]
#[Step(
    'Pre-requisites',
    bullets: [
        'Symfony CLI',
        'PHP 8.4+',
        'Symfony ^7.3|^8.0',
        'meilisearch',
        'OpenAI Api Key',
        'database extensions (sqlite for demo)',
    ]
)]
function md_demo(
    #[AsOption('Symfony skeleton version (for symfony new --version=)')] string $version = '7.3',
    #[AsOption('Skip browser open at the end')] bool $noOpen = false,
): void {
    md_new($version);
    md_install_bundles();
    md_make_controller();
    sf_make_entity();
    if (!$noOpen) { sf_open(); }
    io()->success('Symfony fast demo completed.');
}

// -----------------------------------------------------------------------------
// 1) Create Symfony project (only if directory empty)
// -----------------------------------------------------------------------------
#[AsTask(name: 'md:new', description: 'Create Symfony webapp if directory is empty')]
#[Step(
    'Create Symfony project',
    description: 'Scaffold a Symfony Web App ONLY when the working directory is empty.',
    bullets: [
        'Uses symfony binary so recipes apply',
        'Skips when directory is not empty'
    ],
    actions: [
        new Bash('symfony new --webapp --dir=.')
    ]
)]
function md_new(string $version = '7.3'): void
{
    if (!fs()->exists(context()->workingDirectory)) {
        fs()->mkdir(context()->workingDirectory);
    }
    // (If you want to honor $version dynamically, build the args with it.)
    RunStep::run(_actions_from_current_task(), context());
}

// -----------------------------------------------------------------------------
// 2) Install bundles (Composer will no-op quickly if already present)
// -----------------------------------------------------------------------------
#[AsTask(name: 'md:bundles', description: 'Install helper/demo bundles')]
#[Step(
    'Install Production Bundles',
    description: 'Require useful bundles; Composer skips or is fast if already present.',
    bullets: ['install production'],
    actions: [
        new ComposerRequire(['easycorp/easyadmin-bundle', 'survos/meili-bundle']),
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
function md_install_bundles(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'md:data', description: 'Download data files')]
#[Step(
    'fetch and load csv files',
    bullets: ['fetch from github', 'create draft entity from headers'],
    actions: [
        new Bash('wget https://raw.githubusercontent.com/sanjeevai/Investigate_a_dataset/master/tmdb-movies.csv -O movies.csv'),
        new Console('code:entity Movie --file movies.csv'),
        new DisplayCode('src/Entity/Movie.php', lang: 'php', note: 'doctrine entity')
    ]
)]
function md_make_controller(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask(name: 'md:controller', description: 'Generate AppController (skips if exists)')]
#[Step(
    'Generate controller',
    description: 'Use maker to scaffold AppController + Twig view.',
    bullets: ['make:controller App', 'Skip when src/Controller/AppController.php exists'],
    actions: [
            new Console('make:controller', ['App']),
            new DisplayCode('src/Controller/AppController.php', lang: 'php', note: 'AppController.php')
            new DisplayCode('src/Controller/AppController.php', lang: 'php', note: 'AppController.php')
    ]
)]
function md_make_controller(): void { RunStep::run(_actions_from_current_task(), context()); }

// -----------------------------------------------------------------------------
// 4) make:entity Task + make:migration + migrate
// -----------------------------------------------------------------------------
#[AsTask(name: 'md:make-entity', description: 'Create Task entity then migrate')]
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
#[AsTask(name: 'md:open', description: 'Open the home page')]
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
