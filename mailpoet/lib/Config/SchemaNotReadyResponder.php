<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\API\REST\API as RestApi;
use MailPoet\Automation\Engine\Control\ActionScheduler as AutomationActionScheduler;
use MailPoet\Automation\Engine\Hooks as AutomationHooks;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Migrator\Migrator;
use MailPoet\Router\Router;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;
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

  private const AUTOMATION_STEP_RETRY_DELAY = 60;

  private SchemaState $schemaState;

  private Migrator $migrator;

  private AutomationActionScheduler $automationActionScheduler;

  private WPFunctions $wp;

  public function __construct(
    SchemaState $schemaState,
    Migrator $migrator,
    AutomationActionScheduler $automationActionScheduler,
    WPFunctions $wp
  ) {
    $this->schemaState = $schemaState;
    $this->migrator = $migrator;
    $this->automationActionScheduler = $automationActionScheduler;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->wp->addAction('wp_ajax_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addAction('wp_ajax_nopriv_mailpoet', [$this, 'sendJsonResponse']);
    $this->wp->addAction('wp_ajax_mailpoet_token', [$this, 'sendJsonResponse']);
    $this->wp->addAction('wp_ajax_nopriv_mailpoet_token', [$this, 'sendJsonResponse']);
    $this->wp->addFilter('rest_pre_dispatch', [$this, 'rejectRestRequest'], 10, 3);
    $this->wp->addAction('wp_loaded', [$this, 'rejectRouterRequest']);
    $this->wp->addAction('admin_menu', [$this, 'registerMenu']);
    $this->wp->addAction('admin_init', [$this, 'redirectEmailEditorScreen']);
    $this->wp->addAction(AutomationHooks::AUTOMATION_STEP, [$this, 'deferAutomationStep']);
    foreach (self::ADMIN_POST_ACTIONS as $action) {
      $this->wp->addAction('admin_post_' . $action, [$this, 'sendUnavailablePage']);
      $this->wp->addAction('admin_post_nopriv_' . $action, [$this, 'sendUnavailablePage']);
    }
    $this->registerNotice();
  }

  /**
   * Shows the reason on every admin screen. MailPoet's own screens render the status
   * page, which repeats it, so they are left out.
   */
  private function registerNotice(): void {
    if (Menu::isOnMailPoetAdminPage()) {
      return;
    }
    if ($this->schemaState->hasFailed()) {
      WPNotice::displayError($this->getMessage());
    } else {
      WPNotice::displayWarning($this->getMessage());
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

  private function isMailPoetRoute(string $route): bool {
    $namespace = '/' . RestApi::PREFIX;
    return $route === $namespace || strpos($route, $namespace . '/') === 0;
  }

  /**
   * Action Scheduler keeps running while MailPoet is gated, and it marks an action whose
   * hook has no callback as failed for good. Automation steps due in the window would
   * be lost with them, so this handler takes the step and books it again for later.
   *
   * @param mixed $args
   */
  public function deferAutomationStep($args): void {
    $this->automationActionScheduler->schedule(
      (int)$this->wp->currentTime('timestamp', true) + self::AUTOMATION_STEP_RETRY_DELAY,
      AutomationHooks::AUTOMATION_STEP,
      [$args]
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

  /**
   * The email editor is not registered while gated, so its edit screen would fall back
   * to WordPress's "Invalid post type" page. Send it to the status page instead.
   */
  public function redirectEmailEditorScreen(): void {
    global $pagenow;
    if (!in_array($pagenow, ['post.php', 'post-new.php', 'edit.php'], true)) {
      return;
    }
    if ($pagenow !== 'post.php') {
      $postType = isset($_GET['post_type']) && is_string($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';
    } else {
      $postId = isset($_GET['post']) && is_numeric($_GET['post']) ? (int)$_GET['post'] : 0;
      $postType = $postId > 0 ? (string)$this->wp->getPostType($postId) : '';
    }
    if ($postType !== EmailEditor::MAILPOET_EMAIL_POST_TYPE) {
      return;
    }
    $this->wp->wpSafeRedirect($this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG));
    exit;
  }

  public function renderStatusPage(): void {
    $failed = $this->schemaState->hasFailed();
    $noticeClass = $failed ? 'notice-error' : 'notice-warning';

    echo '<div class="wrap">';
    echo '<h1>MailPoet</h1>';
    echo '<div class="notice ' . esc_attr($noticeClass) . ' inline"><p>' . esc_html($this->getMessage()) . '</p></div>';

    if ($failed && $this->isAdministrator()) {
      $this->renderFailedMigrations();
      echo '<p>' . esc_html__('MailPoet retries the update on every page load. To retry it manually, run:', 'mailpoet') . '</p>';
      echo '<p><code>wp mailpoet migrations run</code></p>';
      echo '<p>' . esc_html__('Include the full error when contacting MailPoet support.', 'mailpoet') . '</p>';
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
      echo '<tr><td>' . esc_html($migration['name']) . '</td><td>' . esc_html((string)($migration['retries'] ?? 0)) . '</td><td>' . esc_html($migration['error_summary']) . '</td></tr>';
    }
    echo '</tbody></table>';
    foreach ($failed as $migration) {
      echo '<details style="max-width: 900px"><summary>' . sprintf(
        // translators: %s is the migration name
        esc_html__('Full error: %s', 'mailpoet'),
        esc_html($migration['name'])
      ) . '</summary>';
      echo '<pre style="white-space: pre-wrap; overflow-wrap: anywhere">' . esc_html((string)$migration['error']) . '</pre>';
      echo '</details>';
    }
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
    return $this->schemaState->hasFailed() ? Error::UPDATE_FAILED : Error::UPDATE_IN_PROGRESS;
  }
}
