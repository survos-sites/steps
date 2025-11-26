<?php

/**
 * Hybrid search explanation.
 */
#[AsTask(name: 'showtime:hybrid', description: 'Hybrid search in practice')]
#[Step(
    description: 'Hybrid search: best of both worlds',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                '◀️ keyword-only · fairly precise (tokenized, not strings)',
                '🔀 balanced hybrid',
                '▶️ semantic-only · exploratory',
            ]
        ),
    ]
)]
function showtime_hybrid(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Symfony Messenger and async indexing.
 */
#[AsTask(name: 'showtime:messenger', description: 'Indexing at scale with Messenger')]
#[Step(
    description: 'Indexing at scale with Symfony Messenger',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Indexing large datasets should be asynchronous',
                'Meili itself is async: writes return tasks',
                'On the Symfony side, Messenger is the natural fit',
            ]
        ),
        new SplitSlide(),
        new Bullet(
            fade: true,
            msg: [
                'Doctrine postFlush or a domain event',
                'Dispatch a message with the entity id',
                'Worker loads the entity, normalizes, and calls Meili',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Same pattern works for full re-indexes and embedding refreshes'
        ),
    ]
)]
function showtime_messenger(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Optional: CSV/JSON → JSONL → entity.
 */
#[AsTask(name: 'showtime:import-pipeline', description: 'Optional import pipeline')]
#[Step(
    description: 'Optional: from files to entities',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'If your data starts as CSV/JSON/ZIP, there is a pipeline:',
                'json:convert – CSV/JSON → JSONL + profile',
                'json:entity – profile → Doctrine entity with sensible types',
                'Then treat the entity like any other for Meili and the API',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'The tutorial walks through these steps in detail; the talk focuses on entities and semantics'
        ),
    ]
)]
function showtime_import_pipeline(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * EasyAdmin-based Meili dashboard.
 */
#[AsTask(name: 'showtime:dashboard', description: 'Bonus: EasyAdmin Meili dashboard')]
#[Step(
    description: 'Bonus: an instant admin dashboard',
    actions: [
        new DisplayCode(
            lang: 'bash',
            note: 'Generate an EasyAdmin dashboard wired to Meili',
            content: <<<'BASH'
bin/console code:meili:admin --path=/
BASH
        ),
        new Bullet(
            fade: true,
            msg: [
                'Generates EasyAdmin dashboard and CRUD controllers',
                'Uses Meili metadata to drive filters and search fields',
                'Produces a convenient developer cockpit for your index',
            ]
        ),
        new BrowserVisit('/', 'http://localhost:24900/ins/0', a: 'riccox-admin.png'),
        new DisplayArtifact('riccox-admin.png', note: 'Riccox Meili admin (optional)'),
    ]
)]
function showtime_dashboard(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Bundle status and vendor-neutral pattern.
 */
#[AsTask(name: 'showtime:bundle-status', description: 'Bundle status and pattern')]
#[Step(
    description: 'About the bundle and the pattern',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Bundle is used in production on my own sites',
                'Still under active development; APIs may evolve',
                'Core idea is stable: define your index with attributes on the entity',
                'Drive Meili settings from entities, not scattered YAML',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'You can reuse the same metadata pattern with other Meili/Symfony bundles'
        ),
    ]
)]
function showtime_bundle_status(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Tutorial slide: step-by-step details available.
 */
#[AsTask(name: 'showtime:tutorial', description: 'Tutorial and repo')]
#[Step(
    description: 'Tutorial: every step documented',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Commands to load each dataset into Meili',
                'CSV/JSON → JSONL → entity generation',
                'Index settings, semantic and hybrid configuration',
                'EasyAdmin dashboard and demo scripts',
            ]
        ),
        new Bullet(
            style: 'callout',
            msg: 'Slides and code are available in the GitHub repo and tutorial page'
        ),
    ]
)]
function showtime_tutorial(): void
{
    RunStep::run(_actions_from_current_task(), context());
}

/**
 * Recap.
 */
#[AsTask(name: 'showtime:recap', description: 'Recap slide')]
#[Step(
    description: 'Recap',
    actions: [
        new Bullet(
            fade: true,
            msg: [
                'Your Doctrine entity can describe DB schema, API, Meili index and semantics',
                'Semantic and hybrid search are practical, cheap and powerful',
                'Attributes give a straightforward way to configure all of this',
                'The tutorial and repo cover the step-by-step details',
            ]
        ),
    ]
)]
function showtime_recap(): void
{
    RunStep::run(_actions_from_current_task(), context());
}
