<?php declare(strict_types=1);
/**
 * file: castor/barcode.castor.php — polymorphic actions version (PSR-4 actions)
 * - Uses Survos\StepBundle\Action\* (Bash, ComposerRequire, YamlWrite, etc.)
 * - Steps are attached via #[Step(...)] attributes
 * - Execution uses RunStep::run(_actions_from_current_task(), context())
 */

use Castor\Attribute\{ AsTask, AsContext, AsOption };
use Castor\Context;
use function Castor\{ context, io };

use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Action\{
    Bash,
    ComposerRequire,
    ImportmapRequire,
    YamlWrite,
    FileWrite,
    DisplayCode,
    BrowserVisit
};

const BARCODE_DEMO_DIR = '../demos/barcode-demo';

// Ensure the working directory exists (mkdir -p) once on import
(function () {
    $absDemo = __DIR__ . '/' . BARCODE_DEMO_DIR;
    if (!is_dir($absDemo)) {
        @mkdir($absDemo, 0777, true);
    }
})();

// -----------------------------------------------------------------------------
// Contexts
// -----------------------------------------------------------------------------
#[AsContext(default: true, name: 'barcode')]
function ctx_barcode(): Context { return new Context(workingDirectory: BARCODE_DEMO_DIR); }

#[AsContext(default: false, name: 'bundle')]
function ctx_bundle(): Context  { return new Context(workingDirectory: __DIR__ . '/..'); }

// -----------------------------------------------------------------------------
// Orchestrated demo
// -----------------------------------------------------------------------------

/** High-level orchestration */
#[AsTask(name: 'barcode:demo', description: 'Scaffold app, install Barcode bundle, add controller + Twig, (optionally) config & start')]
#[Step("Plan",
    description: "Create a Symfony web app (if empty), require Survos Barcode bundle, generate a controller and Twig demo, then start the server.",
    bullets: [
        "Scaffold Symfony webapp (if empty)",
        "composer require survos/barcode-bundle (+dev helper)",
        "Generate AppController + route '/'",
        "Add Twig demo using |barcode and barcode()",
        "Start the server & open the browser"
    ]
)]
function barcode_demo(
    #[AsOption('Symfony skeleton version (for symfony new --version=)')] string $version = '7.3',
    #[AsOption('Also write config/packages/barcode.yaml defaults')] bool $withConfig = true,
    #[AsOption('Skip starting the server/browser at the end')] bool $noStart = false,
): void {
    barcode_new($version);
    barcode_install();
    barcode_controller();
    barcode_twig();
    if ($withConfig) { barcode_config(); }
    if (!$noStart)   { barcode_start(); }
    io()->success('Barcode demo ready!');
}

// -----------------------------------------------------------------------------
// Subtasks
// -----------------------------------------------------------------------------

/** 1) Create app */
#[AsTask(name: 'barcode:new', description: 'Create Symfony webapp in the demo directory')]
#[Step('Create Symfony project',
    description: 'Create a fresh Symfony Web App here only if the directory is empty.',
    bullets: [
        'Ensure target directory exists',
        'Skip scaffolding when directory is not empty',
        'Use "symfony new --webapp" (recipes enabled)',
    ],
    actions: [
        new Bash('[[ $(ls -A . 2>/dev/null) ]] && echo "Directory not empty — skipping Symfony scaffolding." || symfony new --webapp --version=7.3 --dir=.', note: 'Run once in an empty directory'),
    ]
)]
function barcode_new(
    #[AsOption('Symfony version for symfony new --version=')] string $version = '7.3'
): void {
    RunStep::run(_actions_from_current_task(), context());
}

/** 2) Require bundles */
#[AsTask(name: 'barcode:install', description: 'Require survos/barcode-bundle and a dev helper')]
#[Step('Install bundles',
    description: 'Install Survos Barcode bundle and a dev helper via Composer.',
    bullets: ['composer req survos/barcode-bundle', 'composer req --dev survos/code-bundle'],
    actions: [
        new ComposerRequire(['survos/barcode-bundle']),
        new ComposerRequire(['survos/code-bundle'], dev: true),
    ]
)]
function barcode_install(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/** 3) Create controller + route */
#[AsTask(name: 'barcode:controller', description: 'Generate AppController and set route "/"')]
#[Step('Generate controller',
    description: 'Generate a basic AppController with route "/".',
    bullets: ['Scaffold AppController', 'Bind route /'],
    if: 'fs.workingDirIsEmpty',
    actions: [
        new Bash('php bin/console code:controller -m home -r home -p / App -f symfony.html', note: 'Generate AppController and route /'),
        new DisplayCode('src/Controller/AppController.php', lang: 'php', note: 'AppController.php'),
        new BrowserVisit('/', note: 'Open Home Page via Symfony proxy', host: 'http://barcode.wip'),
    ]
)]
function barcode_controller(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/** 4) Write demo Twig */
#[AsTask(name: 'barcode:twig', description: 'Write templates/app/index.html.twig using barcode filter + function')]
#[Step('Write demo Twig',
    description: 'Add a simple page that renders a barcode via filter and function.',
    bullets: ['Create templates/app/index.html.twig', 'Use |barcode and barcode() helpers'],
    actions: [
        new FileWrite(
            path: 'templates/app/index.html.twig',
            content: <<<'TWIG'
{% extends 'base.html.twig' %}
{% block body %}
  <h1>Barcode demo</h1>
  <p>Filter → {{ '0123456789'|barcode }}</p>
  <p>Function → {{ barcode('ABC-12345', 3, 80, '#e74c3c') }}</p>
{% endblock %}
TWIG
            ,
            note: 'Create demo template'
        ),
        new DisplayCode('templates/app/index.html.twig', lang: 'twig', note:'index.html.twig')
    ]
)]
function barcode_twig(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/** 5) Optional config */
#[AsTask(name: 'barcode:config', description: 'Write config/packages/barcode.yaml defaults')]
#[Step('Write barcode config',
    description: 'Provide default width/height/color for the barcode renderer.',
    bullets: ['widthFactor, height, color'],
    actions: [
        new YamlWrite(
            path: 'config/packages/barcode.yaml',
            data: [
                'barcode' => [
                    'widthFactor'     => 3,
                    'height'          => 120,
                    'foregroundColor' => '#6c5ce7',
                ]
            ],
            merge: false,
            note: 'Barcode defaults'
        ),
        new DisplayCode('config/packages/barcode.yaml', lang: 'yaml', note: 'config/packages/barcode.yaml')
    ]
)]
function barcode_config(
    #[AsOption('widthFactor')] int $widthFactor = 3,
    #[AsOption('height')] int $height = 120,
    #[AsOption('foregroundColor')] string $foregroundColor = '#6c5ce7',
): void {
    // (If you want to honor options dynamically, build the YamlWrite from args.)
    RunStep::run(_actions_from_current_task(), context());
}

/** 6) Start local server + open browser */
#[AsTask(name: 'barcode:start', description: 'Start Symfony local server (daemon) and open /')]
#[Step('Start server',
    description: 'Launch the Symfony local server and open the site.',
    bullets: ['symfony server:start -d', 'Open home page'],
    actions: [
        new Bash('symfony server:start -d', note:'Start server'),
        new BrowserVisit('/', note:'Open in browser', host:'http://barcode.wip'),
    ]
)]
function barcode_start(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
