<?php declare(strict_types=1);

/**
 * file: castor/meili-products.castor.php
 * Demo: 2-entity Meilisearch + Symfony site
 * Entity 1: Product (DummyJSON)
 * Entity 2: Movie (profile + codegen teaser)
 */

use Castor\Attribute\AsTask;
use Survos\StepBundle\Metadata\Step;

#[AsTask(name: 'slides:meili-products')]
function slides_meili_products(): void
{
}

/**
 * # Goal: A Two-Entity Search Site
 *
 * We’re going to build a **2-entity website**:
 *
 * - **Products** from DummyJSON (traditional Symfony → Meili → semantic search)
 * - **Movies** using analysis + code generation tools
 *
 * Along the way:
 *
 * - Start with a *plain* Doctrine entity and importer  
 * - Add Meilisearch search & filtering with one attribute + one command  
 * - Auto-generate an **EasyAdmin dashboard + InstantSearch UI**  
 * - Upgrade to **semantic search** with a single embedder configuration
 */
#[Step(label: 'Overview: 2-Entity Meili Site')]
function step_meili_products_overview(): void
{
}

/**
 * # Traditional First: Product with Doctrine
 *
 * We start with the **classic Symfony workflow**:
 *
 * 1. `bin/console make:entity Product`  
 * 2. Add fields that match DummyJSON  
 * 3. Create a command that fetches the data and imports it  
 * 4. Run the command and verify rows in the database
 *
 * No Meilisearch yet — just **Doctrine doing its thing**.
 */
#[Step(label: 'Traditional Symfony First')]
function step_traditional_intro(): void
{
}

/**
 * # Step 1 — Create the `Product` Entity
 *
 * Use the Maker bundle:
 *
 * ```bash
 * bin/console make:entity Product
 * ```
 *
 * Add fields to roughly match DummyJSON:
 *
 * - `title` (string, 255)  
 * - `description` (text)  
 * - `category` (string, 255)  
 * - `price` (float)  
 * - `thumbnail` (string, 255)  
 *
 * At this point:
 *
 * - Doctrine knows about `Product`  
 * - You can already run migrations and see an **empty** table
 */
#[Step(label: 'Create Product Entity')]
function step_make_entity_product(): void
{
}

/**
 * # Step 2 — Add the Import Command
 *
 * We want to import from:
 *
 * - `https://dummyjson.com/products`
 *
 * So we create a command:
 *
 * ```bash
 * bin/console make:command app:import:products
 * ```
 *
 * That command:
 *
 * - Fetches JSON from DummyJSON  
 * - Loops over the products array  
 * - Creates new `Product` entities  
 * - Persists them via Doctrine (with batching)
 */
#[Step(label: 'Create Import Command')]
function step_create_import_command(): void
{
}

/**
 * # Step 3 — Run the Import
 *
 * Once the command is in place:
 *
 * ```bash
 * bin/console app:import:products
 * ```
 *
 * Result:
 *
 * - The **Product** table is populated  
 * - You can see data via `doctrine:query:sql`, Symfony Profiler, or a simple CRUD controller  
 *
 * At this point we have:
 *
 * - A normal Symfony/Doctrine app  
 * - **No search UI** and no Meilisearch yet
 */
#[Step(label: 'Run Import & Verify Data')]
function step_run_import_products(): void
{
}

/**
 * # Now: Add Full-Text Search & Filtering
 *
 * Next step: turn this into a **searchable catalog**.
 *
 * We want:
 *
 * - Full-text search on title and description  
 * - Filtering by category and price  
 * - Sorting by price  
 *
 * With SurvosMeiliBundle this is:
 *
 * - One attribute on the entity  
 * - One command to sync settings  
 * - Import code that automatically populates Meili
 */
#[Step(label: 'From Plain Doctrine to Search')]
function step_intro_meili_search(): void
{
}

