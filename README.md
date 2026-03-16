# Wealthtender Analytics — WordPress Plugin

A WordPress admin plugin that gives Wealthtender an interactive analytics dashboard for their financial-advisor review data. The dashboard is built on Plotly.js and powered by a PHP REST API that reads pre-computed CSV artifacts. No external database, no Python at runtime — just static CSV files served through the WP REST API.

This repo contains everything needed to run the dashboard locally or deploy it to the Wealthtender production WordPress site.

## Quick Start (Local Dev)

Prerequisites: Docker Desktop.

```bash
git clone <repo-url> && cd wt-msba-wp-plugin

# Place your raw reviews CSV
cp /path/to/wealthtender_reviews.csv data/raw/

# Run the NLP pipeline (one-time, generates artifacts/)
pip install -r requirements-pipeline.txt
python -m pipeline.run

# Start WordPress + MySQL
docker compose up -d
```

Open `http://localhost:8080`, complete the WordPress install wizard, then activate **Wealthtender Analytics** from Plugins → Installed Plugins. The dashboard appears as a top-level "WT Analytics" menu item.

To tear down: `docker compose down` (add `-v` to also wipe the database volume).

## Repository Layout

```
wt-msba-wp-plugin/
├── wealthtender-analytics/      # ← THE PLUGIN (this is what gets deployed)
│   ├── wealthtender-analytics.php   # Plugin entry point
│   ├── includes/
│   │   ├── artifacts.php            # Data layer — reads CSVs, computes scores
│   │   ├── constants.php            # Dimensions, colors, stopwords
│   │   ├── rest-api.php             # WP REST API endpoints (wt/v1/…)
│   │   └── roles.php                # Role-based access (admin vs firm)
│   └── admin/
│       ├── css/wt-theme.css         # Full dashboard stylesheet
│       ├── js/wt-common.js          # Shared utilities (WT.api, WT.plot, …)
│       ├── js/wt-eda.js             # EDA page logic
│       ├── js/wt-advisor-dna.js     # Advisor DNA page logic
│       ├── js/wt-benchmarks.js      # Benchmarks page logic
│       ├── js/wt-leaderboard.js     # Leaderboard page logic
│       ├── js/wt-comparisons.js     # Comparisons page logic
│       ├── js/wt-team-comparisons.js # Team Comparisons page logic
│       ├── js/wt-methodology.js     # Methodology page logic
│       ├── js/wt-splash.js          # Home/splash page logic
│       └── pages/*.php              # Page templates (one per tab)
│
├── pipeline/                    # Python NLP pipeline (offline, not needed at runtime)
│   ├── run.py                       # Entry point: python -m pipeline.run
│   ├── clean.py                     # Data cleaning & deduplication
│   ├── embed.py                     # Sentence-transformer embeddings
│   ├── score.py                     # Cosine-similarity dimension scoring
│   ├── enrich_comparisons.py        # Partner-group & comparison artifacts
│   └── config.py                    # Paths, dimension queries, thresholds
│
├── artifacts/                   # Pipeline output — CSVs the plugin reads
│   ├── scoring/                     # review_dimension_scores.csv, entity scores
│   ├── macro_insights/              # Aggregate stats, distributions
│   └── metadata.json                # Pipeline run metadata
│
├── data/
│   ├── raw/                         # Your input CSV (gitignored)
│   └── intermediate/                # Embeddings, parquet files (gitignored)
│
├── Brandbook/assets/            # SVG logos and brand images for the navbar
├── docker-compose.yml           # Local dev: WordPress 6.4 + MySQL 8.0
├── requirements-pipeline.txt    # Python deps for the NLP pipeline
├── LICENSE                      # Apache 2.0
└── NOTICE                       # Team attribution
```

## Deploying to Production

The plugin is self-contained in `wealthtender-analytics/`. To deploy:

1. **Copy the plugin folder** into `wp-content/plugins/wealthtender-analytics/` on the Wealthtender WordPress server.

2. **Copy the artifacts** into `wp-content/plugins/wealthtender-analytics/data/artifacts/`. The plugin reads CSVs from this path at runtime. No Python, no cron — the artifacts are static files.

