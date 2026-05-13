---
name: sql-collation-safety
description: Use when adding or changing SQL joins, WHERE comparisons, temporary tables, segment filters, subscriber synchronization, or WooCommerce queries that compare text columns across WordPress, WooCommerce, and MailPoet tables.
---

# SQL Collation Safety

## Overview

MailPoet runs on WordPress databases where text columns can use compatible but different collations. A direct comparison such as `wp_users.user_email = mailpoet_subscribers.email` can fail on some sites with `Illegal mix of collations`.

Use this skill whenever a change compares text columns across WordPress, WooCommerce, and MailPoet tables.

## What to Check

- Look for joins or comparisons on text columns, especially email, status, name, slug, country, and meta values.
- Treat cross-table comparisons as risky when one side is a WordPress/WooCommerce table and the other side is a MailPoet table.
- Check raw SQL, Doctrine query builders that contain SQL snippets, temporary-table joins, segment filters, activation code, synchronization code, and migrations.

## Required Handling

When adding or changing a risky text comparison:

1. Prefer the existing `MailPoet\Util\DBCollationChecker` helper.
2. Apply the returned `COLLATE ...` clause to the target column in the comparison.
3. If the helper is not a good fit, document why and use the smallest explicit collation handling needed.
4. Add or update an integration test that forces mismatched but compatible collations and executes the affected query.

Good local examples:

- `mailpoet/lib/Util/DBCollationChecker.php`
- `mailpoet/lib/Segments/WP.php`
- `mailpoet/tests/integration/Segments/WPTest.php`

## Test Expectations

A regression test should:

- Change one compared column to a compatible but different collation.
- Assert the setup really produced different collations.
- Execute the real query path, not just inspect generated SQL.
- Restore the original column collation in a `finally` block.

If the comparison runs during activation, user synchronization, or WooCommerce segment filtering, prefer an integration test over a unit test because MySQL is the failure surface.