/**
 * # Declare a Meilisearch Index with `#[MeiliIndex]`
 *
 * On the `Product` entity we add:
 *
 * ```php
 * use Survos\MeiliBundle\Metadata\MeiliIndex;
 *
 * #[MeiliIndex(
 *     searchable: ['title', 'description'],
 *     filterable: ['category', 'price'],
 *     sortable: ['price']
 * )]
 * class Product
 * {
 *     // fields...
 * }
 * ```
 *
 * That single attribute:
 *
 * - Declares the **index name** (by convention)  
 * - Tells Meili which fields are searchable, filterable, and sortable  
 * - Drives both **backend config** and **frontend UI** later
 */
#[Step(label: 'Add #[MeiliIndex] to Product')]
function step_add_meiliindex_attribute(): void
{
}

/**
 * # Sync the Meili Index Settings
 *
 * With the attribute in place, we configure Meili:
 *
 * ```bash
 * bin/console meili:settings:update --force
 * ```
 *
 * This command:
 *
 * - Creates the index if it doesn’t exist  
 * - Applies the `searchable`, `filterable` and `sortable` fields  
 * - Is conceptually similar to `doctrine:schema:update` (but for Meili)
 *
 * Now Meili is ready to accept `Product` documents.
 */
#[Step(label: 'Create/Update Meili Index')]
function step_sync_meili_settings(): void
{
}

/**
 * # Populating Meili from Doctrine
 *
 * When we import entities, Doctrine dispatches lifecycle events.
 *
 * Our Meili integration listens for the relevant events (e.g. `postPersist` / `postUpdate`) and:
 *
 * - Converts `Product` entities into arrays  
 * - Pushes them into Meilisearch  
 * - Keeps Meili **in sync** with Doctrine transparently
 *
 * So:
 *
 * - Running the **exact same import command** is enough  
 * - Doctrine saves to the DB  
 * - Meili is updated automatically
 */
#[Step(label: 'Doctrine → Meili Sync')]
function step_doctrine_to_meili_sync(): void
{
}

/**
 * # Inspecting the Index in riccox
 *
 * Once Products are in Meili, you can inspect them via the Meilisearch dashboard:
 *
 * - URL: `http://localhost:24900/`
 *
 * There you can:
 *
 * - Inspect the `products` index  
 * - See documents as JSON  
 * - Verify searchable/filterable/sortable settings  
 *
 * This is our **low-level view** of what the bundle did for us.
 */
#[Step(label: 'View Index in riccox')]
function step_view_riccox_dashboard(): void
{
}

/**
 * # From Config to UI: Auto-Generated Admin & Search
 *
 * Low-level JSON is nice, but we want:
 *
 * - An **EasyAdmin dashboard** with CRUD + Meili-powered listing  
 * - A rich **InstantSearch** UI with facets and search-as-you-type
 *
 * Instead of hand-writing controllers and Twig templates, we use code generation:
 *
 * ```bash
 * bin/console code:meili:admin
 * ```
 */
#[Step(label: 'Why Code Generation?')]
function step_why_code_generation(): void
{
}

/**
 * # `code:meili:admin` — One Command, Full UI
 *
 * Command:
 *
 * ```bash
 * bin/console code:meili:admin
 * ```
 *
 * What it does:
 *
 * - Scans for entities tagged with `#[MeiliIndex]`  
 * - Generates an **EasyAdmin dashboard** for them  
 * - Adds a dedicated page that:
 *   - Reads the Meili index
 *   - Uses the declared facets
 *   - Builds a beautiful, fast search UI
 *
 * Result:
 *
 * - You immediately get an admin + search UI for **Product**.
 */
#[Step(label: 'Generate Admin + Search UI')]
function step_run_code_meili_admin(): void
{
}

/**
 * # Facets, Filters, Sorting: All from the Attribute
 *
 * You’ve seen the `#[MeiliIndex]` attribute where we list:
 *
 * - `searchable` fields  
 * - `filterable` fields  
 * - `sortable` fields  
 *
 * Those same declarations drive:
 *
 * - Meili index configuration  
 * - The generated InstantSearch UI:
 *   - Filters for `category` and `price`
 *   - Sorting options (e.g. price ascending/descending)
 *
 * **One source of truth** for both backend and frontend.
 */
