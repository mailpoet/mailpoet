<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Response;
use MailPoet\Automation\Engine\Control\ActionScheduler as AutomationActionScheduler;
use MailPoet\Automation\Engine\Hooks as AutomationHooks;
use MailPoet\Config\Env;
use MailPoet\Config\Menu;
use MailPoet\Config\SchemaNotReadyResponder;
use MailPoet\Config\SchemaState;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Migrator\Migrator;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;
use WP_REST_Request;

class SchemaNotReadyResponderTest extends \MailPoetTest {
  private SettingsController $settings;

  private SchemaState $schemaState;

  private SchemaNotReadyResponder $responder;

  /** @var mixed */
  private $pagenow;

  public function _before(): void {
    parent::_before();
    $this->pagenow = $GLOBALS['pagenow'] ?? null;
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('db_version', '0.0.1');
    // fresh instances: the container's shared SchemaState would carry a failure across tests
    $this->schemaState = new SchemaState($this->settings);
    $this->responder = $this->createResponder();
    wp_set_current_user(0);
  }

  public function _after(): void {
    $this->settings->set('db_version', Env::$version);
    $GLOBALS['pagenow'] = $this->pagenow;
    foreach (['menu', 'submenu', '_parent_pages', 'admin_page_hooks', '_registered_pages'] as $name) {
      unset($GLOBALS[$name]);
    }
    parent::_after();
  }

  public function testItBuildsAnUpdateInProgressJsonResponse(): void {
    $response = $this->responder->buildJsonResponse();
    verify($response->status)->equals(Response::STATUS_SERVICE_UNAVAILABLE);
    verify($response->errors[0]['error'])->equals(Error::UPDATE_IN_PROGRESS);
    verify($response->errors[0]['message'])->equals($this->schemaState->getPublicMessage());
  }

  public function testItHidesTheFailureDetailFromAnonymousCallers(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    $response = $this->responder->buildJsonResponse();
    verify($response->status)->equals(Response::STATUS_SERVICE_UNAVAILABLE);
    verify($response->errors[0]['error'])->equals(Error::UPDATE_FAILED);
    verify($response->errors[0]['message'])->stringNotContainsString('Unknown column');
    verify($response->errors[0]['message'])->stringContainsString('contact the site administrator');
  }

  public function testItShowsTheFailureDetailToAdministrators(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    wp_set_current_user(1);
    $response = $this->responder->buildJsonResponse();
    verify($response->errors[0]['error'])->equals(Error::UPDATE_FAILED);
    verify($response->errors[0]['message'])->stringContainsString('Unknown column wp_mailpoet_x.y');
  }

  public function testItRejectsMailPoetRestRoutesWith503(): void {
    foreach (['/mailpoet/v1/subscribers', '/mailpoet/v1'] as $route) {
      $result = $this->responder->rejectRestRequest(null, null, new WP_REST_Request('GET', $route));
      $this->assertInstanceOf(WP_Error::class, $result, $route);
      verify($result->get_error_code())->equals('mailpoet_' . Error::UPDATE_IN_PROGRESS);
      $errorData = $result->get_error_data();
      $this->assertIsArray($errorData);
      verify($errorData['status'])->equals(503);
    }
  }

  public function testItNamesTheFailureInTheRestErrorForAdministratorsOnly(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    $request = new WP_REST_Request('GET', '/mailpoet/v1/subscribers');

    $anonymous = $this->responder->rejectRestRequest(null, null, $request);
    $this->assertInstanceOf(WP_Error::class, $anonymous);
    verify($anonymous->get_error_code())->equals('mailpoet_' . Error::UPDATE_FAILED);
    verify($anonymous->get_error_message())->stringNotContainsString('Unknown column');

    wp_set_current_user(1);
    $admin = $this->responder->rejectRestRequest(null, null, $request);
    $this->assertInstanceOf(WP_Error::class, $admin);
    verify($admin->get_error_message())->stringContainsString('Unknown column wp_mailpoet_x.y');
  }

  public function testItLeavesOtherRestRoutesAlone(): void {
    foreach (['/wp/v2/posts', '/mailpoet-other/v1/x'] as $route) {
      verify($this->responder->rejectRestRequest(null, null, new WP_REST_Request('GET', $route)))->null();
    }
  }

  public function testItKeepsAResultAnotherFilterAlreadyProduced(): void {
    $request = new WP_REST_Request('GET', '/mailpoet/v1/subscribers');
    $earlier = new WP_Error('earlier');
    verify($this->responder->rejectRestRequest($earlier, null, $request))->same($earlier);
  }

  public function testItAnswersADispatchedMailPoetRestRequestWith503(): void {
    $this->responder->init();
    $response = rest_do_request(new WP_REST_Request('GET', '/mailpoet/v1/anything'));
    verify($response->get_status())->equals(503);
    $data = $response->get_data();
    $this->assertIsArray($data);
    verify($data['code'])->equals('mailpoet_' . Error::UPDATE_IN_PROGRESS);
  }

