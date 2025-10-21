<?php
// castor.php (project root or wherever you run castor from)

use Castor\Attribute\AsTask;
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\Actions\{Bash, Console, Composer, Env, Section, RequirePackage, ComposerRequire};
require __DIR__ . '/../vendor/autoload.php';

// TEMP RUNTIME STUB: present-only (no execution).
if (!function_exists('run_step')) {
    function run_step(): void {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $fn = $trace[1]['function'] ?? 'unknown';
        $ref = new ReflectionFunction($fn);
        $attrs = $ref->getAttributes(Step::class);
        if (!$attrs) { return; }
        /** @var Step $step */
        $step = $attrs[0]->newInstance();

        echo "\n== {$step->title} ==\n";
        if ($step->description) { echo $step->description . "\n"; }
        foreach ($step->bullets as $b) { echo " - $b\n"; }
        foreach ($step->actions as $a) {
            $type = (new ReflectionClass($a))->getShortName();
            $summary = $a->note ? " // {$a->note}" : '';
            if ($a instanceof Bash)     { echo "  [Bash] {$a->cmd}{$summary}\n"; }
            if ($a instanceof Console)  { echo "  [Console] {$a->cmd}{$summary}\n"; }
            if ($a instanceof Composer) { echo "  [Composer] {$a->cmd}{$summary}\n"; }
            if ($a instanceof Env)      { echo "  [Env] {$a->key}={$a->value} ({$a->file}){$summary}\n"; }
            if ($a instanceof Section)  { echo "  --- {$a->title} ---\n"; }
        }
        echo "----------------------\n";
    }
}

#[AsTask(name: '0-new', description: 'Create Symfony app')]
#[Step(
    title: 'Create Symfony app',
    description: 'Scaffold a new Symfony webapp and enter the directory.',
    actions: [
        new Bash('symfony new demo --webapp && cd demo'),
    ],
)]
function new_app(): void { run_step(); }

#[AsTask(name: '1-install', description: 'Install StepBundle and EasyAdmin')]
#[Step(
    title: 'Install bundles',
    description: "we'll do them all now to get it out of the way.  Can we put markdown here?",
    bullets: ['SurvosStepBundle', 'EasyAdmin'],
    actions: [
        new ComposerRequire(
            cwd: 'demo',
            dev: false,
            requires: [
                new RequirePackage('survos/step-bundle', 'Add step authoring bundle'),
                new RequirePackage('easycore/easyadmin', 'Easy Administration, PHP based'),
            ]
        )
    ],
)]
function install_bundles(): void { run_step(); }

#[AsTask(name: '2-env', description: 'Set local env')]
#[Step(
    title: 'Configure environment',
    bullets: ['Development mode', 'Meilisearch defaults'],
    actions: [
        new Env('APP_ENV', 'dev', file: '.env.local', cwd: 'demo'),
        new Env('MEILISEARCH_URL', 'http://127.0.0.1:7700', file: '.env.local', cwd: 'demo'),
        new Env('MEILISEARCH_API_KEY', 'masterKey', file: '.env.local', cwd: 'demo'),
        new Console('cache:clear', note: 'Pick up env changes', cwd: 'demo'),
    ],
)]
function set_env(): void { run_step(); }

#[AsTask(name: '3-run', description: 'Run the app ' . __FILE__ . '/' . __METHOD__)]
#[Step(
    title: 'Run the app',
    actions: [
        new Bash('symfony server:start -d', cwd: 'demo'),
        new Bash('symfony open:local', cwd: 'demo'),
    ],
)]
function run_app(): void { run_step(); }

