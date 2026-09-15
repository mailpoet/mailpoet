<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\API\REST\API as RestApi;
use MailPoet\Migrator\Migrator;
use MailPoet\Router\Router;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;
use WP_REST_Request;

/**
 * Answers MailPoet's entry points while the schema is not ready.
 *
 * Initializer skips all plugin wiring in that state, so without these handlers an ajax
 * call would get WordPress's bare "0", a REST call a 404 for an unknown namespace,
 * a router link (unsubscribe, tracking, cron) or a form post the site's home page, and
 * the MailPoet menu would simply be gone. All of those look like a broken install; a
 * 503 or a status page with the reason does not.
 *
 * Migration errors can name tables and queries, so the detailed message goes to
 * administrators only; everyone else gets the generic one.
 */
class SchemaNotReadyResponder {
  private const ADMIN_POST_ACTIONS = [
    'mailpoet_subscription_form',
    'mailpoet_subscription_update',
  ];

  private SchemaState $schemaState;

  private Migrator $migrator;

  private WPFunctions $wp;

  public function __construct(
    SchemaState $schemaState,
    Migrator $migrator,
    WPFunctions $wp
  ) {
    $this->schemaState = $schemaState;
    $this->migrator = $migrator;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->wp->addAction('wp_ajax_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addAction('wp_ajax_nopriv_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addFilter('rest_pre_dispatch', [$this, 'rejectRestRequest'], 10, 3);
    $this->wp->addAction('wp_loaded', [$this, 'rejectRouterRequest']);
    $this->wp->addAction('admin_menu', [$this, 'registerMenu']);
    foreach (self::ADMIN_POST_ACTIONS as $action) {
      $this->wp->addAction('admin_post_' . $action, [$this, 'sendUnavailablePage']);
      $this->wp->addAction('admin_post_nopriv_' . $action, [$this, 'sendUnavailablePage']);
    }
  }

  public function buildJsonResponse(): ErrorResponse {
    return new ErrorResponse(
      [$this->getErrorCode() => $this->getMessage()],
      [],
      Response::STATUS_SERVICE_UNAVAILABLE
    );
  }

  public function sendJsonResponse(): void {
    $this->buildJsonResponse()->send();
  }

  /**
   * @param mixed $result
   * @param mixed $server
   * @param mixed $request
   * @return mixed
   */
  public function rejectRestRequest($result, $server, $request) {
    if ($result !== null || !$request instanceof WP_REST_Request) {
      return $result;
    }
    if (!$this->isMailPoetRoute($request->get_route())) {
      return $result;
    }
    return new WP_Error(
      'mailpoet_' . $this->getErrorCode(),
      $this->getMessage(),
      ['status' => Response::STATUS_SERVICE_UNAVAILABLE]
    );
  }

  public function rejectRouterRequest(): void {
    if (!isset($_GET[Router::NAME])) {
      return;
    }
    $this->sendUnavailablePage();
  }

  public function sendUnavailablePage(): void {
    $this->wp->wpDie(
      esc_html($this->getMessage()),
      '',
      ['response' => Response::STATUS_SERVICE_UNAVAILABLE]
    );
  }

  /**
   * Keeps the MailPoet menu entry in place, pointing at the status page, and lets any
   * bookmarked MailPoet page URL land on the same status page instead of a WordPress
   * "not allowed" screen.
   */
  public function registerMenu(): void {
    $this->wp->addMenuPage(
      'MailPoet',
      'MailPoet',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      Menu::MAIN_PAGE_SLUG,
      [$this, 'renderStatusPage'],
      Menu::ICON_BASE64_SVG,
      30
    );

    $requestedPage = isset($_REQUEST['page']) && is_string($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
    if ($requestedPage === '' || $requestedPage === Menu::MAIN_PAGE_SLUG || strpos($requestedPage, 'mailpoet-') !== 0) {
      return;
    }
    $this->wp->addSubmenuPage(
      Menu::NO_PARENT_PAGE_SLUG,
      'MailPoet',
      'MailPoet',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      $requestedPage,
      [$this, 'renderStatusPage']
    );
  }

  public function renderStatusPage(): void {
    $failed = $this->schemaState->getStatus() === SchemaState::STATUS_FAILED;
    $noticeClass = $failed ? 'notice-error' : 'notice-warning';

    echo '<div class="wrap">';
    echo '<h1>MailPoet</h1>';
    echo '<div class="notice ' . esc_attr($noticeClass) . ' inline"><p>' . esc_html($this->getMessage()) . '</p></div>';

    if ($failed && $this->isAdministrator()) {
      $this->renderFailedMigrations();
      echo '<p>' . esc_html__('MailPoet retries the update on every page load. If it keeps failing, run the update from the command line to see the full error:', 'mailpoet') . '</p>';
      echo '<p><code>wp mailpoet:migrations:run</code></p>';
      echo '<p>' . esc_html__('Include the details above when contacting MailPoet support.', 'mailpoet') . '</p>';
    }

    echo '<p>' . sprintf(
      // translators: %1$s is the plugin version, %2$s is the version the database is at
      esc_html__('Plugin version: %1$s. Database version: %2$s.', 'mailpoet'),
      esc_html((string)Env::$version),
      esc_html($this->schemaState->getDbVersion() ?? 'N/A')
    ) . '</p>';
    echo '</div>';
  }

  private function renderFailedMigrations(): void {
    try {
      $failed = $this->migrator->getFailedMigrations();
    } catch (\Throwable $e) {
      echo '<p>' . esc_html($e->getMessage()) . '</p>';
      return;
    }
    if (!$failed) {
      return;
    }
    echo '<table class="widefat striped" style="max-width: 900px">';
    echo '<thead><tr><th>' . esc_html__('Migration', 'mailpoet') . '</th><th>' . esc_html__('Attempts', 'mailpoet') . '</th><th>' . esc_html__('Error', 'mailpoet') . '</th></tr></thead><tbody>';
    foreach ($failed as $migration) {
      // the store keeps the whole exception dump; the first line carries the message
      $error = explode("\n", (string)$migration['error'], 2)[0];
      echo '<tr><td>' . esc_html($migration['name']) . '</td><td>' . esc_html((string)(int)$migration['retries']) . '</td><td>' . esc_html($error) . '</td></tr>';
    }
    echo '</tbody></table>';
  }

  private function isMailPoetRoute(string $route): bool {
    $namespace = '/' . RestApi::PREFIX;
    return $route === $namespace || strpos($route, $namespace . '/') === 0;
  }

  /**
   * The detailed message for administrators, the generic one for everyone else.
   */
  public function getMessage(): string {
    return $this->isAdministrator()
      ? $this->schemaState->getMessage()
      : $this->schemaState->getPublicMessage();
  }

  private function isAdministrator(): bool {
    return $this->wp->currentUserCan('manage_options');
  }

  private function getErrorCode(): string {
    return $this->schemaState->getStatus() === SchemaState::STATUS_FAILED
      ? Error::UPDATE_FAILED
      : Error::UPDATE_IN_PROGRESS;
  }
}
