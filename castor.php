<?php
// file: castor.php (application root)

declare(strict_types=1);

const SYMFONY_NAMESPACE = 'sy';
define('STEPS_PROJECT_DIR', __DIR__); // absolute path to the 'steps' Symfony app
function steps_project_dir(): string { return STEPS_PROJECT_DIR; }
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

use Castor\Attribute\{AsArgument,AsOption};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Service\CastorStepExporter;
use Survos\StepBundle\Util\ArtifactHelper;
use Survos\StepBundle\Attribute\{Arg,Opt};
use function Castor\{ run, import, variable, io, context, ask, load_dot_env };
use Castor\Attribute\{AsListener, AsTask, AsContext};
use Castor\{Context};
use Castor\Event\AfterBootEvent;
use Castor\Event\FunctionsResolvedEvent;
use Symfony\Component\Console\Input\InputOption;

try {
    import('.castor/vendor/tacman/castor-tools/castor.php');
} catch (Throwable $e) {
    io()->error("castor composer install");
    io()->error($e->getMessage());
}

const ARTIFACT_ROOT = CastorStepExporter::ARTIFACT_ROOT;

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
            // Avoid re-importing the same file
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

//function artifacts_dir(): string { return CastorStepExporter::artifactsDir(project_name()); }
function artifacts_dir(): string { return CastorStepExporter::ARTIFACT_ROOT . project_name() . '/'; }
function artifact_path(string $path): string { return artifacts_dir() .  $path; }
function artifact(string $path): string { return file_get_contents(artifact_path($path)); }

$exporter = new CastorStepExporter(__DIR__);
$artifactsDir = $exporter->artifactsDir(project_name());
// -----------------------------------------------------------------------------
// Load castor decks
//   - Support export CODE=barcode to import a single deck
//   - Otherwise load all castor/*.castor.php
// -----------------------------------------------------------------------------
$castorFileFromEnv = null;
if (project_name()) {
    $file = __DIR__ . sprintf("/castor/%s.castor.php", project_name());
    if (!is_file($file)) {
        fwrite(STDERR, "Missing deck: {$file}\n");
        exit(1);
    }
    $castorFileFromEnv = $file;
    $_ENV['CASTOR_FILE'] = $file;
//    variable('castorFile', $file);
//    context()->var['castorFile'] = $file;
    import($file);
} else {
    assert(false, "The steps project requires that you set a CODE env var.");
    foreach (glob(__DIR__ . '/castor/*.castor.php') ?: [] as $f) {
        import($f);
    }
}



function project_name(): ?string { return $_SERVER['CODE']??assert(false, "run\n\nexport CODE=xx\n\nfirst"); }
function demo_name(): string { return project_name() . '-demo'; }
#[AsContext(default: true)] function ctx(): Context { return new Context(workingDirectory: '../demo/' . demo_name()); }

#[AsTask('rec', null, "Start ciine")]
function rec(
    #[Arg('dir to record')] ?string $dir=null,
    #[Arg('title to embed')] ?string $title=null,

): void {
    $ctx = context()->withWorkingDirectory(__DIR__ . '/public'); // hack, we know __DIR__ exists

    $dir = 'public/casts';
    $filename = '$(date +%Y-%m-%d_%H-%M-%S)'; // the bash pattern
    $filename = 'step1';
    if (!$dir) {
        $dir = io()->ask("What is the directory?");
    }
    run("ciine rec $dir/{$filename}.cast --overwrite --capture-input -i 1 --title='$title'", context: $ctx);
    io()->writeln("<fg=green;options=bold>REC is running.</>");
}

//local name="$1"
//  shift
//  local title="$*"
//  local dir="casts/$name"
//  mkdir -p "$dir"
//  ciine rec "$dir/$(date +%Y-%m-%d_%H-%M-%S).cast" --capture-input -i 1 --title="$title"
//}

// -----------------------------------------------------------------------------
// Sample tasks & global listeners
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

// -----------------------------------------------------------------------------
// Sample tasks & global listeners (unchanged, tidied)
// -----------------------------------------------------------------------------

