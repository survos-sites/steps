<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',      // bundle-level
    __DIR__ . '/../../vendor/autoload.php',   // one level up
    __DIR__ . '/../../../vendor/autoload.php' // monorepo root
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

use Castor\Attribute\AsTask;
use function Castor\{context, io, import, fs,task};
use Survos\StepBundle\Runtime\Runtime;
use Survos\StepBundle\Runtime\Actions\PhpArtifact;
use Survos\StepBundle\Runtime\Actions\TwigArtifact;
use Survos\StepBundle\Runtime\Actions\ScreenshotArtifact;

#[AsTask('artifact:create', CASTOR_NAMESPACE, 'create ls and tree artifacts, display tree')]
#[Step(
    bullets: [
        'list',
        'display'
    ],
    actions: [
        new Bash('ls -lh', a: 'easy-artifacts/ls.terminal'),
        new Bash('tree --gitignore', a: 'easy-artifacts/tree.terminal'),
        new DisplayArtifact('easy-artifacts/tree.terminal'),
    ]
)]
function ea_artifact(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask('artifact:display', CASTOR_NAMESPACE, 'display ls.terminal artifact')]
#[Step(
    actions: [
        new DisplayArtifact('easy-artifacts/ls.terminal'),
    ]
)]
function display_artifact(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

#[AsTask(name: 'demo:artifacts', description: 'Emit artifacts for controller + twig and a screenshot, then commit')]
function demo_artifacts(): void
{
    io()->warning("These might be old");
    $projectDir = context()->workingDirectory ?? getcwd();
    $rt = new Runtime($projectDir);

    // Slide 1: PHP class file (no parsing now; slides can reflect later)
    (new PhpArtifact(\App\Controller\AppController::class))->__invoke($rt);

    // Slide 2: Twig template
    (new TwigArtifact('app/home.html.twig'))->__invoke($rt);

    // Optional: Screenshot (Panther)
    (new ScreenshotArtifact('http://127.0.0.1:8000/'))->__invoke($rt);

    // Git ops wrapped + stored as artifacts
    $rt->gitDiff();                         // saves changes.diff
    $rt->gitCommit('Demo: controller + twig + screenshot');  // saves commit.txt
    $rt->gitTag('slide/demo:artifacts', 'slide tag', true);  // saves tag.txt

    io()->success('Artifacts saved under public/artifacts/<slide>/…');
}
