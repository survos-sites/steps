<?php
// file: conference/url-workflow.castor.php
// Build a Symfony 8.2 app with a native workflow, step by step, from nothing.
// Run:  CODE=url-workflow castor build        (no server is started)

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Survos\StepBundle\Action\{Artifact, Bash, ComposerRequire, Console, DisplayArtifact, FileWrite};
use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Runtime\RunStep;

use function Castor\context;

const URL_DEMO_NAME = 'url-workflow-demo';

/**
 * The context steps run in: the demo app, once `new` has created it. Resolved per task,
 * because the default context is built before the demo exists and `build` runs in one process.
 * Until then it is the parent directory, never this (slideshow) repository.
 */
function url_ctx(): \Castor\Context
{
    $demo = dirname(__DIR__, 2) . '/demo/' . URL_DEMO_NAME;
    $dir = is_dir($demo) ? $demo : dirname($demo);
    if (!is_dir($dir)) {
        mkdir($dir, 0o775, true);
    }

    return context()->withWorkingDirectory($dir);
}

#[\Survos\StepBundle\Metadata\Deck(
    title: 'A native Symfony workflow, from nothing',
    subtitle: 'Places, transitions, a diagram and a URL fetcher, with survos/workflow-extras-bundle',
    author: 'Tac Tacelosky',
)]
function deck_url_workflow(): void
{
}