#[AsTask('server', namespace: SYMFONY_NAMESPACE,
    aliases: ['start'],
    description: 'Wrapper for symfony server:start -d')]
function symfony_server(
    #[AsOption()] ?bool $stop=null,
    #[AsOption()] bool $start=true,
): void
{
    $start && run("symfony server:start -d");
}

#[AsTask('new', namespace: SYMFONY_NAMESPACE,
    description: 'Wrapper for symfony new --webapp')]
function symfony_new(
    #[Opt('purge directory first, no prompt')] bool $purge = false,
    #[Arg("The context code, but not --context!")] ?string $code=null
): void
{
    $demoDir = context()->workingDirectory;
    $ctx = context()->withWorkingDirectory(__DIR__); // hack, we know __DIR__ exists

    if (file_exists($demoDir)) {
        if ($purge = io()->confirm("purge $demoDir?")) {
            dump("rm -rf $demoDir");
            $output = run("rm -rf $demoDir", $ctx);
            io()->write($output->getOutput());
        }
//        io()->warning("The demo directory '{$demoDir}' already exists.");
//        return;
    }
    $exporter = new CastorStepExporter();
    dump($demoDir);
    // we need the working directory of the context, but it doesn't yet exist!
//    dump($demoDir, $ctx);
//    run("mkdir $demoDir -p && echo 'hello!' > $demoDir/README.md",
//        new Context()->withWorkingDirectory(__DIR__));
//    $output = run("cat README.md"); // relative to project working dir
    run('symfony new --webapp '. $demoDir, $ctx);
    // now we can use the demo working dir
    run('composer config extra.symfony.allow-contrib true');

    // NOW we can run commands in the project context
    run('symfony proxy:domain:attach ' . project_name());
    run("symfony server:start -d");
    //        new Bash('symfony server:start -d'),

    io()->success('Symfony project now available in ' . $demoDir);
}

#[AsTask('open',  namespace: SYMFONY_NAMESPACE,
    aliases: ['open'],
    description: 'Wrapper for symfony open:local')]
function symfony_local(
    #[\Castor\Attribute\AsArgument("path relative to server, default to /")] string $path='/'
): void
{
    run($cmd = 'symfony open:local --path '. $path);
}
#[AsTask('clear:cache',  namespace: SYMFONY_NAMESPACE,
    aliases: ['c:cl'],
    description: 'symfony cache:clear && cache:pool:clear --all')]
function symfony_clear_cash(
    #[\Castor\Attribute\AsArgument("path relative to server, default to /")] string $path='/'
): void
{
    run('symfony console cache:clear && symfony console cache:pool:clear --all');
}

#[AsTask('slideshow', description: 'Open a slideshow in the browser')]
function symfony_slideshow(
      #[Arg("path relative to server, default to /")] ?string $path=null,
      #[AsOption(null, "list the slides instead of navigating through them")] bool $list = false
): void
{
    if (!$path) {
        // the context must be _this_ repo, not the created symfony project
        $path = sprintf('/steps/step/slides%s/%s', $list ? '-overview' : '',  project_name());
    }
    run($cmd = 'symfony open:local --path '. $path, new Context()->withWorkingDirectory(__DIR__));
//    run($cmd = 'symfony open:local --path '. $path, new Context()->withWorkingDirectory(__DIR__));
}

#[AsTask('start', description: 'Wrapper for symfony start')]
function symfony_start(
): void
{
    run('symfony server:start -d');
}

