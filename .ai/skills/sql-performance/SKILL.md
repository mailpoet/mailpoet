---
name: sql-performance
description: Use when adding or changing SQL queries, joins, WHERE/ORDER BY clauses, listing/search/filter queries, statistics aggregation, segment computation, or any query whose cost grows with the number of subscribers, sent emails, or open/click stats.
---

# SQL Performance & Scalability

## Overview

MailPoet runs on installs with hundreds of thousands of subscribers and millions of open/click rows. A query that feels instant on dev data can lock up a listing page or stall a cron worker at that scale. New features regularly regress this — for example, adding a `LIKE '%term%'` search plus an extra JOIN onto the newsletter listing query made the whole listing slow, because the leading wildcard prevents any index from being used.

Use this skill whenever a change adds or modifies a query — especially one that runs on a listing page, in a cron worker, or over the subscriber, sending, or statistics tables.

Related: [[sql-collation-safety]] fires on the same kinds of changes for a different reason.

## Failure modes to avoid

- **Leading-wildcard `LIKE '%term%'`** — non-sargable, forces a full scan. A trailing-only `LIKE 'term%'` can use an index; a leading wildcard cannot.
- **JOINs bolted onto hot queries** — adding a JOIN or subquery to a listing or count query that already runs on every page load multiplies its cost.
- **N+1 / per-row queries in loops** — one query per subscriber or newsletter inside a loop. Batch into a single query with `IN (...)` or a join.
- **Unbounded result sets** — `SELECT` with no `LIMIT` or pagination over a table that grows without bound (subscribers, stats, sending queue).
- **Filtering or `ORDER BY` on unindexed columns** — sorts and filters on large tables need a supporting index.
- **`COUNT` / aggregation over large stats tables** — opens, clicks, and WooCommerce purchases are the largest tables; aggregate them with care and the right index.

## Required handling

When adding or changing a query:

1. Estimate the row count at scale, not on dev data — think 100k+ subscribers and millions of stats rows.
2. Prefer sargable, indexed predicates. Avoid leading-wildcard `LIKE`, and avoid wrapping indexed columns in functions inside the `WHERE`.
3. Paginate or `LIMIT` anything that scans a growing table.
4. Push aggregation into SQL backed by an index instead of loading rows into PHP.
5. If a new access path needs an index, add it via a migration (`pnpm migrations:new db`, see `lib/Migrations/Db/`).
6. When unsure about a query's plan, run `EXPLAIN` against a realistic dataset.

## Pushing back on non-scalable proposals

If a human — or your own first instinct — proposes a query that will not scale, say so before implementing it: name the failure mode, estimate the cost at scale, and offer the scalable alternative. If the tradeoff is accepted intentionally (e.g. "just do it"), proceed — but leave the reasoning visible.

## Local examples

- `lib/Listing/ListingRepository.php` — `applySearch()` is where per-entity listing search (the `LIKE` surface) is implemented; the newsletter-listing regression above lived in this kind of code.
- `lib/Statistics/StatisticsOpensRepository.php`, `lib/Statistics/StatisticsClicksRepository.php` — aggregation over the largest tables.
- `lib/Segments/DynamicSegments/Filters/` — segment computation; these queries run over the full subscriber base.

## Test expectations

There is no cheap unit test for query cost. For changes to hot paths, add or extend a scenario in the `performance` k6 suite (`mailpoet/tests/performance/scenarios.js`, run via `cd mailpoet && ./do test:performance`). At minimum, reason explicitly in the PR about how the query behaves on a large install.