#[Step(label: 'Facet & Sort Config in One Place')]
function step_facets_from_attribute(): void
{
}

/**
 * # The Product Card: Twig… but in the Browser
 *
 * In the demo, each product is shown as a **card**:
 *
 * - Thumbnail image  
 * - Title and description snippet  
 * - Category and price
 *
 * Technically:
 *
 * - It *is* a Twig file  
 * - But it’s rendered by **js-twig in the browser**
 *
 * That means:
 *
 * - Super-fast rendering (no extra HTTP calls per hit)  
 * - Still feels like Twig templating  
 * - Powered directly by Meili search results in the browser
 */
#[Step(label: 'Product Card Rendering with js-twig')]
function step_product_card_js_twig(): void
{
}

/**
 * # js-twig vs Server-Side Twig
 *
 * js-twig is a Twig implementation that runs **in the browser**:
 *
 * - Very similar syntax to normal Twig  
 * - Some features aren’t available, but the common ones are
 *
 * We also provide helpers:
 *
 * - `path()` for generating URLs from routes  
 * - Hooks for **Stimulus** functions  
 *
 * So you get:
 *
 * - Familiar Twig-like templates  
 * - Executed entirely client-side for performance
 */
#[Step(label: 'js-twig Capabilities')]
function step_js_twig_capabilities(): void
{
}

/**
 * # Level Up: Add Semantic Search
 *
 * Keyword search + filters are great...
 *
 * But sometimes you want:
 *
 * - “laptop for photo editing”  
 * - “budget phone with good camera”  
 *
 * …even if those exact words never appear in the data.
 *
 * That’s where **semantic search** with embeddings comes in.
 */
#[Step(label: 'Why Semantic Search?')]
function step_intro_semantic_search(): void
{
}

/**
 * # Enable Semantic Search: Configuration Steps
 *
 * To enable semantic search:
 *
 * 1. Get an **OpenAI API key**  
 * 2. Configure an **embedder** in `survos_meili` config  
 * 3. Add the embedder to the `#[MeiliIndex]` attribute on `Product`  
 * 4. Update Meili settings with embed support
 *
 * Roughly:
 *
 * - Symfony knows how to call OpenAI  
 * - SurvosMeiliBundle knows how to store embeddings in Meili  
 * - Your index starts supporting vector search
 */
#[Step(label: 'Configure the Embedder')]
function step_configure_embedder(): void
{
}

/**
 * # Run the Semantic Settings Update
 *
 * Once the embedder is configured and attached to `#[MeiliIndex]`:
 *
 * ```bash
 * bin/console meilisearch:settings:update --force --embed
 * ```
 *
 * This:
 *
 * - Updates the index with embedding-related settings  
 * - Ensures Meili is ready for **hybrid / semantic** queries
 *
 * The underlying product data does not change — we just add a new way to search it.
 */
#[Step(label: 'Update Meili with Embeddings')]
function step_update_meili_with_embeddings(): void
{
}

/**
 * # Using Semantic Search in the UI
 *
 * After refreshing the browser, the Product search UI now offers:
 *
 * - The original keyword search box  
 * - A **semantic search** option (e.g. a separate input or a toggle)
 *
 * From the user’s perspective:
 *
 * - Type a natural language query  
 * - Results are ordered by meaning, not just exact word matches  
 *
 * Keyword search + semantic search live happily side by side.
 */
#[Step(label: 'Semantic Search in the Browser')]
function step_semantic_search_ui(): void
{
}

/**
 * # Second Entity: Movies Tab (Teaser)
 *
 * For the **movies** tab, we’ll take a different route:
 *
 * - Use existing CSV/JSON data (e.g. a movies dataset)  
 * - Run it through an analysis step to produce a **Jsonl profile**  
 * - Use **code generation tools** to:
 *   - Generate a `Movie` Doctrine entity
 *   - Generate Meili config and UI templates
 *
 * So:
 *
 * - Products show the **traditional path** (manual entity + import)  
 * - Movies demonstrate the **automated, profile-driven path**
 */
#[Step(label: 'Movies: Analysis & Codegen')]
function step_movies_teaser(): void
{
}