#[AsListener(AfterBootEvent::class)]
function add_option(AfterBootEvent $event): void
{
    if ($contextCode = $_ENV['CASTOR_CODE'] ?? null) {
         context($contextCode);
    }
//    dd(context()->workingDirectory);
//        $env = load_dot_env();
//        io()->writeln($_SERVER['DATABASE_URL'] ?? throw new \RuntimeException('DATABASE_URL is not defined'));
//        dd($_SERVER, $_ENV);
    $definition = $event->application->getDefinition();
    $definition->addOption(new InputOption('dry', description: "Dry run " . basename(__FILE__)));
//    $definition->addOption(new InputOption('castor-file', description: "for tasks, etc. ", default: $contextCode ? "castor/$contextCode.php": null));
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

#[AsTask(name: 'tasks', namespace: '', description: 'Ordered list of tasks from a .castor file')]
function tasks(
    #[AsArgument(description: 'The .castor file')] ?string $castorFile=null
): int
{
    // idea: move this to step-bundle and use classes and $exporter->exportSlides()
    if (!$castorFile) {
        $castorFile = $_ENV['CASTOR_FILE'];
    }
//    $code = $_SERVER['CODE'] ?? throw new \RuntimeException('CODE is not defined');
//    $exporter = new CastorStepExporter(__DIR__);
//
//    $slides = $exporter->exportSlides($code, $castorFile);
//    dd($slides);

    $io = io();
    $table = new \Symfony\Component\Console\Helper\Table($io);
    $table->setHeaders(['function', 'Task', 'Description']);
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
                    foreach ($step->actions as $action) {
                        // too messy!
                        if (false && property_exists($action, 'a')) {
                            $artifactId = $action->a;
                            $taskName =  $asTaskAttr->name;
                            dump($action, $step,$taskName);
                            $wd = __DIR__; // hack
                            $helper =  new ArtifactHelper($wd, ArtifactHelper::safe($taskName), ArtifactHelper::safe('step'));
                            dd($helper->baseDir());
                            $abs = artifact_path($relativePath);

                            dd($wd, $helper->path($artifactId));
                        }
                    }

                    $taskName = $attributes['taskName'] ?? '';


                    $steps[] = [
                        'description' => $step->description ?? 'No description',
                        'actions' => $step->actions ?? []
                    ];


                }

                $tasks[] = [
                    'function' => $rf->getName(),
                    'name' => ($asTaskAttr->namespace ? $asTaskAttr->namespace . ':' : '') .
                        $asTaskAttr->name ?? $fnName,
                    'description' => $asTaskAttr->description ?? 'No description',
                    'steps' => $steps
                ];
            }
        }
    }

    $commandStyle = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('white', 'black');
    io()->getFormatter()->setStyle('bash', $commandStyle);

// Display the results
    foreach ($tasks as $task) {
        $description = $task['description'];
//        io()->writeln("{$task['name']} {$task['description']}");

//        $io = io();
//        $io->block([
//            'composer require symfony/console',
//            'php bin/console cache:clear',
//        ], 'BASH', 'fg=white;bg=black', ' $ ', true);

//        // Or using section
//        $io->section('Run these commands:');
//        $io->listing([
//            'composer install',
//            'npm run build',
//            'php artisan migrate',
//        ]);
//
//        // For multi-line code blocks
//        $phpCode = <<<'PHP'
//    <?php
//
//    class Example {
//        public function method(): void
//        {
//            echo "Hello World";
//        }
//    }
//    PHP;
//
//        // Display with a visual separator
//
//        $io->writeln('');
//        $io->writeln('<fg=black;bg=white> PHP Code </>');
//        $io->writeln('<comment>' . $phpCode . '</comment>');
//        $io->writeln('');
//
//
//        // Simple approach with comment style
//        io()->writeln('<comment>composer install</comment>');
//        io()->writeln('<comment>php artisan migrate</comment>');

        foreach ($task['steps'] as $index => $step) {
            if ($stepDescription = $step['description']) {
                // the _task_ description
                io()->writeln('## ' . $stepDescription);
                $description .= "\n\n" . $stepDescription;

            foreach ($step['actions'] as $action) {
                if (property_exists($action, 'note')) {
                    if ($stepDescription = $action->note) {
                        io()->writeln(sprintf("<comment>%s</comment>", '# ' . $stepDescription));
                    }
                }
                if (io()->isVerbose()) {
                    $description .= sprintf("\n<bash>%s</bash>", $action->toCommand() ?? $action::class);
                }
//                dump($action->toCommand()??$action::class);
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
        }
        $table->addRow([
            $task['function'], $task['name'], $description]);

    }
    $table->render();


    return \Symfony\Component\Console\Command\Command::SUCCESS;
}

