<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends BaseVisitLinksTest
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['', '/slideshow/', 200])]
	#[TestWith(['', '/', 200])]
	#[TestWith(['', '/castor/logs', 200])]
	#[TestWith(['', '/entity-constants', 200])]
	#[TestWith(['', '/routes-sitemap', 200])]
	#[TestWith(['', '/routes-sitemap.txt', 200])]
	#[TestWith(['', '/routes-sitemap.json', 200])]
	#[TestWith(['', '/debug-menu', 200])]
	#[TestWith(['', '/favicon.svg', 200])]
	#[TestWith(['', '/health', 200])]
	#[TestWith(['', '/slideshow/?debugMenuSlots=1', 200])]
	#[TestWith(['', '/slideshow/article/c-intro', 200])]
	#[TestWith(['', '/slideshow/show/c-intro', 200])]
	#[TestWith(['', '/slideshow/overview/c-intro', 200])]
	#[TestWith(['', '/slideshow/markdown/c-intro', 200])]
	#[TestWith(['', '/slideshow/json/c-intro.json', 200])]
	#[TestWith(['', '/slideshow/article/meili', 200])]
	#[TestWith(['', '/slideshow/show/meili', 200])]
	#[TestWith(['', '/slideshow/overview/meili', 200])]
	#[TestWith(['', '/slideshow/markdown/meili', 200])]
	#[TestWith(['', '/slideshow/json/meili.json', 200])]
	#[TestWith(['', '/slideshow/article/url-workflow', 200])]
	#[TestWith(['', '/slideshow/show/url-workflow', 200])]
	#[TestWith(['', '/slideshow/overview/url-workflow', 200])]
	#[TestWith(['', '/slideshow/markdown/url-workflow', 200])]
	#[TestWith(['', '/slideshow/json/url-workflow.json', 200])]
	#[TestWith(['', '/slideshow/article/x-meili', 200])]
	#[TestWith(['', '/slideshow/show/x-meili', 200])]
	#[TestWith(['', '/slideshow/overview/x-meili', 200])]
	#[TestWith(['', '/slideshow/markdown/x-meili', 200])]
	#[TestWith(['', '/slideshow/json/x-meili.json', 200])]
	#[TestWith(['', '/slideshow/show/conference', 200])]
	#[TestWith(['', '/slideshow/overview/c-intro?debug=1', 200])]
	#[TestWith(['', '/castor/logs/c-intro', 200])]
	#[TestWith(['', '/slideshow/overview/meili?debug=1', 200])]
	#[TestWith(['', '/castor/logs/meili', 200])]
	#[TestWith(['', '/castor/logs/url-workflow', 200])]
	#[TestWith(['', '/castor/logs/x-meili', 200])]
	#[TestWith(['', '/castor/logs?debugMenuSlots=1', 200])]
	#[TestWith(['', '/castor/logs/url-workflow/raw', 200])]
	#[TestWith(['', '/castor/logs/ez-products', 200])]
	#[TestWith(['', '/castor/logs/ez-products/raw', 200])]
	#[TestWith(['', '/castor/logs/ezp', 200])]
	#[TestWith(['', '/castor/logs/ezp/raw', 200])]
	#[TestWith(['', '/entity-constants?debugMenuSlots=1', 200])]
	#[TestWith(['', '/routes-sitemap?debugMenuSlots=1', 200])]
	#[TestWith(['', '/debug-menu?debugMenuSlots=1', 200])]
	#[TestWith(['', '/slideshow/article/c-intro?debugMenuSlots=1', 200])]
	#[TestWith(['', '/slideshow/article/meili?debugMenuSlots=1', 200])]
	#[TestWith(['', '/slideshow/json/conference.json', 200])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		parent::loginAsUserAndVisit($username, $url, (int)$expected);
	}
}
