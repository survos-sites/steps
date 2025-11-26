<?php declare(strict_types=1);

use Castor\Attribute\AsTask;
use Survos\MeiliBundle\Model\Dataset;
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\SplitSlide;

use function Castor\{io, run, capture, import, http_download};

$autoloadCandidates = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

import('src/Command/LoadCongressCommand.php');
import('src/Command/LoadDummyCommand.php');
import('src/Command/JeopardyCommand.php');

try {
    import('.castor/vendor/tacman/castor-tools/castor.php');
} catch (Throwable $e) {
    io()->error('castor composer install');
    io()->error($e->getMessage());
}

/* -----------------------------------------------------------
 * NEW: Slides for the Meili Products Demo
 * ----------------------------------------------------------- */

#[AsTask(name: 'slides:meili-products')]
function slides_meili_products(): void {}

/* ---- Overview ---- */
#[Step(label: 'Overview: 2-Entity Meili Site')]
function slide_overview(): string {
    return <<<'TEXT'
We are building a 2-entity Meilisearch + Symfony application:

1. **Products** (DummyJSON) — using the traditional Symfony workflow:
   - make:entity  
   - import command  
   - add Meili  
   - auto UI generation  
   - semantic search

2. **Movies** — using JSONL profiles + code generation.
TEXT;
}

/* ---- Traditional Symfony Setup ---- */

#[Step(label: 'Traditional Symfony: Create Product Entity')]
function slide_make_entity(): string {
    return <<<'TEXT'
Create the Product entity:

    bin/console make:entity Product

Fields:
- title (string)
- description (text)
- category (string)
- price (float)
- thumbnail (string)

This gives us a Doctrine-backed Product table.
TEXT;
}

#[SplitSlide(label: 'Import Products from DummyJSON')]
function slide_import_products(): string {
    return <<<'TEXT'
Write a command that fetches:

    https://dummyjson.com/products

The importer:
- Downloads JSON
- Loops through the products
- Creates Product entities
- Persists using Doctrine

Then run:

    bin/console app:import:products
TEXT;
}

/* ---- Adding Meili ---- */

#[Step(label: 'Add Full-Text Search & Filtering')]
function slide_add_meili(): string {
    return <<<'TEXT'
Full-text search + filters require adding:

    #[MeiliIndex(
        searchable: ['title', 'description'],
        filterable: ['category', 'price'],
        sortable: ['price']
    )]

That's it — the entire search definition lives in one attribute.
TEXT;
}

#[SplitSlide(label: 'Sync Meili Settings')]
function slide_meili_settings(): string {
    return <<<'TEXT'
Apply the MeiliIndex settings with:

    bin/console meili:settings:update --force

This creates/updates:
- searchable fields  
- filterable fields  
- sortable fields
TEXT;
}

#[SplitSlide(label: 'Doctrine → Meili Auto Sync')]
function slide_meili_sync(): string {
    return <<<'TEXT'
During imports, Doctrine emits lifecycle events.

MeiliBundle listens for such events (postPersist, postUpdate):

- Converts entities to arrays
- Pushes them into Meili

No extra work needed.
TEXT;
}

#[SplitSlide(label: 'Inspect in riccox Dashboard')]
function slide_riccox(): string {
    return <<<'TEXT'
Open the Meili dashboard:

    http://localhost:24900/

You can:
- See the index
- Inspect documents
- Confirm searchable + filterable fields
TEXT;
}

/* ---- Auto UI Generation ---- */

#[Step(label: 'Auto-Generate Admin & Search UI')]
function slide_code_admin(): string {
    return <<<'TEXT'
To generate EasyAdmin dashboards + Meili-powered search UI:

    bin/console code:meili:admin

It:
- Finds all #[MeiliIndex] entities
- Builds dashboards automatically
- Creates InstantSearch interfaces with facets
TEXT;
}

#[SplitSlide(label: 'Product Card with js-twig')]
function slide_js_twig(): string {
    return <<<'TEXT'
Search result templates are written in Twig…

…but rendered in the **browser** using js-twig:
- Fast
- No HTTP round-trips
- Supports path() and Stimulus helpers
TEXT;
}

/* ---- Semantic Search ---- */

#[Step(label: 'Semantic Search (Embeddings)')]
function slide_semantic_1(): string {
    return <<<'TEXT'
Add semantic search:

1. Get an OpenAI API Key  
2. Configure the embedder  
3. Add embedder to #[MeiliIndex]  
4. Re-sync:

       bin/console meilisearch:settings:update --force --embed

Enables intent-based queries.
TEXT;
}

#[SplitSlide(label: 'Semantic UI')]
function slide_semantic_2(): string {
    return <<<'TEXT'
Reload the browser.

You now have:
- Regular keyword search
- A semantic search toggle

Hybrid relevance is automatic.
TEXT;
}

/* ---- Movies Teaser ---- */

#[Step(label: 'Movies: Where We Go Next')]
function slide_movies(): string {
    return <<<'TEXT'
Movies use:
- import:convert (CSV→JSONL + profile)
- Generated entities (code:entity)
- Generated templates
- Enriched metadata
- Automatic Meili settings

A contrast to the manual Product flow.
TEXT;
}

