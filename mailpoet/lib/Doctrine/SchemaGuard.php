<?php declare(strict_types = 1);

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;
use MailPoetVendor\Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Runs a read that touches a column or table a migration may not have added yet.
 *
 * During a plugin update the new code is live before its migrations have run, so
 * anything mapping a new column can hit a database that does not have it. Each
 * new column has reintroduced this: tracking_consent in STOMAIL-8268, then
 * tracking_allowed in STOMAIL-8310, which returned a 500 from the automation
 * analytics endpoint on an ordinary page load.
 *
 * Two things have to happen, and doing only the first is what made the
 * tracking_allowed fix look complete when it was not:
 *
 * 1. Catch the schema exception, so the caller can fall back instead of dying.
 * 2. Suppress wpdb's error output. wpdb prints the failed query before Doctrine
 *    ever raises it, so a caught exception still leaves a block of "WordPress
 *    database error" HTML glued to the front of the response body — which broke
 *    the newsletters listing JSON even after the exception was handled.
 *
 * Deliberately narrow. It catches only the two schema exceptions, so a genuine
 * database fault still surfaces rather than being quietly turned into a
 * fallback value, and it suppresses errors for the wrapped read only.
 *
 * This is not the schema-readiness gate described in STOMAIL-8268's follow-ups.
 * It is the shared piece those per-call-site guards should have had, so the next
 * column does not need a fourth hand-written copy of the same try/catch.
 */
class SchemaGuard {
  /**
   * @template T
   * @param callable():T $read Executed immediately.
   * @param T $fallback Returned when the schema is not ready yet.
   * @return T
   */
  public function readOr(callable $read, $fallback) {
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      return $read();
    } catch (InvalidFieldNameException | TableNotFoundException $e) {
      return $fallback;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }
}
