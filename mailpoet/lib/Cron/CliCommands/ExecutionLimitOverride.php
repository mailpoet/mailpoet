<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Overrides MailPoet's cron execution limit for the duration of a callback.
 *
 * The cron machinery enforces a 20-second limit via the `mailpoet_cron_get_execution_limit`
 * filter. When running a worker from WP-CLI we usually want it to run to completion, so the
 * default is to lift the cap entirely; a caller may instead pass a number of seconds to cap it.
 * The filter is always removed afterwards (including on exceptions) so global state never leaks.
 */
class ExecutionLimitOverride {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * @template T
   * @param int|null $seconds Cap in seconds, or null to lift the limit entirely.
   * @param callable():T $fn
   * @return T
   */
  public function overrideDuring(?int $seconds, callable $fn) {
    if ($seconds !== null && $seconds < 0) {
      throw new InvalidArgumentException(sprintf('Execution limit must be a non-negative number of seconds, got %d.', $seconds));
    }
    $limit = $seconds ?? PHP_INT_MAX;
    $filter = function () use ($limit) {
      return $limit;
    };

    $this->wp->addFilter('mailpoet_cron_get_execution_limit', $filter, PHP_INT_MAX);
    try {
      return $fn();
    } finally {
      $this->wp->removeFilter('mailpoet_cron_get_execution_limit', $filter, PHP_INT_MAX);
    }
  }
}