  public function testItRegistersTheAjaxRouterAndFormHandlers(): void {
    $this->responder->init();
    verify(has_action('wp_ajax_mailpoet', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_action('wp_ajax_nopriv_mailpoet', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_action('wp_ajax_mailpoet_token', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_action('wp_ajax_nopriv_mailpoet_token', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_action('wp_loaded', [$this->responder, 'rejectRouterRequest']))->notEmpty();
    verify(has_action('admin_post_mailpoet_subscription_form', [$this->responder, 'sendUnavailablePage']))->notEmpty();
    verify(has_action('admin_post_nopriv_mailpoet_subscription_form', [$this->responder, 'sendUnavailablePage']))->notEmpty();
    verify(has_action('admin_post_mailpoet_subscription_update', [$this->responder, 'sendUnavailablePage']))->notEmpty();
    verify(has_action('admin_post_nopriv_mailpoet_subscription_update', [$this->responder, 'sendUnavailablePage']))->notEmpty();
    verify(has_action('admin_init', [$this->responder, 'redirectEmailEditorScreen']))->notEmpty();
    verify(has_action(AutomationHooks::AUTOMATION_STEP, [$this->responder, 'deferAutomationStep']))->notEmpty();
  }

  public function testItBooksADueAutomationStepAgainForLater(): void {
    $args = ['automation_run_id' => 42, 'step_id' => 'send-email'];
    $now = time();
    $scheduler = $this->make(AutomationActionScheduler::class, [
      'schedule' => Expected::once(function (int $timestamp, string $hook, array $actionArgs) use ($args, $now): int {
        verify($timestamp)->greaterThanOrEqual($now + 60);
        verify($hook)->equals(AutomationHooks::AUTOMATION_STEP);
        verify($actionArgs)->equals([$args]);
        return 1;
      }),
    ]);
    $responder = new SchemaNotReadyResponder($this->schemaState, $this->diContainer->get(Migrator::class), $scheduler, $this->diContainer->get(WPFunctions::class));

    $responder->deferAutomationStep($args);
  }

  public function testADeferredStepIsReallyScheduledWithActionScheduler(): void {
    $args = ['automation_run_id' => 43, 'step_id' => 'send-email'];
    try {
      // called directly: in this process the real step handler is hooked too and would run
      $this->responder->deferAutomationStep($args);
      verify(as_has_scheduled_action(AutomationHooks::AUTOMATION_STEP, [$args], 'mailpoet-automation'))->true();
    } finally {
      as_unschedule_all_actions(AutomationHooks::AUTOMATION_STEP, [$args], 'mailpoet-automation');
    }
  }

  public function testItAddsTheNoticeExceptOnMailPoetScreens(): void {
    $this->responder->init();
    verify($this->renderMailPoetNotices())->stringContainsString('notice-warning');

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $this->createResponder()->init();
    verify(substr_count($this->renderMailPoetNotices(), 'mailpoet_notice_server'))->equals(1);
  }

  public function testTheNoticeIsAnErrorAfterAFailedMigration(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    $this->responder->init();
    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringNotContainsString('Unknown column');
  }

  public function testItEndsARouterRequestWithA503Page(): void {
    $wp = $this->make(new WPFunctions(), [
      'wpDie' => Expected::once(function ($message, $title, $args) {
        verify($message)->stringContainsString('update is in progress');
        verify($args['response'])->equals(503);
      }),
    ]);
    $responder = $this->createResponder(null, $wp);

    $responder->rejectRouterRequest();

    $_GET[Router::NAME] = 'track';
    $responder->rejectRouterRequest();
  }

  public function testItRegistersTheMenuEntryAndTheRequestedDeepLink(): void {
    wp_set_current_user(1); // add_submenu_page() registers nothing for a user without the capability
    $this->responder->init();
    verify(has_action('admin_menu', [$this->responder, 'registerMenu']))->notEmpty();

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $this->responder->registerMenu();

    verify(menu_page_url(Menu::MAIN_PAGE_SLUG, false))->stringContainsString('page=' . Menu::MAIN_PAGE_SLUG);
    verify($this->getDeepLinkSlugs())->equals(['mailpoet-newsletters']);
  }

  public function testItRegistersNoDeepLinkForOtherPagesOrTheMainPage(): void {
    wp_set_current_user(1);
    foreach (['woocommerce', Menu::MAIN_PAGE_SLUG, ''] as $page) {
      $_REQUEST['page'] = $page;
      $this->responder->registerMenu();
      verify($this->getDeepLinkSlugs())->equals([], "page={$page}");
    }
  }

  public function testTheStatusPageExplainsAnUpdateInProgress(): void {
    wp_set_current_user(1);
    $html = $this->renderStatusPage($this->responder);
    verify($html)->stringContainsString('notice-warning');
    verify($html)->stringContainsString('update is in progress');
    verify($html)->stringContainsString('Database version: 0.0.1');
    verify($html)->stringNotContainsString('mailpoet migrations run');
  }

  public function testTheStatusPageKeepsFailureDetailFromNonAdministrators(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    $html = $this->renderStatusPage($this->createResponder($this->createMigratorWithFailure()));
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('contact the site administrator');
    verify($html)->stringNotContainsString('Unknown column');
    verify($html)->stringNotContainsString('Migration_2');
  }

  public function testTheStatusPageListsFailedMigrationsForAdministrators(): void {
    $this->schemaState->markFailed(new \Exception('Unknown column wp_mailpoet_x.y'));
    wp_set_current_user(1);
    $html = $this->renderStatusPage($this->createResponder($this->createMigratorWithFailure()));
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('Unknown column wp_mailpoet_x.y');
    verify($html)->stringContainsString('<td>Migration_2</td><td>3</td><td>Exception: Unknown column &#039;x&#039; in /plugin/Migration_2.php:12</td>');
    verify($html)->stringContainsString('<summary>Full error: Migration_2</summary>');
    verify($html)->stringContainsString("Migration_2.php:12\nStack trace:\n#0 ...</pre>");
    verify($html)->stringContainsString('mailpoet migrations run');
  }

  private function createMigratorWithFailure(): Migrator {
    return $this->makeEmpty(Migrator::class, [
      'getFailedMigrations' => [
        ['name' => 'Migration_2', 'status' => Migrator::MIGRATION_STATUS_FAILED, 'retries' => 3, 'error' => "Exception: Unknown column 'x' in /plugin/Migration_2.php:12\nStack trace:\n#0 ...", 'error_summary' => "Exception: Unknown column 'x' in /plugin/Migration_2.php:12"],
      ],
    ]);
  }

  /**
   * @return string[]
   */
  private function getDeepLinkSlugs(): array {
    $submenu = $GLOBALS['submenu'] ?? [];
    $this->assertIsArray($submenu);
    $deepLinks = $submenu[Menu::NO_PARENT_PAGE_SLUG] ?? [];
    $this->assertIsArray($deepLinks);
    return array_column($deepLinks, 2);
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
        if (is_array($function) && ($function[0] ?? null) instanceof \MailPoet\WP\Notice && is_callable($function)) {
          $function();
        }
      }
    }
    return (string)ob_get_clean();
  }

  private function renderStatusPage(SchemaNotReadyResponder $responder): string {
    ob_start();
    $responder->renderStatusPage();
    return (string)ob_get_clean();
  }

  private function createResponder(?Migrator $migrator = null, ?WPFunctions $wp = null): SchemaNotReadyResponder {
    return new SchemaNotReadyResponder(
      $this->schemaState,
      $migrator ?? $this->diContainer->get(Migrator::class),
      $this->diContainer->get(AutomationActionScheduler::class),
      $wp ?? $this->diContainer->get(WPFunctions::class)
    );
  }

  public function testItSendsTheEmailEditorScreenToTheStatusPage(): void {
    $emailId = wp_insert_post(['post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE, 'post_title' => 'Gated email', 'post_status' => 'draft']);
    $postId = wp_insert_post(['post_type' => 'post', 'post_title' => 'Ordinary post', 'post_status' => 'draft']);
    $this->assertIsInt($emailId);
    $this->assertIsInt($postId);
    try {
      $redirects = [];
      $wp = $this->make(new WPFunctions(), [
        'wpSafeRedirect' => function (string $url) use (&$redirects) {
          $redirects[] = $url;
          throw new \RuntimeException('redirected');
        },
      ]);
      $responder = $this->createResponder(null, $wp);

      // an ordinary post, a non-editor screen and a new post of the default type pass through
      $GLOBALS['pagenow'] = 'post.php';
      $_GET['post'] = (string)$postId;
      $responder->redirectEmailEditorScreen();
      $GLOBALS['pagenow'] = 'edit.php';
      $_GET['post'] = (string)$emailId;
      $responder->redirectEmailEditorScreen();
      $GLOBALS['pagenow'] = 'post-new.php';
      unset($_GET['post']);
      $responder->redirectEmailEditorScreen();
      verify($redirects)->equals([]);

      $GLOBALS['pagenow'] = 'post.php';
      $_GET['post'] = (string)$emailId;

      try {
        $responder->redirectEmailEditorScreen();
        $this->fail('editing an email should redirect');
      } catch (\RuntimeException $e) {
        verify($e->getMessage())->equals('redirected');
      }

      unset($_GET['post']);
      $_GET['post_type'] = EmailEditor::MAILPOET_EMAIL_POST_TYPE;
      foreach (['post-new.php', 'edit.php'] as $screen) {
        $GLOBALS['pagenow'] = $screen;
        try {
          $responder->redirectEmailEditorScreen();
          $this->fail("{$screen} for emails should redirect");
        } catch (\RuntimeException $e) {
          verify($e->getMessage())->equals('redirected');
        }
      }

      verify($redirects)->arrayCount(3);
      verify($redirects[0])->stringContainsString('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    } finally {
      wp_delete_post($emailId, true);
      wp_delete_post($postId, true);
    }
  }
}
