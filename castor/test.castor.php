<?php declare(strict_types=1);
// file: castor/test.castor.php — minimal artifact smoke test

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php'
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) { require_once $autoload; break; }
}

use Castor\Attribute\AsTask;
use Castor\Attribute\AsContext;
use Castor\Attribute\AsOption;
use Castor\Context;
use function Castor\{context, io, task};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;
use Survos\StepBundle\Util\PathUtil;
use Survos\StepBundle\Action\{Console,DisplayCode,FileWrite};
//use Survos\StepBundle\Metadata\Actions\{};

const TEST_DEMO_DIR = '../demos/test-demo';

/** Run all steps inside a sandbox dir */
#[AsContext(name: 'test-artifacts')]
function ctx(): Context
{
    return new Context(workingDirectory: TEST_DEMO_DIR);
}

/**
 * 1) Write a tiny PHP file
 * 2) Snapshot it as an artifact (Console with id: emitDemo)
 * 3) Render that artifact on the next slide
 */
#[AsTask(name: 'test:artifacts', description: 'Minimal artifact smoke test')]
#[Step('write demo class', "Write DemoController.php, we have the string just write it",
    bullets: [
        'sometimes we just want to cat a file to the CLI'
    ],
    actions: [
        // Create the directory and file; RunStep will also snapshot changed files into git/* if repo exists
        new FileWrite(
            path: 'src/Controller/DemoController.php',
            content: <<<'PHPX'
<?php
namespace App\Controller;

final class DemoController
{
    public function __construct(private string $name = 'world') {}

    public function greet(): string
    {
        return 'hello ' . $this->name;
    }
}
PHPX
        ),
    ]
)]
#[Step('snapshot + render',
    "a console command (echo okay, followed by displaying the code in the slideshow",
    bullets: [
        'just one bullet point'
    ],
    actions: [
        // Snapshot the freshly-written file as a DISPLAY artifact via a Console action
        // The id "emitDemo" gives us a predictable actionKey => "console-emitdemo"
        new Console(
            cmd: 'echo ok',
            id: 'emitDemo',
            artifacts: [
                new \Survos\StepBundle\Metadata\Actions\Artifact(
                    'src/Controller/DemoController.php',
                    'DemoController.php',
                    'code/php'
                ),
            ]
        ),

        // Render the artifact snapshot (NOT the live file) using the artifact: locator
        // Resolved to: /artifacts/<safeTask>/<safeStep>/files/console-emitdemo/DemoController.php
        new DisplayCode(
            target: 'artifact:snapshot + render::emitDemo::DemoController.php',
            lang: 'php',
            note: 'Artifact snapshot of DemoController.php'
        ),
    ]
)]
function test_artifacts(): void
{
    try {
        // Important: pass task() and context() so RunStep binds artifacts to the correct slide/task
        RunStep::run(task(), context(), options: ['mode' => getenv('CASTOR_MODE') ?: 'run']);
    } catch (\Throwable $e) {
        io()->error('Artifact test failed: ' . $e->getMessage());
        throw $e; // abort aggressively so we notice failures
    }
}