3. **Copy the brand assets** into `wp-content/plugins/wealthtender-analytics/data/brand/`. These are the SVG logos used in the navbar.

4. **Activate the plugin** from WP Admin → Plugins.

That's it. The plugin registers its own admin menu, REST routes, roles, scripts, and styles — no theme modifications needed.

### What the Plugin Does NOT Require

- No custom database tables
- No external API keys or third-party services
- No Python or Node.js at runtime
- No wp-cron jobs
- No theme template overrides
- No Composer or npm dependencies

The only external resource loaded at runtime is Plotly.js from `cdn.plot.ly`.

## Architecture

### Data Flow

```
Raw CSV → pipeline/ (Python, offline) → artifacts/ (CSVs) → PHP REST API → Plotly.js charts
```

The Python pipeline runs once (or whenever new reviews arrive) to produce scored CSVs. The WordPress plugin reads those CSVs through `artifacts.php` and serves them via the REST API. The JavaScript modules fetch from the API and render Plotly charts client-side.

### REST API

All endpoints live under `wp-json/wt/v1/` and require an authenticated WordPress session (any logged-in user with a WT role). The endpoints:

| Endpoint | Purpose |
|---|---|
| `GET /health` | Heartbeat (public) |
| `GET /metadata` | Pipeline run metadata |
| `GET /entities` | List of all firms and advisors |
| `GET /stopwords` | English + domain stopwords for EDA |
| `GET /eda/charts` | Full EDA payload (distributions, scatter, n-grams, time series) |
| `GET /eda/review/{id}` | Single review detail (from reviews_clean.csv) |
| `GET /advisor-dna/macro-totals` | Aggregate KPIs for scored reviews |
| `GET /advisor-dna/macro-sample` | Random sample of scored reviews |
| `GET /advisor-dna/entity-reviews` | All scored reviews for one entity |
| `GET /advisor-dna/advisor-scores` | Enriched dimension scores for one entity |
| `GET /advisor-dna/percentile-scores` | Percentile-ranked scores for one entity |
| `GET /advisor-dna/method-breakpoints` | Scoring method tier breakpoints |
| `GET /advisor-dna/review/{id}` | Single scored review detail |
| `GET /benchmarks/pool-stats` | Pool-level statistics |
| `GET /benchmarks/distributions` | Score distributions by dimension |
| `GET /leaderboard` | Top-N ranked entities |
| `GET /comparisons/partner-groups` | Available partner groups |
| `GET /comparisons/partner-group/{code}` | Members of one partner group |
| `GET /comparisons/entities` | Multi-entity comparison |
| `GET /comparisons/head-to-head` | Two-entity detailed comparison |

### Role-Based Access

The plugin defines two roles:

- **Admin** (`wt_admin_access`): Full access to all pages. WordPress administrators get this automatically.
- **Firm** (`wt_firm_access`): Restricted to Advisor DNA, Benchmarks, Leaderboard, Comparisons, and Team Comparisons. No access to EDA or raw review data.

To give a Wealthtender client firm access: create a WordPress user, assign them the "Firm Portal User" role (created on plugin activation), and they'll see the restricted dashboard when they log in.

### Full-Screen App Mode

The plugin hides WordPress admin chrome (sidebar, admin bar, footer) via CSS and renders its own branded navbar. The dashboard looks and feels like a standalone SPA, not a typical WP admin page. This is handled by:

- `wt_fullscreen_head_styles()` — removes WP toolbar padding
- `wt_admin_body_class()` — adds `.wt-fullscreen-app` class
- `wt-theme.css` — hides `#wpadminbar`, `#adminmenumain`, `#wpfooter`

### JavaScript Architecture

All JS modules follow the same pattern:

```javascript
(function($) {
    'use strict';
    // State object, init(), bindEvents(), loadData(), renderChart()
})(jQuery);
```

Key shared utilities in `wt-common.js`:

