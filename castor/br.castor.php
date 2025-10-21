<?php declare(strict_types=1);

/**
 * Portable Castor 1.0 slides for BunnyBundle (no app attributes/namespaces required)
 *
 * Usage examples:
 *   vendor/bin/castor slides:list --project=/path/to/bunny-demo
 *   vendor/bin/castor slides:run --slide=1 --project=/path/to/bunny-demo
 *   vendor/bin/castor slides:export:json --out=bunny-deck.json --project=/path/to/bunny-demo
 */

use Castor\Attribute\AsTask;
use Symfony\Component\Filesystem\Filesystem;

use function Castor\context;
use function Castor\io;
use function Castor\run as castor_run;

/* ---------------------------------------------------------
| Helpers (portable, no app autoload required)
* --------------------------------------------------------- */


function fs(): Filesystem { static $fs; return $fs ??= new Filesystem(); }

function run(array $cmd, ?string $cwd = null): void
{
    $ctx = context();
    if ($cwd) $ctx = $ctx->withWorkingDirectory($cwd);
    castor_run($cmd, context: $ctx);
}

function capture(array $cmd, ?string $cwd = null): string
{
    $ctx = context();
    if ($cwd) $ctx = $ctx->withWorkingDirectory($cwd);
    return castor_run($cmd, context: $ctx)->getOutput();
}

/** Append a line to .env.local if missing (exact match). */
function ensure_env_local(string $projectDir, string $line): void
{
    $path = rtrim($projectDir, '/').'/'.'.env.local';
    $line = rtrim($line, "\r\n");
    if (!fs()->exists($path)) {
        fs()->dumpFile($path, $line."\n");
        io()->writeln("<info>created</info> .env.local and added: {$line}");
        return;
    }
    $existing = file_get_contents($path) ?: '';
    if (!preg_match('/^'.preg_quote($line, '/').'$/m', $existing)) {
        fs()->appendToFile($path, $line."\n");
        io()->writeln("<info>updated</info> .env.local with: {$line}");
    } else {
        io()->writeln("<comment>exists</comment> .env.local already has: {$line}");
    }
}

/**
 * Try to fetch code for a “show_source” action:
 * - If path is provided, return file contents.
 * - If class+method provided, run a one-off php -r inside $projectDir
 *   with vendor/autoload.php to reflect and slice method source.
 */
function fetch_source(array $action, string $projectDir): string
{
    if (!empty($action['path'])) {
        $path = $action['path'][0] === '/' ? $action['path'] : rtrim($projectDir,'/').'/'.$action['path'];
        if (fs()->exists($path)) return file_get_contents($path) ?: '';
        return "// file not found: {$path}\n";
    }

    if (!empty($action['class']) && !empty($action['method'])) {
        $code = <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$c = $argv[1]; $m = $argv[2];
if (!class_exists($c)) { fwrite(STDOUT, "// class not found: $c\n"); exit(0); }
$rm = new ReflectionMethod($c, $m);
$f = $rm->getFileName(); $s = $rm->getStartLine(); $e = $rm->getEndLine();
if (!$f || !is_file($f)) { fwrite(STDOUT, "// source file not found for $c::$m()\n"); exit(0); }
$lines = file($f);
echo implode('', array_slice($lines, $s-1, $e-$s+1));
PHP;
        $tmp = tempnam(sys_get_temp_dir(), 'showsrc_').'.php';
        file_put_contents($tmp, $code);
        try {
            return capture(['php', $tmp, $action['class'], $action['method']], $projectDir);
        } finally {
            @unlink($tmp);
        }
    }

    return "// no source parameters provided\n";
}

/* ---------------------------------------------------------
| Deck definition (pure data) — mirrors the README
| Schema (per slide):
| - title: string
| - description: string
| - bullets: list<string>
| - actions: list<action>
|   action:
|     - type: composer|console|bash|show_source
|     - cmd: string                (for composer/console/bash)
|     - path|class|method|lang     (for show_source)
|     - label: string|null         (optional display caption)
* --------------------------------------------------------- */

