<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\UpdateFailureNotifier;
use MailPoet\Migrator\Migrator;
use MailPoet\Migrator\MigratorException;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class UpdateFailureNotifierTest extends \MailPoetTest {
  private const DUMP = "Exception: Unknown column 'x' in /plugin/Migration_2.php:12\nStack trace:\n#0 ...";

  private UpdateFailureNotifier $notifier;

  public function _before(): void {
    parent::_before();
    $this->notifier = $this->createNotifier([
      ['name' => 'Migration_2', 'status' => Migrator::MIGRATION_STATUS_FAILED, 'retries' => 3, 'error' => self::DUMP, 'unknown' => false],
    ]);
    wp_set_current_user(0);
  }

  public function testItKeepsTheDetailFromUsersWithoutManageOptions(): void {
    $html = $this->notifier->render(MigratorException::migrationFailed('Migration_2', new \Exception('Unknown column wp_mailpoet_x.y')));
    verify($html)->stringContainsString('contact the site administrator');
    verify($html)->stringNotContainsString('Unknown column');
    verify($html)->stringNotContainsString('Migration_2');
  }

  public function testItShowsAdministratorsEachFailedMigrationWithItsFullError(): void {
    wp_set_current_user(1);
    $html = $this->notifier->render(MigratorException::migrationFailed('Migration_2', new \Exception('Unknown column wp_mailpoet_x.y')));
    verify($html)->stringContainsString('MailPoet database update failed.');
    verify($html)->stringContainsString('Migration_2 (attempts: 4): ' . esc_html("Exception: Unknown column 'x' in /plugin/Migration_2.php:12"));
    verify($html)->stringContainsString('<summary>Full error: Migration_2</summary>');
    verify($html)->stringContainsString(esc_html("Migration_2.php:12") . '<br>Stack trace:<br>#0 ...');
    verify($html)->stringNotContainsString("\n");
    verify($html)->stringContainsString('wp mailpoet migrations run');
  }

  public function testItShowsAFailureTheStoredRowsDoNotExplain(): void {
    wp_set_current_user(1);
    // Migration_2's row is left over from an earlier attempt; this attempt broke before reaching it
    $failure = MigratorException::migrationClassNotFound('MailPoet\\Migrations\\App\\Migration_1');
    $html = $this->notifier->render($failure);
    verify($html)->stringContainsString('Migration_2 (attempts: 4)');
    verify($html)->stringContainsString('Migration class &quot;MailPoet\\Migrations\\App\\Migration_1&quot; not found.');
    verify($html)->stringContainsString('<summary>Full error</summary>');
  }

  public function testItDoesNotRepeatAFailureAStoredRowExplains(): void {
    wp_set_current_user(1);
    $html = $this->notifier->render(MigratorException::migrationFailed('Migration_2', new \Exception('Unknown column wp_mailpoet_x.y')));
    verify($html)->stringNotContainsString('<summary>Full error</summary>');
    verify(substr_count($html, '<details>'))->equals(1);
  }

  public function testItShowsTheFailureItselfWhenNoMigrationFailed(): void {
    wp_set_current_user(1);
    $notifier = $this->createNotifier([]);
    $failure = new \RuntimeException('Populator could not write the default settings');
    $html = $notifier->render($failure);
    verify($html)->stringContainsString('Populator could not write the default settings');
    verify($html)->stringContainsString('<summary>Full error</summary>');
    verify($html)->stringContainsString(esc_html(__FILE__));
  }

  public function testItNamesTheSiteInTheCommandOnMultisite(): void {
    wp_set_current_user(1);
    $wp = $this->make(WPFunctions::class, ['isMultisite' => true, 'homeUrl' => 'https://shop.example.com']);
    $notifier = new UpdateFailureNotifier($this->makeEmpty(Migrator::class, ['getFailedMigrations' => []]), $wp);
    verify($notifier->render(new \Exception('boom')))
      ->stringContainsString('<code>wp mailpoet migrations run --url=https://shop.example.com</code>');
    verify($this->notifier->render(new \Exception('boom')))
      ->stringContainsString('<code>wp mailpoet migrations run</code>');
  }

  public function testItShowsAdministratorsTheFullStartupError(): void {
    $failure = new \RuntimeException('Unknown column wp_mailpoet_x.y');
    $html = $this->notifier->renderStartupFailure($failure);
    verify($html)->stringContainsString('contact the site administrator');
    verify($html)->stringNotContainsString('Unknown column');

    wp_set_current_user(1);
    $html = $this->notifier->renderStartupFailure($failure);
    verify($html)->stringContainsString('<strong>MailPoet could not start.</strong>');
    verify($html)->stringContainsString('Unknown column wp_mailpoet_x.y');
    verify($html)->stringContainsString('<summary>Full error</summary>');
    verify($html)->stringContainsString(esc_html(__FILE__));
  }

  public function testItEscapesEverythingItPrints(): void {
    wp_set_current_user(1);
    $notifier = $this->createNotifier([
      ['name' => '<b>Migration_3</b>', 'status' => Migrator::MIGRATION_STATUS_FAILED, 'retries' => 0, 'error' => "<script>alert(1)</script>\n<img src=x onerror=alert(2)>", 'unknown' => false],
    ]);
    $html = $notifier->render(new \Exception('<script>alert(3)</script>'));
    verify($html)->stringNotContainsString('<script>');
    verify($html)->stringNotContainsString('<img');
    verify($html)->stringNotContainsString('<b>');
  }

  public function testTheNoticeKeepsItsStructureAfterWordPressFiltering(): void {
    wp_set_current_user(1);
    $this->notifier->notify(new \Exception('boom'));
    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringNotContainsString('MailPoet Error:');
    verify($html)->stringContainsString('<details>');
    verify($html)->stringContainsString('<p style="font-family: monospace">');
    verify($html)->stringContainsString('Stack trace:<br>');
    verify($html)->stringNotContainsString('<br />');
  }

  /**
   * @param array<int, array<string, mixed>> $failedMigrations
   */
  private function createNotifier(array $failedMigrations): UpdateFailureNotifier {
    $migrator = $this->makeEmpty(Migrator::class, ['getFailedMigrations' => $failedMigrations]);
    return new UpdateFailureNotifier($migrator, $this->diContainer->get(WPFunctions::class));
  }

  /**
   * Renders only MailPoet's own notices; other plugins' admin_notices callbacks stay untouched.
   */
  private function renderMailPoetNotices(): string {
    $filters = $GLOBALS['wp_filter'];
    $this->assertIsArray($filters);
    $hook = $filters['admin_notices'] ?? null;
    if (!$hook instanceof \WP_Hook) {
      return '';
    }
    ob_start();
    foreach ($hook->callbacks as $callbacks) {
      foreach ($callbacks as $callback) {
        $function = $callback['function'];
        if (is_array($function) && ($function[0] ?? null) instanceof Notice && is_callable($function)) {
          $function();
        }
      }
    }
    return (string)ob_get_clean();
  }
}
