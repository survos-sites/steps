<?php
// File: castor/basic.castor.php

use Castor\Attribute\AsTask;
use Survos\StepBundle\Runtime\RunStep; // ← use the class directly

use Survos\StepBundle\Metadata\Step;
use Survos\StepBundle\Metadata\Actions\{
    Console,
    OpenUrl,
    ComposerRequire,
    ImportmapRequire,
    RequirePackage
};

#[AsTask(name: '0-install', description: 'Install PHP and front-end dependencies')]
#[Step(
    title: 'Install dependencies',
    description: 'Add Step authoring + EasyAdmin (PHP) and Tabler (front-end).',
    bullets: ['composer packages (left-justified bash block)','importmap packages'],
    actions: [
        new ComposerRequire(
            requires: [
                new RequirePackage('survos/step-bundle', 'Step authoring bundle'),
                new RequirePackage('easycorp/easyadmin-bundle', 'Easy Administration, PHP based'),
            ],
            dev: false,
            note: 'Install PHP packages'
        ),
        new ImportmapRequire(
            requires: [ new RequirePackage('@tabler/core', 'Tabler UI') ],
            cwd: 'demo',
            note: 'Install front-end packages via importmap'
        ),
    ]
)]
function install_dependencies(): void
   { RunStep::run();}