function bunny_deck(string $projectDir, ?string $apiKey = null): array
{
    return [
        [
            'title' => 'Quickstart',
            'description' => 'Create a new Symfony app and install BunnyBundle',
            'bullets' => [
                'symfony new bunny-demo --webapp && cd bunny-demo',
                'composer require survos/bunny-bundle',
            ],
            'actions' => [
                ['type' => 'bash', 'cmd' => "symfony new bunny-demo --webapp"],
                ['type' => 'bash', 'cmd' => "cd bunny-demo && composer require survos/bunny-bundle"],
            ],
        ],
        [
            'title' => 'Installation',
            'description' => 'Bootstrap configuration with your main API key, then list resources',
            'bullets' => [
                'composer require survos/bunny-bundle',
                'bin/console bunny:config <api-key> >> .env.local',
                'bin/console bunny:list',
            ],
            'actions' => [
                ['type' => 'composer', 'cmd' => 'composer require survos/bunny-bundle'],
                // we simulate ">> .env.local" by reading output and appending (in slides:run)
                ['type' => 'console',  'cmd' => $apiKey ? "php bin/console bunny:config {$apiKey}" : "php bin/console bunny:config \$BUNNY_API_KEY"],
                ['type' => 'console',  'cmd' => 'php bin/console bunny:list'],
            ],
        ],
        [
            'title' => 'Interactive browse',
            'description' => 'Install SimpleDatatables and open /bunny/zones',
            'bullets' => [
                'composer require survos/simple-datatables-bundle',
                'symfony server:start -d',
                'symfony open:local --path=/bunny/zones',
            ],
            'actions' => [
                ['type' => 'composer', 'cmd' => 'composer require survos/simple-datatables-bundle'],
                ['type' => 'bash',     'cmd' => 'symfony server:start -d'],
                ['type' => 'bash',     'cmd' => 'symfony open:local --path=/bunny/zones'],
            ],
        ],
        [
            'title' => 'API key via .env.local',
            'description' => 'Avoid passing the key each time; store it as an env var',
            'bullets' => [
                'echo "BUNNY_API_KEY=..." >> .env.local',
                'Use %env(BUNNY_API_KEY)% in config',
            ],
            'actions' => [
                // show current .env.local (if exists)
                ['type' => 'show_source', 'path' => '.env.local', 'lang' => 'properties', 'label' => '.env.local (current)'],
                // we actually append the provided key during slides:run
                // no direct shell echo here to avoid quoting issues on Windows
            ],
        ],
        [
            'title' => 'Dump configuration',
            'description' => 'Generate survos_bunny.yaml and env references via bunny:config',
            'bullets' => [
                'bin/console bunny:config <api-key>',
                'Writes packages/survos_bunny.yaml with %env()% refs',
                'Add generated env vars to .env.local',
            ],
            'actions' => [
                ['type' => 'console', 'cmd' => $apiKey ? "php bin/console bunny:config {$apiKey}" : "php bin/console bunny:config \$BUNNY_API_KEY"],
                // show files (if they exist)
                ['type' => 'show_source', 'path' => 'config/packages/survos_bunny.yaml', 'lang' => 'yaml', 'label' => 'config/packages/survos_bunny.yaml'],
                ['type' => 'show_source', 'path' => '.env.local', 'lang' => 'properties', 'label' => '.env.local (updated)'],
            ],
        ],
        [
            'title' => 'CLI usage',
            'description' => 'Use the command line interface to list resources',
            'bullets' => [
                'bin/console bunny:list',
                'Optional flags per your setup',
            ],
            'actions' => [
                ['type' => 'console', 'cmd' => 'php bin/console bunny:list'],
            ],
        ],
        [
            'title' => 'Secure the admin',
            'description' => 'Lock down /bunny routes in security.yaml',
            'bullets' => [
                'Protect /bunny routes with firewall/access_control',
                'Limit to ROLE_ADMIN (or similar)',
            ],
            'actions' => [
                ['type' => 'show_source', 'path' => 'config/packages/security.yaml', 'lang' => 'yaml', 'label' => 'config/packages/security.yaml (excerpt)'],
            ],
        ],
    ];
}

