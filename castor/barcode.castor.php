<?php declare(strict_types=1);

// file: castor/barcode.castor.php

/**
 * Castor 1.0 tasks to scaffold a tiny Barcode demo using survos/barcode-bundle.
 *
 * Usage (from bundle root):
 *   vendor/bin/castor barcode:demo
 *   # or step-by-step:
 *   vendor/bin/castor barcode:new
 *   vendor/bin/castor barcode:install
 *   vendor/bin/castor barcode:controller
 *   vendor/bin/castor barcode:twig
 *   vendor/bin/castor barcode:config
 *   vendor/bin/castor barcode:start
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',      // bundle-level
    __DIR__ . '/../../vendor/autoload.php',   // one level up
    __DIR__ . '/../../../vendor/autoload.php' // monorepo root
];

use function Castor\{fs,task};
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Metadata\Actions\{
    Section,
    Bash,
    Console,
    Composer,
    ComposerRequire,
    ImportmapRequire,
    RequirePackage,
    Env,
    YamlWrite,
    FileWrite,
    FileCopy,
    OpenUrl,
    DisplayCode,
    BrowserVisit,
    BrowserClick,
    BrowserAssert,
    ShowClass
};


foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

use Castor\Attribute\AsTask;
use Castor\Attribute\AsContext;
use Castor\Context;
use Castor\Attribute\AsOption;

use function Castor\context;
use function Castor\io;
use function Castor\run;

use Survos\StepBundle\Util\ArtifactWriter;

use Survos\StepBundle\Util\PathUtil;
use Survos\StepBundle\Metadata\Step;

const DEMO_DIR = '../demos/barcode-demo';

/* --------------------------------------------
| Context (Castor 1.0): run all tasks from DEMO_DIR
|---------------------------------------------*/
#[AsContext(default: true, name: 'barcode')]
function create_default_context(): Context
{
    return new Context(
        workingDirectory: DEMO_DIR
    );
}

#[AsContext(default: false, name: 'bundle')]
function create_bundle_context(): Context
{
    return new Context(
        workingDirectory: __DIR__ . '/..'
    );
}

/* --------------------------------------------
| Tiny helper to run a shell command in (possibly adjusted) context
|---------------------------------------------*/
function run_step(
    #[AsOption('Title to show before running the command')] string $title,
    #[AsOption('Shell command to execute')] string                 $command,
    ?string                                                        $cwd = null
): void
{
    $baseCtx = context();
    $baseWd = (string)$baseCtx->workingDirectory;
    $target = $cwd ? PathUtil::absPath($cwd, $baseWd) : PathUtil::absPath($baseWd, getcwd() ?: $baseWd);

    if (!is_dir($target)) {
        PathUtil::ensureDir($target);
    }

    $stepCtx = ($target === $baseWd) ? $baseCtx : $baseCtx->withWorkingDirectory($target);

    io()->section($title);
    io()->writeln(sprintf('<info>$ %s</info> (cwd: %s)', $command, $target));
    run($command, context: $stepCtx);
}

/* --------------------------------------------
| Orchestrator: full demo (mirrors README “proof it works”)
|---------------------------------------------*/
#[Step("Welcome.  We're going to do the following",
    bullets: [
        'uno (needs fade in!)',
        'two',
        'three',
    ]
)]
#[AsTask(name: 'barcode:demo', description: 'Create a fresh Symfony app, install barcode bundle, add Twig, config, and start the server')]
function barcode_demo(
    #[AsOption('Symfony skeleton version (passed to symfony new --version=)')] string $symfonyVersion = '7.3',
    #[AsOption('Write config/packages/barcode.yaml defaults')] bool                   $with_config = true,
    #[AsOption('Skip starting the server/browser at the end')] bool                   $no_start = false,
): void
{
    barcode_new($symfonyVersion);
    barcode_install();
    barcode_controller();
    barcode_twig();
    if ($with_config) {
        barcode_config();
    }
    if (!$no_start) {
        barcode_start();
    }
    io()->success('Barcode demo ready!');
}

/* --------------------------------------------
| 1) symfony new (create inside the context directory)
|---------------------------------------------*/
#[AsTask(name: 'barcode:new', description: 'Create Symfony webapp in the current context directory')]
#[Step('symfony new --webapp')]
function barcode_new(
    #[AsOption('Symfony version for symfony new --version=')] string $version = '7.3'
): void
{
    $dir = PathUtil::absPath((string)context()->workingDirectory, getcwd() ?: '.');

    // If directory is not empty, skip creation (idempotent)
    if (is_dir($dir) && count(scandir($dir) ?: []) > 2) {
        io()->warning("Directory '$dir' already contains a project; skipping symfony new.");
        return;
    }

    PathUtil::ensureDir($dir);
    run_step("Create Symfony $version webapp", "symfony new --webapp --version=$version --dir=.");
    io()->success("Symfony app created in '$dir'.");
}