#[AsTask('new', description: 'Create the Symfony project')]
#[Step(
    'A fresh Symfony 8.2 application',
    description: 'The Symfony CLI creates the webapp skeleton on the next development release.',
    bullets: ['--version=next: Symfony 8.2-dev, which has native workflow attributes', '--webapp: Doctrine, Twig, Messenger, the profiler'],
    actions: [
        new Bash('symfony new ' . URL_DEMO_NAME . ' --version=next --webapp --no-git --no-interaction', cwd: __DIR__ . '/../../demo'),
    ]
)]
function url_new(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('require', description: 'Install workflow-extras and use SQLite')]
#[Step(
    'Install workflow-extras',
    description: 'The bundle adds descriptions, labels and ordered next-transition chaining on top of native workflow attributes.',
    bullets: ['A throwaway SQLite database, so there is nothing to set up', 'workflow: ~ turns on attribute discovery'],
    actions: [
        new ComposerRequire(['survos/workflow-extras-bundle:dev-main']),
        new FileWrite('.env.local', <<<'END'
DATABASE_URL="sqlite:///%kernel.project_dir%/var/url-demo.db"
END, a: 'env.local'),
        new FileWrite('config/packages/workflow.yaml', <<<'END'
workflow: ~
END, a: 'workflow.yaml'),
    ]
)]
function url_require(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('entity', description: 'The Url entity')]
#[Step(
    'The Url entity',
    description: 'A URL, the place it is in (an enum), and what happened when we fetched it.',
    bullets: ['marking is a backed enum, so the database cannot hold an unknown place', 'isValid() will be the guard on two transitions'],
    actions: [
        new FileWrite('src/Entity/Url.php', <<<'END'
<?php
namespace App\Entity;

use App\Workflow\UrlPlace;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Url
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 2048)]
    public string $address;

    #[ORM\Column(enumType: UrlPlace::class)]
    public UrlPlace $marking = UrlPlace::Discovered;

    #[ORM\Column(nullable: true)]
    public ?int $statusCode = null;

    #[ORM\Column(nullable: true)]
    public ?int $bytes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $error = null;

    public function isValid(): bool
    {
        return false !== filter_var($this->address, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($this->address, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
    }
}
END, a: 'Url.php'),
    ]
)]
function url_entity(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('places', description: 'The places, as an enum')]
#[Step(
    'Places',
    description: 'Each place is an enum case with a description. next: says which transition to try after arriving.',
    bullets: ['Discovered → try validate, then reject', 'Validated → fetch'],
    actions: [
        new FileWrite('src/Workflow/UrlPlace.php', <<<'END'
<?php
namespace App\Workflow;

use Survos\WorkflowExtrasBundle\Attribute\Place;

enum UrlPlace: string
{
    #[Place('A URL ready for validation', next: [UrlWorkflow::VALIDATE, UrlWorkflow::REJECT], initial: true)]
    case Discovered = 'discovered';

    #[Place('A valid HTTP or HTTPS URL', next: UrlWorkflow::FETCH)]
    case Validated = 'validated';

    #[Place('Not a valid HTTP or HTTPS URL')]
    case Invalid = 'invalid';

    #[Place('Fetch attempted; inspect status code, bytes and error')]
    case Fetched = 'fetched';
}
END, a: 'UrlPlace.php'),
    ]
)]
function url_places(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('workflow', description: 'The workflow class')]
#[Step(
    'The workflow',
    description: 'Three transitions. Guards are expressions on the subject; listeners do the work.',
    bullets: ['validate / reject are mutually exclusive guards', 'fetch runs synchronously and records status code and bytes'],
    actions: [
        new FileWrite('src/Workflow/UrlWorkflow.php', <<<'END'
<?php
namespace App\Workflow;

use App\Entity\Url;
use Survos\WorkflowExtrasBundle\Attribute\AsWorkflow;
use Survos\WorkflowExtrasBundle\Attribute\Transition;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsWorkflow(supports: [Url::class], name: self::NAME, places: UrlPlace::class)]
final class UrlWorkflow
{
    public const NAME = 'url';

    #[Transition(UrlPlace::Discovered, UrlPlace::Validated, guard: 'subject.isValid()')]
    public const VALIDATE = 'validate';

    #[Transition(UrlPlace::Discovered, UrlPlace::Invalid, guard: '!subject.isValid()')]
    public const REJECT = 'reject';

    #[Transition(UrlPlace::Validated, UrlPlace::Fetched, 'Fetch synchronously, recording HTTP status and response bytes')]
    public const FETCH = 'fetch';

    public function __construct(private HttpClientInterface $client) {}

    #[AsTransitionListener(self::NAME, self::REJECT)]
    public function reject(TransitionEvent $event): void
    {
        $event->getSubject()->error = 'Expected a valid HTTP or HTTPS URL.';
    }

    #[AsTransitionListener(self::NAME, self::FETCH)]
    public function fetch(TransitionEvent $event): void
    {
        $url = $event->getSubject();
        try {
            $response = $this->client->request('GET', $url->address, ['timeout' => 10, 'max_duration' => 20]);
            $url->statusCode = $response->getStatusCode();
            // false retains bodies for HTTP errors (404, 500, ...).
            $url->bytes = strlen($response->getContent(false));
        } catch (TransportExceptionInterface $exception) {
            $url->error = $exception->getMessage();
        }
    }
}
END, a: 'UrlWorkflow.php'),
    ]
)]
function url_workflow(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('diagram', description: 'Draw the workflow')]
#[Step(
    'The workflow, drawn',
    description: 'Symfony can draw any workflow it has registered. The same definition we just wrote produces this.',
    bullets: ['bin/console workflow:dump url', 'mermaid for documents, PNG (via Graphviz) for slides'],
    actions: [
        new Console('workflow:dump', ['url', '--dump-format=mermaid'], a: 'url-workflow.mmd', prefer: 'php'),
        new Bash('bin/console workflow:dump url | dot -Tpng -o url-workflow.png'),
        new Artifact('url-workflow.png', 'url-workflow.png', type: 'image/png'),
        new DisplayArtifact('url-workflow.png', note: 'Discovered → Validated → Fetched, or Discovered → Invalid'),
    ]
)]
function url_diagram(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('continuation', description: 'Follow next transitions after saving')]
#[Step(
    'Persistence policy',
    description: 'The bundle chooses the next transition; the app decides when to save. After each flush, continue.',
    actions: [
        new FileWrite('src/Workflow/UrlContinuation.php', <<<'END'
<?php
namespace App\Workflow;

use App\Entity\Url;
use Doctrine\ORM\EntityManagerInterface;
use Survos\WorkflowExtrasBundle\Contract\WorkflowContinuationInterface;
use Survos\WorkflowExtrasBundle\Service\NextTransitionRunner;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;

final class UrlContinuation implements WorkflowContinuationInterface
{
    public function __construct(
        private Registry $registry,
        private NextTransitionRunner $runner,
        private EntityManagerInterface $manager,
    ) {}

    public function supports(object $subject, ?string $workflowName = null): bool
    {
        return $subject instanceof Url && ($workflowName === null || $workflowName === UrlWorkflow::NAME);
    }

    public function kickoff(object $subject): void
    {
        if ($subject->marking === UrlPlace::Discovered) {
            $this->continueAfterFlush($subject);
        }
    }

    public function continueAfterFlush(object $subject, array $context = [], ?Transition $previous = null): void
    {
        $this->runner->continue($this->registry->get($subject, UrlWorkflow::NAME), $subject, $context, $previous);
        $this->manager->flush();
    }
}
END, a: 'UrlContinuation.php'),
    ]
)]
function url_continuation(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('command', description: 'A command that loads URLs')]
#[Step(
    'Load some URLs',
    description: 'Insert a URL and flush. The workflow takes it from there: no transition calls in the command.',
    actions: [
        new FileWrite('src/Command/LoadUrlsCommand.php', <<<'END'
<?php
namespace App\Command;

use App\Entity\Url;
use App\Workflow\UrlPlace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:load-urls', description: 'Insert URLs and follow next transitions synchronously')]
final class LoadUrlsCommand extends Command
{
    public function __construct(private EntityManagerInterface $manager) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('urls', InputArgument::IS_ARRAY, 'Trusted URLs to fetch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        foreach ($input->getArgument('urls') ?: ['https://example.com', 'not-a-url'] as $address) {
            $url = new Url();
            $url->address = $address;
            $url->marking = UrlPlace::Discovered;
            $this->manager->persist($url);
            $this->manager->flush(); // Bundle kickoff follows next; no transition call here.
            $rows[] = [$url->address, $url->marking->value, $url->statusCode, $url->bytes, $url->error];
        }
        (new SymfonyStyle($input, $output))->table(['URL', 'Place', 'Status', 'Bytes', 'Error'], $rows);
        return Command::SUCCESS;
    }
}
END, a: 'LoadUrlsCommand.php'),
    ]
)]
function url_command(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('page', description: 'A page that lists them')]
#[Step(
    'A page',
    description: 'A controller and a template, so the result is something you can look at.',
    actions: [
        new FileWrite('src/Controller/HomeController.php', <<<'END'
<?php
namespace App\Controller;

use App\Entity\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function __invoke(EntityManagerInterface $manager): Response
    {
        return $this->render('url/index.html.twig', ['urls' => $manager->getRepository(Url::class)->findBy([], ['id' => 'ASC'])]);
    }
}
END, a: 'HomeController.php'),
        new FileWrite('templates/url/index.html.twig', <<<'END'
{% extends 'base.html.twig' %}
{% block title %}URL workflow{% endblock %}
{% block body %}
<h1>URL workflow</h1>
<p>Discovered → Validated → Fetched, or Discovered → Invalid. Validation happens at once; the fetch is a separate step.</p>
<table cellpadding="10">
    <thead><tr><th>URL</th><th>Place</th><th>Status</th><th>Bytes</th><th>Error</th></tr></thead>
    <tbody>{% for url in urls %}
        <tr><td>{{ url.address }}</td><td>{{ url.marking.value }}</td><td>{{ url.statusCode ?? '—' }}</td><td>{{ url.bytes ?? '—' }}</td><td>{{ url.error ?? '—' }}</td></tr>
    {% else %}<tr><td colspan="5">Run bin/console app:load-urls</td></tr>{% endfor %}</tbody>
</table>
{% endblock %}
END, a: 'index.html.twig'),
    ]
)]
function url_page(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('migrate', description: 'Create the database')]
#[Step(
    'The database',
    description: 'A migration for the Url table, then run it.',
    actions: [
        new Console('make:migration', prefer: 'php'),
        new Console('doctrine:migrations:migrate', prefer: 'php'),
    ]
)]
function url_migrate(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('run', description: 'Run it')]
#[Step(
    'Run it',
    description: 'One valid URL and one that is not. Each ends in the place its transitions led to.',
    bullets: ['https://example.com → fetched, with status code and size', 'not-a-url → invalid'],
    actions: [
        new Console('app:load-urls', a: 'load-urls.txt', prefer: 'php'),
        new DisplayArtifact('load-urls.txt', lang: 'text', note: 'Output'),
    ]
)]
function url_run(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('messenger', description: 'Install workflow-messenger')]
#[Step(
    'Run some transitions in a worker',
    description: 'So far every transition ran inside the request or command that started it. A fetch can be slow or fail, so it belongs in a queue. workflow-messenger-bundle lets a transition be queued: your listeners are unchanged, but a Messenger worker runs them.',
    bullets: [
        'validate and reject stay synchronous: they are cheap and decide where the URL goes next',
        'fetch becomes a queued transition handled by a worker',
        'the webapp skeleton already has an async Doctrine transport, so there is nothing to provision',
    ],
    actions: [
        new ComposerRequire(['survos/workflow-messenger-bundle']),
    ]
)]
function url_messenger(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('async', description: 'Queue the fetch')]
#[Step(
    'Queue the fetch',
    description: 'Two lines change in the workflow: the messenger bundle\'s Transition attribute (imported as AsyncTransition, because it is async by default) on the fetch transition, with the transport to use. The listener that does the fetching is exactly as before.',
    bullets: [
        'use ...\\Attribute\\Transition as AsyncTransition',
        '#[AsyncTransition(..., transport: \'async\')] on FETCH',
    ],
    actions: [
        new FileWrite('src/Workflow/UrlWorkflow.php', <<<'END'
<?php
namespace App\Workflow;

use App\Entity\Url;
use Survos\WorkflowExtrasBundle\Attribute\AsWorkflow;
use Survos\WorkflowExtrasBundle\Attribute\Transition;
use Survos\WorkflowMessengerBundle\Attribute\Transition as AsyncTransition;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsWorkflow(supports: [Url::class], name: self::NAME, places: UrlPlace::class)]
final class UrlWorkflow
{
    public const NAME = 'url';

    #[Transition(UrlPlace::Discovered, UrlPlace::Validated, guard: 'subject.isValid()')]
    public const VALIDATE = 'validate';

    #[Transition(UrlPlace::Discovered, UrlPlace::Invalid, guard: '!subject.isValid()')]
    public const REJECT = 'reject';

    #[AsyncTransition(UrlPlace::Validated, UrlPlace::Fetched, 'Fetch in a worker, recording HTTP status and response bytes', transport: 'async')]
    public const FETCH = 'fetch';

    public function __construct(private HttpClientInterface $client) {}

    #[AsTransitionListener(self::NAME, self::REJECT)]
    public function reject(TransitionEvent $event): void
    {
        $event->getSubject()->error = 'Expected a valid HTTP or HTTPS URL.';
    }

    #[AsTransitionListener(self::NAME, self::FETCH)]
    public function fetch(TransitionEvent $event): void
    {
        $url = $event->getSubject();
        try {
            $response = $this->client->request('GET', $url->address, ['timeout' => 10, 'max_duration' => 20]);
            $url->statusCode = $response->getStatusCode();
            // false retains bodies for HTTP errors (404, 500, ...).
            $url->bytes = strlen($response->getContent(false));
        } catch (TransportExceptionInterface $exception) {
            $url->error = $exception->getMessage();
        }
    }
}
END, a: 'UrlWorkflow-async.php'),
    ]
)]
function url_async(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('worker', description: 'Run a worker')]
#[Step(
    'Run a worker',
    description: 'Load two more URLs. Validation runs at once, so the invalid one is already settled and the valid one waits at "validated". A worker then takes the queued fetch, runs the same listener, and the URL becomes "fetched".',
    bullets: [
        'app:load-urls only inserts the rows; the workflow queues the fetch',
        'messenger:consume async handles one message here; in production a supervisor keeps it running',
    ],
    actions: [
        new Console('app:load-urls', ['https://example.org', 'not-a-url-either'], a: 'load-urls-async.txt', prefer: 'php'),
        new DisplayArtifact('load-urls-async.txt', lang: 'text', note: 'Before the worker: the valid URL is waiting in the queue'),
        new Console('messenger:consume', ['async', '--limit=1', '--time-limit=30', '-vv'], a: 'consume.txt', prefer: 'php'),
        new DisplayArtifact('consume.txt', lang: 'text', note: 'The worker receives the request and handles it'),
        new Console('dbal:run-sql', ['select address, marking, status_code, bytes from url order by id'], a: 'urls-after-worker.txt', prefer: 'php'),
        new DisplayArtifact('urls-after-worker.txt', lang: 'text', note: 'After the worker: everything has settled'),
    ]
)]
function url_worker(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('browser', description: 'A browser test, and a screenshot')]
#[Step(
    'A browser test, and a screenshot',
    description: 'A real browser looks at the page. No web server runs: the browser\'s requests are answered by the application\'s own kernel, so the test sees exactly what the app serves.',
    bullets: [
        'zenstruck/browser drives Playwright; playwright-symfony routes its requests into the kernel',
        'The test environment points at the same SQLite file, so the table has the rows we loaded',
        'takeScreenshot() saves the page as an image',
    ],
    actions: [
        new ComposerRequire(['zenstruck/browser', 'playwright-php/playwright-symfony'], dev: true),
        new Bash('vendor/bin/playwright-install chromium', note: 'Downloads Chromium for Playwright (about 600 MB, once per machine).'),
        new FileWrite('.env.test.local', <<<'END'
DATABASE_URL="sqlite:///%kernel.project_dir%/var/url-demo.db"
END, a: 'env.test.local'),
        new FileWrite('tests/HomeTest.php', <<<'END'
<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class HomeTest extends KernelTestCase
{
    use HasBrowser;

    public function testTheUrlTable(): void
    {
        // A real browser, but no web server: its requests are answered by this app's own kernel.
        $this->playwrightBrowser()
            ->visit('/')
            ->assertSeeIn('h1', 'URL workflow')
            ->assertSee('https://example.com')
            ->assertSee('fetched')
            ->assertSee('invalid')
            ->takeScreenshot('home.png')
        ;
    }
}
END, a: 'HomeTest.php'),
        new Bash('php bin/phpunit tests/HomeTest.php'),
        new Artifact('var/browser/screenshots/home.png', 'home.png', type: 'image/png'),
        new DisplayArtifact('var/browser/screenshots/home.png', note: 'The page, as the browser saw it'),
    ]
)]
function url_browser(): void { RunStep::run(_actions_from_current_task(), url_ctx()); }

#[AsTask('build', description: 'Build the whole demo (no server)')]
#[Step(
    'One-shot build',
    description: 'Every step in order.',
    bullets: ['new → require → entity → places → workflow → diagram → continuation → command → page → migrate → run → messenger → async → worker → browser'],
)]
function url_build(): void
{
    url_new();
    url_require();
    url_entity();
    url_places();
    url_workflow();
    url_diagram();
    url_continuation();
    url_command();
    url_page();
    url_migrate();
    url_run();
    url_messenger();
    url_async();
    url_worker();
    url_browser();
}