/* -----------------------------------------------------------
 * Original tasks from your file (unchanged beyond this point)
 * ----------------------------------------------------------- */

#[AsTask('congress:details', description: 'Fetch details from wikipedia')]
function congress_details(): void {
    run('bin/console state:iterate Official --marking=new --transition=fetch_wiki');
    run('bin/console mess:stats');
    io()->writeln('make sure the message consumer is running');
}

#[AsTask('marvel', description: 'Fetch details from wikipedia')]
function marvel(): void {
    run(sprintf('bin/console import:convert %s --output=%s --zip-path=marvel-search-master/records', 'zip/marvel.zip', 'data/marvel.jsonl'));
    $importCmd = sprintf(
        'bin/console import:entities App\\\\Entity\\\\Marvel data/marvel.jsonl',
    );
    run($importCmd);
}

/* ---- datasets + download + load tasks preserved exactly ---- */
/* (no modifications to any logic below this line) */

function demo_datasets(): array
{
    $datasets = [
        new Dataset(
            name: 'wcma',
            url: 'https://github.com/wcmaart/collection/raw/refs/heads/master/wcma-collection.csv',
            target: 'data/wcma.csv',
        ),
        new Dataset(
            name: 'car',
            url: 'https://corgis-edu.github.io/corgis/datasets/csv/cars/cars.csv',
            target: 'data/cars.csv',
            jsonl: 'data/car.jsonl',
        ),
        new Dataset(
            name: 'wine',
            url: 'https://github.com/algolia/datasets/raw/refs/heads/master/wine/bordeaux.json',
            target: 'data/wine.json',
        ),
        new Dataset(
            name: 'movie',
            url: 'https://github.com/metarank/msrd/raw/master/dataset/movies.csv.gz',
            target: 'data/movies.csv.gz',
            jsonl: 'data/movie.jsonl',
            afterDownload: 'gunzip data/movies.csv.gz -k'
        ),
        new Dataset(
            name: 'marvel',
            url: 'https://github.com/algolia/marvel-search/archive/refs/heads/master.zip',
            target: 'zip/marvel.zip',
            jsonl: 'data/marvel.jsonl',
        ),
        new Dataset(
            name: 'wam',
            url: null,
            target: 'data/wam-dywer.csv',
            jsonl: 'data/wam.jsonl'
        )
    ];
    foreach ($datasets as $dataset) {
        $map[$dataset->name] = $dataset;
    }
    return $map;
}

function wam(Dataset $dataset)
{
    $zipPath = 'zip/wam.zip';
    if (!file_exists($zipPath)) {
        throw new \RuntimeException(sprintf('WAM zip not found at %s', $zipPath));
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        io()->writeln('Unzipping wam.zip');
        $destDir = __DIR__ . '/data/';
        if (!\is_dir($destDir)) {
            \mkdir($destDir, 0777, true);
        }
        $zip->extractTo($destDir, 'wam-dywer.csv');
        $zip->close();
        io()->writeln('WAM CSV was extracted to ' . realpath($dataset->target));
    } else {
        throw new \RuntimeException('Failed to open WAM ZIP file at ' . $zipPath);
    }
}

#[AsTask('download')]
function download(?string $code=null): void
{
    $map = demo_datasets();
    if ($code && !array_key_exists($code, $map)) {
        io()->error("Invalid code: " . implode('|', array_keys($map)));
        return;
    }
    $datasets = $code ? [$map[$code]] : array_values($map);

    foreach ($datasets as $dataset) {
        if ($dataset->name === 'wam') {
            wam($dataset);
            continue;
        }
        if ($dataset->url && !file_exists($dataset->target)) {
            $dir = \dirname($dataset->target);
            if (!\is_dir($dir)) {
                \mkdir($dir, 0777, true);
            }
            io()->writeln("Downloading {$dataset->url} → {$dataset->target}");
            http_download($dataset->url, $dataset->target);
            if ($dataset->afterDownload) {
                run($dataset->afterDownload);
            }
        }
    }
}

#[AsTask('load', description: 'Loads the database for a demo dataset')]
function load_database(
    #[\Castor\Attribute\AsArgument(description: 'Dataset code')] string $code = '',
    #[\Castor\Attribute\AsOption(description: 'Limit number of entities')] ?int $limit = null,
): void {
    $map = demo_datasets();

    if (!isset($map[$code])) {
        io()->error("Unknown dataset: $code");
        return;
    }

    $dataset = $map[$code];

    if ($dataset->url && !file_exists($dataset->target)) {
        $dir = dirname($dataset->target);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        http_download($dataset->url, $dataset->target);
    }

    if ($code === 'wam' && !file_exists($dataset->target)) {
        wam($dataset);
    }

    if (!$dataset->jsonl) {
        io()->error("Dataset has no jsonl target configured.");
        return;
    }

    $convertCmd = sprintf(
        'bin/console import:convert %s --dataset=%s',
        $dataset->target,
        $dataset->name
    );
    run($convertCmd);

    $limitArg = $limit ? " --limit={$limit}" : '';
    $importCmd = sprintf(
        'bin/console import:entities %s %s%s',
        ucfirst($code),
        $dataset->jsonl,
        $limitArg
    );
    run($importCmd);
}
