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

use Castor\Attribute\AsArgument;
use Survos\StepBundle\Renderer\DebugActionRenderer;
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
        __DIR__ . '/bundles/step-bundle/src/Castor/Renderer/DebugActionRenderer.php',

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
    $definition->addOption(new InputOption('dry', description: "Dry run " . basename(__FILE__)));
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

#[AsTask(name: 'tasks', namespace: '', description: 'Ordered list of tasks from a .castor file')]
function tasks(
    #[AsArgument(description: 'The .castor file')] string $castorFile
): int
{
    if (!file_exists($castorFile)) {
        $castorFile = __DIR__ . "/castor/{$castorFile}.castor.php";
    }
    if (!file_exists($castorFile)) {
        io()->error("Missing castor file: {$castorFile}");
        return \Symfony\Component\Console\Command\Command::FAILURE;
    }
    // Include the castor file and detect only the functions it declares, then walk through those.
    $before = get_defined_functions()['user'] ?? [];
    $targetFile = realpath($castorFile); // Calculate once instead of in loop
    $tasks = [];
    foreach ($before as $fnName) {
        $rf = new \ReflectionFunction($fnName);
        $functionFile = $rf->getFileName();

        if ($functionFile && realpath($functionFile) === $targetFile) {
            $attributes = $rf->getAttributes(AsTask::class);

            // Only process functions with AsTask attribute
            if (!empty($attributes)) {
                $asTaskAttr = $attributes[0]->newInstance();

                // Get Step attributes
                $stepAttrs = $rf->getAttributes(\Survos\StepBundle\Metadata\Step::class);
                $steps = [];

                foreach ($stepAttrs as $stepAttr) {
                    $step = $stepAttr->newInstance();
                    $steps[] = [
                        'description' => $step->description ?? 'No description',
                        'actions' => $step->actions ?? []
                    ];
                }

                $tasks[] = [
                    'name' => $asTaskAttr->name ?? $fnName,
                    'description' => $asTaskAttr->description ?? 'No description',
                    'steps' => $steps
                ];
            }
        }
    }

// Display the results
    foreach ($tasks as $task) {
        io()->writeln("{$task['name']} {$task['description']}");
        foreach ($task['steps'] as $index => $step) {
            if ($description = $step['description']) {

            }

            foreach ($step['actions'] as $action) {
                dump($action->toCommand()??$action::class);
//                // we do all this already in DebugActionRenderer
//
//                $display = match (get_class($action)) {
//                    \Survos\StepBundle\Action\Console::class => $action->consolePath . ' ' . $action->command,
//                    \Survos\StepBundle\Action\Bash::class => $action->command,
//                    \Survos\StepBundle\Action\ComposerRequire::class => function(\Survos\StepBundle\Action\ComposerRequire $action)  {
//                        dd($action->packages);
//                },
//
//                    \Survos\StepBundle\Action\BrowserVisit::class => "open " . $action->path,
//                    default => get_class($action),
//                };
//                io()->writeln("    - {$display}");

            }
        }
        echo "\n";
    }

    return \Symfony\Component\Console\Command\Command::SUCCESS;
}

