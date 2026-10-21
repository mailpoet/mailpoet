<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Migrator\Migrator;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;
use Throwable;

/**
 * Tells administrators that the database update failed, or that MailPoet could not start, and why.
 *
 * Migration errors can name tables, queries and server paths, so the detail is shown
 * only to users with manage_options; other wp-admin users get a one-line notice.
 */
class UpdateFailureNotifier {
  private Migrator $migrator;

  private WPFunctions $wp;

  public function __construct(
    Migrator $migrator,
    WPFunctions $wp
  ) {
    $this->migrator = $migrator;
    $this->wp = $wp;
  }

  public function notify(Throwable $failure): void {
    WPNotice::displayError($this->render($failure), '', '', false, false);
  }

  public function hasRecordedFailures(): bool {
    try {
      return $this->migrator->getFailedMigrations() !== [];
    } catch (Throwable $e) {
      return false;
    }
  }

  public function render(Throwable $failure): string {
    if (!$this->wp->currentUserCan('manage_options')) {
      return '<p>' . esc_html__('MailPoet database update failed. Please contact the site administrator.', 'mailpoet') . '</p>';
    }

    $html = '<p><strong>' . esc_html__('MailPoet database update failed.', 'mailpoet') . '</strong></p>';
    $html .= $this->renderFailedMigrations($failure);
    $command = 'wp mailpoet migrations run';
    if ($this->wp->isMultisite()) {
      // WP-CLI works on the main site unless told which site to use
      $command .= ' --url=' . $this->wp->homeUrl();
    }
    $html .= '<p>' . esc_html__('MailPoet retries the update on every page load. To retry it manually, run:', 'mailpoet') . ' <code>' . esc_html($command) . '</code></p>';
    $html .= '<p>' . esc_html__('Include the full error when contacting MailPoet support.', 'mailpoet') . '</p>';
    return $html;
  }

  /**
   * The stored rows carry the error of each failed migration. The failure that just happened
   * is shown as well when no row explains it, e.g. the run broke before it reached the
   * migration whose row is stored.
   */
  private function renderFailedMigrations(Throwable $failure): string {
    try {
      $failed = $this->migrator->getFailedMigrations();
    } catch (Throwable $e) {
      // the migrations table itself cannot be read; the original failure says more
      return $this->renderFailure($failure);
    }
    $html = '';
    // the activation lock is not a failure of its own, so it needs no explaining
    $explained = $failure instanceof ActivationInProgressException;
    foreach ($failed as $migration) {
      $explained = $explained || strpos($failure->getMessage(), $migration['name']) !== false;
      $error = (string)$migration['error'];
      $html .= '<p>' . sprintf(
        // translators: %1$s is the migration name, %2$d how many times it was retried, %3$s the error
        esc_html__('%1$s (retries: %2$d): %3$s', 'mailpoet'),
        esc_html($migration['name']),
        (int)($migration['retries'] ?? 0),
        esc_html(wp_check_invalid_utf8(explode("\n", $error, 2)[0], true))
      ) . '</p>';
      $html .= $this->renderDump(
        // translators: %s is the migration name
        sprintf(esc_html__('Full error: %s', 'mailpoet'), esc_html($migration['name'])),
        $error
      );
    }
    return $explained ? $html : $html . $this->renderFailure($failure);
  }

  /**
   * For a failure outside the database update, e.g. a service that cannot start.
   */
  public function renderStartupFailure(Throwable $failure): string {
    if (!$this->wp->currentUserCan('manage_options')) {
      return '<p>' . esc_html__('MailPoet could not start. Please contact the site administrator.', 'mailpoet') . '</p>';
    }
    return '<p><strong>' . esc_html__('MailPoet could not start.', 'mailpoet') . '</strong></p>'
      . $this->renderFailure($failure)
      . '<p>' . esc_html__('Include the full error when contacting MailPoet support.', 'mailpoet') . '</p>';
  }

  private function renderFailure(Throwable $failure): string {
    return '<p>' . esc_html($failure->getMessage()) . '</p>'
      . $this->renderDump(esc_html__('Full error', 'mailpoet'), (string)$failure);
  }

  /**
   * WP\Notice runs nl2br() over the message, so the dump gets its line breaks here and
   * carries no newline characters of its own.
   */
  private function renderDump(string $summary, string $dump): string {
    // esc_html() returns '' for text with a single invalid byte, which a PHP 7.4 trace
    // can contain where it cuts a multibyte argument short
    $lines = str_replace(["\r\n", "\n"], '<br>', esc_html(wp_check_invalid_utf8($dump, true)));
    return '<details><summary>' . $summary . '</summary><p style="font-family: monospace">' . $lines . '</p></details>';
  }
}