- `WT.api(endpoint, params, success, error)` — wrapper around `$.ajax` to the REST API
- `WT.plot(el, traces, layout)` — wrapper around `Plotly.newPlot` with spinner cleanup, error handling, and responsive config
- `WT.showLoading(selector)` — renders a loading spinner into a container
- `WT.emptyState(selector, message)` — renders an empty-state placeholder
- `WT.baseLayout(overrides)` — returns a Plotly layout merged with brand defaults

## Dashboard Pages

| Page | What It Shows |
|---|---|
| **Home** | Welcome splash with KPI cards |
| **EDA** | Exploratory data analysis — rating distributions, token-length scatter, n-gram frequency, time series. Uses `reviews_clean.csv` (all reviews, including unscored). |
| **Advisor DNA** | Per-entity deep dive — dimension radar charts, review-level scatter plots, score tables. Uses `review_dimension_scores.csv` (scored reviews only). |
| **Benchmarks** | Pool-level score distributions — violin/box plots per dimension, filterable by method, entity type, and pool. |
| **Leaderboard** | Top-N ranked bar chart with click-to-compare. Select up to two entities for side-by-side radar + table. |
| **Comparisons** | Multi-entity comparison — partner groups or manual entity selection. Radar overlay + difference table. |
| **Team Comparisons** | Firm-level team view — all advisors within a firm compared side by side. |
| **Methodology** | Static page explaining the scoring methodology (rendered from JS). |

## Data Pipeline

The Python pipeline transforms raw Wealthtender review exports into the scored CSVs the plugin consumes. It runs offline — completely independent of WordPress.

```bash
pip install -r requirements-pipeline.txt
python -m pipeline.run
```

Pipeline stages:

1. **clean.py** — Deduplicates reviews, strips boilerplate/prompt text, filters by token count, removes test accounts.
2. **embed.py** — Generates sentence embeddings using `all-MiniLM-L6-v2` (384-dim). Also generates per-entity weighted average embeddings.
3. **score.py** — Computes cosine similarity between each review embedding and six dimension query embeddings. Produces `review_dimension_scores.csv` and entity-level aggregates.
4. **enrich_comparisons.py** — Builds partner-group comparison artifacts and enriched entity scores.

Output lands in `artifacts/`. The Docker Compose file mounts this directory into the plugin's `data/artifacts/` path.

### Updating Scores with New Reviews

When Wealthtender has new reviews:

1. Export a fresh `wealthtender_reviews.csv` into `data/raw/`.
2. Run `python -m pipeline.run`.
3. Copy the updated `artifacts/` to the production server's `data/artifacts/` path (or re-deploy the plugin folder).

No WordPress restart needed — the plugin reads CSVs fresh on each API request (with PHP `static` caching within a single request lifecycle).

## Six Scoring Dimensions

| Key | Label | What It Measures |
|---|---|---|
| `trust_integrity` | Trust & Integrity | Fiduciary duty, honesty, transparency |
| `listening_personalization` | Customer Empathy | Listening, personalization, understanding goals |
| `communication_clarity` | Communication Clarity | Plain-English explanations, regular updates |
| `responsiveness_availability` | Responsiveness | Accessibility, prompt replies, availability |
| `life_event_support` | Life Event Support | Guidance through retirement, loss, transitions |
| `investment_expertise` | Investment Expertise | Technical skill, market knowledge, credentials |

Each review gets a 0–1 cosine-similarity score per dimension. Entity scores are aggregated via mean, penalized-mean, or weighted-mean methods. Percentiles and tiers are computed relative to the full pool. See `METHODOLOGY.md` for the full analytical methodology.

## Docker Compose (Local Dev)

The `docker-compose.yml` provides:

- **WordPress 6.4** (PHP 8.2, Apache) on port 8080
- **MySQL 8.0** on port 3306

Volume mounts:

- `./wealthtender-analytics` → plugin directory (live-reloads PHP/JS/CSS changes)
- `./artifacts` → `data/artifacts/` inside the plugin
- `./Brandbook/assets` → `data/brand/` inside the plugin

Credentials: `wordpress` / `wordpress` for both MySQL and WP database config.

## License

Apache 2.0. See `LICENSE` and `NOTICE`.