/* ---------------------------------------------------------
| Core tasks
* --------------------------------------------------------- */

#[AsTask(description: 'List the slides (outline) as JSON')]
function slides_list(
    #[\Castor\Attribute\Option('Path to the Symfony project root')] ?string $project = '.',
    #[\Castor\Attribute\Option('Bunny API key (optional; otherwise expects BUNNY_API_KEY in env)')] ?string $api_key = null,
): void {
    $deck = bunny_deck($project, $api_key);
    io()->writeln(json_encode([
        'project' => $project,
        'slides'  => array_map(fn($s, $i) => ['#' => $i+1] + $s, $deck, array_keys($deck)),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

#[AsTask(description: 'Run actions in a given slide (1-based index)')]
function slides_run(
    #[\Castor\Attribute\Option('Slide number to execute (1-based)')] int $slide,
    #[\Castor\Attribute\Option('Path to the Symfony project root')] ?string $project = '.',
    #[\Castor\Attribute\Option('Bunny API key (optional)')] ?string $api_key = null,
): void {
    $deck = bunny_deck($project, $api_key);
    $idx  = $slide - 1;
    if (!isset($deck[$idx])) {
        io()->error("Slide {$slide} not found");
        return;
    }
    $s = $deck[$idx];
    io()->title("Slide {$slide}: {$s['title']}");

    foreach ($s['actions'] as $a) {
        $type = $a['type'] ?? 'bash';

        // Special handling for “write key” slide
        if ($s['title'] === 'API key via .env.local' && $type === 'show_source' && $api_key) {
            ensure_env_local($project, 'BUNNY_API_KEY='.$api_key);
        }

        switch ($type) {
            case 'composer':
                run(['bash', '-lc', $a['cmd']], $project);
                break;
            case 'console':
                run(['bash', '-lc', $a['cmd']], $project);
                break;
            case 'bash':
                run(['bash', '-lc', $a['cmd']], $project);
                break;
            case 'show_source':
                $code = fetch_source($a, $project);
                $label = $a['label'] ?? ($a['path'] ?? ($a['class'] ?? 'code'));
                io()->section($label);
                io()->writeln($code);
                break;
            default:
                io()->warning("Unknown action type: {$type}");
        }
    }

    io()->success('Slide actions finished.');
}

#[AsTask(description: 'Export slides to JSON (for on-the-fly rendering)')]
function slides_export_json(
    #[\Castor\Attribute\Option('Output file path (default: bunny-deck.json)')] ?string $out = 'bunny-deck.json',
    #[\Castor\Attribute\Option('Path to the Symfony project root')] ?string $project = '.',
    #[\Castor\Attribute\Option('Bunny API key (optional)')] ?string $api_key = null,
): void {
    $deck = bunny_deck($project, $api_key);

    // Resolve “show_source” code now so the JSON is self-contained for a static viewer
    $slides = [];
    foreach ($deck as $s) {
        $actions = [];
        foreach ($s['actions'] as $a) {
            if (($a['type'] ?? null) === 'show_source') {
                $actions[] = $a + ['code' => fetch_source($a, $project)];
            } else {
                $actions[] = $a; // keep commands as-is
            }
        }
        $slides[] = $s + ['actions' => $actions];
    }

    $payload = [
        'project' => $project,
        'generated_at' => date('c'),
        'slides' => $slides,
    ];

    fs()->dumpFile($out, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    io()->success("Wrote JSON deck to {$out}");
}
