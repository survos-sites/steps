<?php declare(strict_types=1);

/**
 * file: castor/ez-products.castor.php
 * Demo orchestration for "EZ Products" with Step/Action attributes
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) { if (is_file($autoload)) { require_once $autoload; break; } }

use Castor\Attribute\{AsTask, AsArgument, AsContext};
use Castor\Context;
use function Castor\{variable, context};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Action\{
    Artifact,
    ComposerRequire,
    Env,
    Console,
    Bash,
    CopyFile,
    DisplayCode,
    BrowserVisit
};

const PRODUCT_SKELETON_PATH = 'vendor/survos/ez-bundle/app';
const PRODUCT_DEMO_DIR = '../demos/products';

#[AsContext(name: 'ezp')]
function ctx_ezp(): Context { return new Context(workingDirectory: PRODUCT_DEMO_DIR); }

#[AsTask('new', namespace: 'ezp', description: 'Create Symfony project using the Symfony CLI')]
#[Step(
    bullets: [
        '--webapp install common packages'
    ],
    actions: [
        new Bash('symfony new --webapp --dir=' . PRODUCT_DEMO_DIR)
    ]
)]
function ezp_new(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * setup — require bundles, create dirs, start server
 */
#[AsTask('setup', description: 'Setup bundles and directories, start server')]
#[Step(
    'Install packages & prepare folders',
    description: 'Require needed bundles, ensure src/* folders exist, and start the Symfony server.',
    bullets: [
        'require bundles:  qr-code, ez, easyadmin, ux_icons',
        'Create src/{Command,Entity,Repository} and templates/',
        'Start the local server in the background',
    ],
    actions: [
        new ComposerRequire(['endroid/qr-code-bundle','survos/ez-bundle']),
        new ComposerRequire(['easycorp/easyadmin-bundle','symfony/ux-icons']),
        new Bash('symfony server:start -d'),
    ]
)]
function setup(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * ez — copy admin controllers from the skeleton and warm cache
 */
#[AsTask('ez', description: 'Setup easyadmin as landing page -- readonly')]
#[Step(
    'Copy EasyAdmin controllers (skeleton → app)',
    bullets: [
        'Create src/{Command,Entity,Repository} and templates/',
    ],
    description: 'Copy starter Admin controllers from the bundle skeleton and clear cache.',
    actions: [
        new Bash('mkdir -p src/Command src/Entity src/Repository templates'),
        new Bash('mkdir -p src/Controller/Admin'),
        // Copy the entire Admin controller dir from the skeleton (safe for demo)
        new Bash('cp -R ' . PRODUCT_SKELETON_PATH . '/src/Controller/Admin/ src/Controller/Admin/'),
        new Console('cache:clear'),
        new DisplayCode('src/Controller/Admin/MeiliDashboardController.php', lang: 'php', note: 'If present in skeleton'),
    ]
)]
function easyadmin(): void { RunStep::run(_actions_from_current_task(), context()); }

