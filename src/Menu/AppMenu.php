<?php

declare(strict_types=1);

namespace App\Menu;

use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\StepBundle\Service\CastorStepExporter;
use Survos\TablerBundle\Menu\MenuBuilderTrait;

/**
 * The top menu: one entry per project (a deck, as the slideshow calls it), then the tools.
 */
final class AppMenu
{
    use MenuBuilderTrait;

    public function __construct(
        private readonly CastorStepExporter $exporter,
    ) {
    }

    #[AsMenuListener(MenuSlot::NAVBAR_MENU)]
    public function navbarMenu(MenuEvent $event): void
    {
        $menu = $event->menu;

        $this->add($menu, 'survos_step_index', label: 'All slideshows');

        $projects = $this->addSubmenu($menu, 'Projects', translationDomain: false);
        foreach ($this->exporter->listSlideshows() as $deck) {
            $code = $deck['code'];
            $project = $this->addSubmenu($projects, $code, translationDomain: false);
            $this->add($project, 'survos_step_article', ['code' => $code], label: 'Article', translationDomain: false);
            $this->add($project, 'survos_step_slideshow', ['code' => $code], label: 'Slideshow', translationDomain: false);
            $this->add($project, 'survos_step_slides', ['code' => $code], label: 'Overview', translationDomain: false);
            $this->add($project, 'survos_step_markdown', ['code' => $code], label: 'Markdown', translationDomain: false);
            $this->add($project, 'survos_step_json', ['code' => $code], label: 'JSON', translationDomain: false);
        }

        $castor = $this->addSubmenu($menu, 'Castor', translationDomain: false);
        $this->add($castor, 'survos_castor_logs_index', label: 'Logs', translationDomain: false);
        $this->add($castor, 'run_castor', label: 'Run castor', translationDomain: false);
    }
}