/* --------------------------------------------
| 2) composer req survos/barcode-bundle
|---------------------------------------------*/
#[AsTask(name: 'barcode:install-bundles', description: 'Install survos/barcode-bundle via Composer')]
#[Step('install bundles',
    actions: [
        new ComposerRequire([
            new RequirePackage('survos/barcode-bundle')
        ]),
        new ComposerRequire([
            new RequirePackage('survos/code-bundle')
        ], dev: true),
    ]
)]
function barcode_install(): void
{
    RunStep::run();
//    run_step('Install BarcodeBundle', 'symfony composer req survos/barcode-bundle');
    io()->success('Bundle installed.');
}

# PATCH (edit the existing function body/attributes)
# file: castor/barcode.castor.php  — only the controller task/steps need changes

# Replace your current barcode_controller() attribute block with this:

#[AsTask(name: 'barcode:controller', description: 'Generate AppController and switch route path to "/"')]
#[Step('createController',
    actions: [
        new Console(
            'code:controller -m home -r home -p / App -f symfony.html',
            id: 'createHomeController',
            artifacts: [
                // snapshot the generated controller file as an artifact
                new \Survos\StepBundle\Metadata\Actions\Artifact(
                    'src/Controller/AppController.php',
                    'AppController.php',
                    'code/php'
                ),
            ]
        ),
    ],
)]
#[Step('show controller + homepage',
    actions: [
        // reflect via FQCN (pretty header, methods, etc.)
        new ShowClass('App\\Controller\\AppController', methods: ['__construct'], declarations: true),

        // render the artifact snapshot (not the live file)
        // Will be resolved to: public/artifacts/<safeTask>/<safeStep>/files/console-createhomecontroller/AppController.php
        new DisplayCode('artifact:createController::createHomeController::AppController.php', lang: 'php', note: 'AppController.php'),

        // open the app homepage
        new BrowserVisit('http://127.0.0.1:8000/', note: 'Open Home Page'),
    ],
)]
function barcode_controller(): void
{
    try {
        // Important: pass task() and context() so RunStep can bind artifacts properly
        RunStep::run(task(), context());
    } catch (\Throwable $e) {
        // io()->error($e->getMessage());
    }
}

/* --------------------------------------------
| 4) Write Twig demo template using the filter & function
|---------------------------------------------*/
#[AsTask(name: 'barcode:twig', description: 'Write templates/app/index.html.twig with barcode examples')]
function barcode_twig(): void
{
    $projectDir = PathUtil::absPath((string)context()->workingDirectory, getcwd() ?: '.');
    $tplPath = $projectDir . '/templates/app/index.html.twig';
    PathUtil::ensureDir(\dirname($tplPath));

    $tpl = <<<'TWIG'
{% extends 'base.html.twig' %}
{% block body %}
  <p>
    {# as a filter #}
    {{ 'test'|barcode }}
  </p>
  <p>
    {# as a function #}
    {{ barcode(random(), 2, 80, 'red') }}
  </p>
{% endblock %}
TWIG;

    file_put_contents($tplPath, $tpl);
    io()->success("Wrote $tplPath");
}

/* --------------------------------------------
| (Optional) 5) config/packages/barcode.yaml defaults
|---------------------------------------------*/
#[AsTask(name: 'barcode:config', description: 'Write config/packages/barcode.yaml with default width/height/color')]
function barcode_config(
    #[AsOption('widthFactor')] int        $widthFactor = 3,
    #[AsOption('height')] int             $height = 120,
    #[AsOption('foregroundColor')] string $foregroundColor = 'purple',
): void
{
    $projectDir = PathUtil::absPath((string)context()->workingDirectory, getcwd() ?: '.');
    $yamlPath = $projectDir . '/config/packages/barcode.yaml';
    PathUtil::ensureDir(\dirname($yamlPath));

    $yaml = <<<YAML
barcode:
  widthFactor: {$widthFactor}
  height: {$height}
  foregroundColor: '{$foregroundColor}'
YAML;

    file_put_contents($yamlPath, $yaml);
    io()->success("Wrote $yamlPath");
}

/* --------------------------------------------
| 6) Start local server and open browser
|---------------------------------------------*/
#[AsTask(name: 'barcode:start', description: 'Start Symfony local server (daemon) and open /')]
function barcode_start(): void
{
    run_step('Start Symfony local server', 'symfony server:start -d');
    run_step('Open browser', 'symfony open:local');
    io()->success('Server started and browser opened.');
}

/* --------------------------------------------
| Utility: show the context working directory
|---------------------------------------------*/
#[AsTask(name: 'barcode:ctx', description: 'Show current context workingDirectory')]
function barcode_ctx(): void
{
    $wd = PathUtil::absPath((string)context()->workingDirectory, getcwd() ?: '.');
    io()->success("Context workingDirectory: $wd");
}