#[AsTask('show_classes', description: 'Show the classes')]
#[Step(
    bullets: [
        'Create src/{Command,Entity,Repository} and templates/',
    ],
    description: 'Copy starter Admin controllers from the bundle skeleton and clear cache.',
    actions: [
        new Artifact('src/Controller/Admin/DashboardController.php', 'DashboardController.php'),

//        new DisplayCode(PRODUCT_SKELETON_PATH . '/src/Controller/Admin/DashboardController.php',
//            lang: 'php', note: 'If present in skeleton'),
    ]
)]
function show_classes(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
/**
 * database — configure SQLite and build schema
 */
#[AsTask('database', description: 'Configure and initialize database')]
#[Step(
    'Configure SQLite & build schema',
    description: 'Write DATABASE_URL to .env.local (if missing) and update Doctrine schema.',
    bullets: [
        'Use SQLite at var/data.db for zero-setup demo',
        'Run doctrine:schema:update with --force',
    ],
    actions: [
        new Env('DATABASE_URL', [
            'default' => 'sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db'
        ], '.env.local'),
        new Console('doctrine:schema:update', ['--force','--dump-sql']),
        new DisplayCode('.env.local', lang: 'env'),
    ]
)]
function database(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * copy-files — bring specific demo files from skeleton
 */
#[AsTask('copy-files', description: 'Copy demo files from bundle to app')]
#[Step(
    'Copy demo entities, command, templates & config',
    description: 'Grab a few representative files from the bundle skeleton so the demo can run.',
    bullets: [
        'config/packages/ux_icons.yaml',
        'src/Entity/Product.php + Repository',
        'src/Command/LoadCommand.php',
        'templates/product.html.twig',
        'Clear cache to pick up config',
    ],
    actions: [
        new Bash('mkdir -p config/packages src/Entity src/Repository src/Command templates'),
//        new CopyFile(getSkeletonPath() . '/config/packages/ux_icons.yaml',              'config/packages/ux_icons.yaml'),
//        new CopyFile(getSkeletonPath() . '/src/Entity/Product.php',                    'src/Entity/Product.php'),
//        new CopyFile(getSkeletonPath() . '/src/Repository/ProductRepository.php',      'src/Repository/ProductRepository.php'),
//        new CopyFile(getSkeletonPath() . '/src/Command/LoadCommand.php',               'src/Command/LoadCommand.php'),
//        new CopyFile(getSkeletonPath() . '/templates/product.html.twig',               'templates/product.html.twig'),
        new Console('cache:clear'),
        new Artifact('src/Entity/Product.php', 'Product.php'),
        new DisplayCode('templates/product.html.twig', lang: 'twig'),
    ]
)]
function copy_files(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * load — import sample data
 */
#[AsTask('load', description: 'load dummyproducts data')]
#[Step(
    'Import sample products',
    description: 'Run the demo data loader so EasyAdmin screens have content.',
    actions: [
        new Console('app:load'),
    ]
)]
function load_data(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * open — open site in browser at a specific path
 */
#[AsTask('open', description: 'Start web server and open in browser')]
#[Step(
    'Open local site',
    description: 'Launch the app at the requested path using the Symfony CLI.',
    actions: [
        new Bash('symfony open:local --path=${path}'),
        new BrowserVisit('${path}', host: 'http://127.0.0.1:8000'),
    ]
)]
function open(#[AsArgument] string $path = '/'): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * build — one-shot: run all steps in order
 */
#[AsTask('build', description: 'Complete demo setup (all steps)')]
#[Step(
    'One-shot demo build',
    description: 'Run each step in sequence to get a working demo with data.',
    bullets: ['setup → copy-files → ez → database → load → open'],
    actions: [
        new Console('castor setup'),
        new Console('castor copy-files'),
        new Console('castor ez'),
        new Console('castor database'),
        new Console('castor load'),
        new Console('castor open'),
    ]
)]
function build(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * clean — remove generated files and reset
 */
#[AsTask('clean', description: 'Remove generated files and reset')]
#[Step(
    'Clean demo artifacts',
    description: 'Remove generated PHP/Twig files and the SQLite DB.',
    bullets: ['Safe to re-run build after this'],
    actions: [
        new Bash('rm -f src/Entity/Product.php src/Repository/ProductRepository.php src/Command/LoadCommand.php'),
        new Bash('rm -f templates/products.html.twig var/data.db'),
    ]
)]
function clean(): void { RunStep::run(_actions_from_current_task(), context()); }

/**
 * ngrok — expose local server (simple default)
 */
#[AsTask('ngrok', description: 'Start ngrok tunnel to local Symfony server')]
#[Step(
    'Expose locally via ngrok',
    description: 'Open a public HTTPS URL for your running Symfony server.',
    bullets: [
        'Requires `ngrok` installed & authenticated',
        'Defaults to 127.0.0.1:8000; adjust flags if needed',
    ],
    actions: [
        new Bash('ngrok http 127.0.0.1:8000'),
    ]
)]
function ngrok(
    #[AsArgument] string $subdomain = '',
    #[AsArgument] string $region = '',
    #[AsArgument] bool $inspect = true
): void { RunStep::run(_actions_from_current_task(), context()); }

