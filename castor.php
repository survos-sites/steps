<?php
// File: castor.php (application root)

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',      // bundle-level
    __DIR__ . '/../../vendor/autoload.php',   // one level up
    __DIR__ . '/../../../vendor/autoload.php' // monorepo root
];

use function Castor\{import,variable,io,context,http_download,open};
use Castor\Attribute\AsListener;
use Castor\Event\AfterBootEvent;
use Castor\Event\FunctionsResolvedEvent;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

/** Auto-import Survos StepBundle JSONL listeners once per process. */
(function (): void {
    $candidates = [
        // vendor install
        __DIR__ . '/vendor/survos/step-bundle/src/Castor/SlideshowJsonlListeners.php',
        // monorepo layouts
        __DIR__ . '/packages/step-bundle/src/Castor/SlideshowJsonlListeners.php',
        __DIR__ . '/bundles/step-bundle/src/Castor/SlideshowJsonlListeners.php',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            import($path);
            break;
        }
    }
})();



//import('.castor/vendor/castor-php/php-qa/castor.php');
//import('composer://castor-php/php-qa', file: 'castor.php');
$listenerFilename = __DIR__ . '/functions/CastorListener.php';
assert(file_exists($listenerFilename), "Missing $listenerFilename");
import($listenerFilename);

import(__DIR__ . '/vendor/survos/step-bundle/src/Castor/CastorTaskLogger.php');


// Support CODE=basic (only one deck) or import all castor/*.castor.php
if ($code = ($_SERVER['CODE'] ?? $_ENV['CODE'] ?? null)) {
    import(__DIR__."/castor/{$code}.castor.php");
} else {
    foreach (glob(__DIR__.'/castor/*.castor.php') as $f) {
        import($f);
    }
}

#[AsTask('test:download')]
function testDownload()
{
    $url = 'http://eu-central-1.linodeobjects.com/speedtest/100MB-speedtest';
    $url = 'https://dummyjson.com/products';

    open($url);

    http_download($url, $local = pathinfo($url, PATHINFO_FILENAME), stream: true);
    io()->writeln("download to $local completed. " . filesize($local));


}


#[AsListener(AfterBootEvent::class)]
function add_option(AfterBootEvent $event)
{
    $definition = $event->application->getDefinition();
    $definition->addOption(new InputOption('foobar', description: "a global option set in " . __FILE__));
//    dd($definition);
    $event->application->setDefinition($definition);
}

#[AsListener(FunctionsResolvedEvent::class)]
function scope_tasks(FunctionsResolvedEvent $e)
{
    $deployTask = [];
    $regularTasks = [];
    foreach ($e->taskDescriptors as $taskDescriptor) {
        if ($taskDescriptor->taskAttribute instanceof AsDeployTask) {
            $deployTask[] = $taskDescriptor;
        } else {
            $regularTasks[] = $taskDescriptor;
        }
    }

    $deploy = variable('deploy', false);
    $e->taskDescriptors = $deploy ? $deployTask : $regularTasks;
    $e->symfonyTaskDescriptors = $deploy ? [] : $e->symfonyTaskDescriptors;
}

#[\Attribute(\Attribute::TARGET_FUNCTION)]
class AsDeployTask extends Castor\Attribute\AsTask
{
}

#[AsTask(name: 'bar', namespace: 'foo', description: 'Output foo bar')]
function a_very_long_function_name_that_is_very_painful_to_write(): void
{
    io()->writeln('Foo bar');
}

#[AsTask()]
function foo(): void
{
    $context = context();

    io()->writeln($context->workingDirectory); // will print the directory of the castor.php file

    $context = $context->withWorkingDirectory('/tmp'); // will create a new context where the current directory is /tmp
    run('pwd', context: $context); // will print "/tmp"
}
