# Scoring Methodology

This document explains the NLP pipeline that transforms raw Wealthtender advisor reviews into the seven-dimensional scores powering the analytics dashboard.

## Overview

The system uses sentence-transformer embeddings and cosine similarity to score free-text reviews against seven expert-authored dimension queries. Each review receives a 0–1 similarity score per dimension — no fine-tuning, no labeled training data, no LLM inference at query time. The scores are computed in batch by the Python pipeline and served as static CSV artifacts.

## Pipeline Stages

### 1. Cleaning (`pipeline/clean.py`)

Raw review exports from Wealthtender contain noise that must be removed before embedding:

- **Deduplication**: Exact and near-duplicate reviews are removed.
- **Prompt stripping**: Survey prompt text embedded in responses is regex-stripped (e.g., "Things you value in your advisor:", "On a scale of 1–10:").
- **Boilerplate removal**: Compliance disclaimers like "This reviewer received no compensation…" are stripped.
- **Token filtering**: Reviews below 150 total tokens or 75 informative tokens are dropped — very short reviews don't produce meaningful embeddings.
- **Minimum review count**: Entities with fewer than 5 reviews are excluded from scoring (they remain in the EDA dataset).
- **Test account exclusion**: Known test advisor accounts are filtered out by ID.

Output: `artifacts/scoring/reviews_clean.csv` — all reviews that pass cleaning (used by the EDA page), and a filtered subset that proceeds to embedding.

### 2. Embedding (`pipeline/embed.py`)

Each cleaned review is embedded into a 384-dimensional vector using the `all-MiniLM-L6-v2` sentence-transformer model. This is a lightweight, widely-used model optimized for semantic similarity tasks.

The pipeline also computes **entity-level embeddings** — weighted averages of all review embeddings for each advisor or firm, with more recent reviews weighted higher (time-decay weighting).

Output: Intermediate parquet files in `data/intermediate/` (gitignored, regenerable).

### 3. Scoring (`pipeline/score.py`)

Seven dimension queries (see below) are embedded using the same model. Each review's embedding is compared to each query embedding via **cosine similarity**, producing a score between 0 and 1.

Higher scores mean the review's language is more semantically aligned with that dimension's description. A review praising an advisor's transparency and honesty will score high on `trust_integrity`; one describing prompt email responses will score high on `responsiveness_availability`.

Entity-level scores are aggregated from review-level scores using three methods:

- **Mean**: Simple average of all review scores for that entity.
- **Penalized mean**: Mean with a penalty for entities with few reviews, shrinking scores toward the population mean (addresses small-sample noise).
- **Weighted mean**: Time-weighted average giving more influence to recent reviews.

Output: `artifacts/scoring/review_dimension_scores.csv` (review-level) and entity-level aggregate files.

### 4. Enrichment (`pipeline/enrich_comparisons.py`)

Adds percentile rankings, tier labels, and partner-group comparison artifacts:

- **Percentiles**: Each entity's score is ranked against the full pool to produce a 0–100 percentile per dimension.
- **Tiers**: Percentile-based labels — "Outstanding" (90th+), "Excellent" (75th–89th), "Strong" (50th–74th), "Developing" (25th–49th), "Emerging" (below 25th).
- **Composite score**: Weighted average across all seven dimensions, also percentile-ranked.
- **Partner groups**: Pre-defined groupings of entities (e.g., by network affiliation) for group-level comparison views.

## The Seven Dimensions

Each dimension is defined by a concise expert-authored query (two to three sentences, ~30–45 words) that describes the ideal client experience for that trait. Shorter, tighter queries produce cleaner semantic matches against the short, focused language clients actually use in reviews. The full query texts are in `pipeline/config.py` (and mirrored in `wealthtender-analytics/includes/constants.php` as `wt_get_dim_query_texts()` for display). Summary:

### Trust & Integrity (`trust_integrity`)
Fiduciary duty, honesty, transparency about fees, ethical integrity.

### Customer Empathy & Personalization (`listening_personalization`)
Active listening, understanding personal goals, a customized and personalized roadmap instead of a generic approach.

### Communication Clarity (`communication_clarity`)
Explaining complex concepts clearly without jargon, full logic and rationale behind every recommendation.

### Responsiveness (`responsiveness_availability`)
Exceptional customer service, accessibility, prompt calls/emails, fast support on urgent questions.

### Life Event Support (`life_event_support`)
Compassion, empathy, and emotional support through major transitions like divorce, college, and loss.

### Investment Expertise (`investment_expertise`)
Confidence in market knowledge, technical expertise, and skilled investment strategy that navigates asset allocation and risk.

### Outcomes & Results (`outcomes_results`)
Tangible results and delivery on the milestones and life goals clients hired them for.

## Why Cosine Similarity?

Cosine similarity between sentence embeddings is a well-established approach for zero-shot text classification. Advantages for this use case:

- **No labeled data needed**: The dimension queries serve as the "labels" — no manual annotation of thousands of reviews.
- **Interpretable**: Scores directly measure how semantically similar a review is to each dimension description.
- **Fast**: Embedding + similarity is a batch matrix operation. No LLM inference at query time.
- **Stable**: The `all-MiniLM-L6-v2` model is a frozen, published checkpoint. Re-running the pipeline on the same data produces identical results.

## Key Design Decisions

**Two datasets, not one.** The EDA page shows all cleaned reviews (including those too short to score reliably). The Advisor DNA page shows only scored reviews. This is intentional — the EDA is for exploring the full dataset, while scoring requires enough text to produce meaningful embeddings.

**Scores are relative, not absolute.** A score of 0.45 on `trust_integrity` doesn't mean "45% trustworthy." It means the review language has 0.45 cosine similarity with the trust query. Percentile rankings (computed against the full pool) are the more meaningful metric for comparing entities.

**No real-time computation.** The plugin reads pre-computed CSVs. If you need updated scores, re-run the pipeline and replace the artifacts. This was a deliberate architectural choice for WordPress compatibility — no GPU, no Python runtime, no background workers.

## CSV Artifact Schema

### `reviews_clean.csv` (EDA dataset)
All cleaned reviews, including unscored short ones.

| Column | Description |
|---|---|
| `ID` | Unique review identifier |
| `review_text` | Cleaned review text |
| `advisor_url` / `firm_url` | Entity identifiers |
| `advisor_name` / `firm_name` | Display names |
| `star_rating` | 1–5 star rating |
| `review_date` | Date of review |
| `token_count` | Token count after cleaning |

### `review_dimension_scores.csv` (Scored dataset)
Reviews that passed quality thresholds, with dimension scores.

| Column | Description |
|---|---|
| `review_idx` | Review index |
| `entity_id` | Advisor or firm identifier |
| `entity_name` | Display name |
| `entity_type` | `"advisor"` or `"firm"` |
| `trust_integrity` | 0–1 cosine similarity score |
| `listening_personalization` | 0–1 cosine similarity score |
| `communication_clarity` | 0–1 cosine similarity score |
| `responsiveness_availability` | 0–1 cosine similarity score |
| `life_event_support` | 0–1 cosine similarity score |
| `investment_expertise` | 0–1 cosine similarity score |
| `outcomes_results` | 0–1 cosine similarity score |

## Reproducing Results

```bash
# From the repo root
pip install -r requirements-pipeline.txt
cp /path/to/wealthtender_reviews.csv data/raw/
python -m pipeline.run
```

The pipeline is deterministic given the same input CSV and model checkpoint. All configuration (paths, thresholds, dimension queries, stopwords) lives in `pipeline/config.py`.
