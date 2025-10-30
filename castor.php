<?php
// file: castor.php (application root)

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Autoload
// -----------------------------------------------------------------------------
$autoloadCandidates = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) { require_once $autoload; break; }
}

use function Castor\{ run, import, variable, io, context };
use Castor\Attribute\AsListener;
use Castor\Attribute\AsTask;
use Castor\Event\AfterBootEvent;
use Castor\Event\FunctionsResolvedEvent;
use Symfony\Component\Console\Input\InputOption;

// -----------------------------------------------------------------------------
// Import Survos StepBundle castor utilities (listeners + helpers) once
// -----------------------------------------------------------------------------
(function (): void {
    $candidates = [
        // JSONL listeners (logger)
        __DIR__ . '/vendor/survos/step-bundle/src/Castor/SlideshowJsonlListeners.php',
        __DIR__ . '/packages/step-bundle/src/Castor/SlideshowJsonlListeners.php',
        __DIR__ . '/bundles/step-bundle/src/Castor/SlideshowJsonlListeners.php',

        // Step helpers (global functions, e.g., _actions_from_current_task)
        __DIR__ . '/vendor/survos/step-bundle/src/Castor/StepHelpers.php',
        __DIR__ . '/packages/step-bundle/src/Castor/StepHelpers.php',
        __DIR__ . '/bundles/step-bundle/src/Castor/StepHelpers.php',
    ];

    $loaded = [];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            // Avoid re-importing the same file twice
            if (!in_array($path, $loaded, true)) {
                import($path);
                $loaded[] = $path;
            }
        }
    }
})();

// -----------------------------------------------------------------------------
// Your local listeners (optional)
// -----------------------------------------------------------------------------
$listenerFilename = __DIR__ . '/functions/CastorListener.php';
if (is_file($listenerFilename)) {
    import($listenerFilename);
}

// -----------------------------------------------------------------------------
// Load castor decks
//   - Support CODE=barcode to import a single deck
//   - Otherwise load all castor/*.castor.php
// -----------------------------------------------------------------------------
if ($code = ($_SERVER['CODE'] ?? $_ENV['CODE'] ?? null)) {
    $file = __DIR__ . "/castor/{$code}.castor.php";
    if (!is_file($file)) {
        fwrite(STDERR, "Missing deck: {$file}\n");
        exit(1);
    }
    import($file);
} else {

    foreach (glob(__DIR__ . '/castor/*.castor.php') ?: [] as $f) {
        import($f);
    }
}

// -----------------------------------------------------------------------------
// Sample tasks & global listeners (unchanged, tidied)
// -----------------------------------------------------------------------------
#[AsTask('sanity', description: 'Sanity: can Castor run() spawn a process?')]
function sanity(): void
{
    io()->writeln('About to run ls (tokenized array)…');
    run(['ls', '-lh'], context());

    io()->writeln('About to run bash -lc "echo …" (string)…');
    run('bash -lc "echo HI_FROM_CASTOR && sleep 1 && echo DONE"', context());

    io()->success('Completed spawn test.');
}

#[AsListener(AfterBootEvent::class)]
function add_option(AfterBootEvent $event): void
{
    $definition = $event->application->getDefinition();
    $definition->addOption(new InputOption('foobar', description: "A global option added in " . basename(__FILE__)));
    $event->application->setDefinition($definition);
}

#[AsListener(FunctionsResolvedEvent::class)]
function scope_tasks(FunctionsResolvedEvent $e): void
{
    $deployTask = [];
    $regularTasks = [];
    foreach ($e->taskDescriptors as $td) {
        if ($td->taskAttribute instanceof AsDeployTask) {
            $deployTask[] = $td;
        } else {
            $regularTasks[] = $td;
        }
    }
    $deploy = variable('deploy', false);
    $e->taskDescriptors        = $deploy ? $deployTask : $regularTasks;
    $e->symfonyTaskDescriptors = $deploy ? []         : $e->symfonyTaskDescriptors;
}

#[\Attribute(\Attribute::TARGET_FUNCTION)]
class AsDeployTask extends Castor\Attribute\AsTask {}

// Small example tasks
#[AsTask(name: 'foo', description: 'Show CWD juggling')]
function foo(): void
{
    $ctx = context();
    io()->writeln($ctx->workingDirectory);
    $ctx = $ctx->withWorkingDirectory('/tmp');
    run('pwd', context: $ctx);
}
