<?php

declare(strict_types=1);

namespace App\Tests;

use Survos\StepBundle\Service\CastorStepExporter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The exporter runs in one long-lived process here (as it does in tests and worker-mode servers). A deck can only be
 * loaded once per process, so every export after the first used to come back with no slides, still as HTTP 200:
 * the crawl tests only check status, so they cannot see it.
 */
final class DeckExportTest extends KernelTestCase
{
    public function testEveryExportInOneProcessHasSlides(): void
    {
        $exporter = static::getContainer()->get(CastorStepExporter::class);

        $counts = [];
        foreach (['url-workflow', 'c-intro', 'url-workflow', 'meili', 'c-intro'] as $i => $code) {
            $deck = $exporter->exportSlides($code);
            $counts[] = $code.'#'.$i.'='.\count($deck['slides']);
            $this->assertNotEmpty($deck['slides'], "export #{$i} of '{$code}' has no slides (".implode(', ', $counts).')');
        }

        // the same deck exports the same number of slides every time
        $this->assertSame(
            \count($exporter->exportSlides('url-workflow')['slides']),
            \count($exporter->exportSlides('url-workflow')['slides']),
        );
    }

    public function testDeckMetaSurvivesRepeatExports(): void
    {
        $exporter = static::getContainer()->get(CastorStepExporter::class);

        $first = $exporter->exportSlides('url-workflow')['meta'];
        $second = $exporter->exportSlides('url-workflow')['meta'];

        $this->assertNotEmpty($first['title'] ?? null);
        $this->assertSame($first, $second);
    }
}
