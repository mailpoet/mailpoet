<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\AccessControl;
use MailPoet\Mailer\SendingEnvironment;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class NonProductionSendingNoticeTest extends \MailPoetTest {
  private $addedActions = [];
  private $dieCalls = [];
  private $redirects = [];
  private $allowCalls = 0;
  private $clearCalls = 0;
  private $terminated = 0;

  public function _before() {
    parent::_before();
    $this->addedActions = [];
    $this->dieCalls = [];
    $this->redirects = [];
    $this->allowCalls = 0;
    $this->clearCalls = 0;
    $this->terminated = 0;
    unset($_POST['_wpnonce']);
  }

  public function _after() {
    parent::_after();
    unset($_POST['_wpnonce']);
    remove_all_actions('admin_notices');
  }

  public function testItIsNotShownWhenSendingIsAllowed() {
    $notice = $this->createNotice(['isSendingAllowed' => true]);
    verify($notice->init(true))->null();
  }

  public function testItIsNotShownOutsideMailPoetPages() {
    $notice = $this->createNotice(['isSendingAllowed' => false]);
    verify($notice->init(false))->null();
  }

  public function testItClearsTheProductionOverrideOnAdminRequests() {
    $this->createNotice([], ['isAdmin' => true])->init(false);
    verify($this->clearCalls)->equals(1);
  }

  /** @dataProvider nonAdminRequestProvider */
  public function testItDoesNotClearTheProductionOverrideOutsideAdminRequests(array $wpOverrides) {
    $this->createNotice([], $wpOverrides)->init(false);
    verify($this->clearCalls)->equals(0);
  }

  public function nonAdminRequestProvider(): array {
    return [
      'front end' => [['isAdmin' => false]],
      'admin cron' => [['isAdmin' => true, 'wpDoingCron' => true]],
      'admin AJAX' => [['isAdmin' => true, 'wpDoingAjax' => true]],
    ];
  }

  public function testItRegistersTheHandlerEvenWhenNothingIsDisplayed() {
    $this->createNotice(['isSendingAllowed' => true])->init(false);
    verify($this->addedActions)->arrayContains('admin_post_' . NonProductionSendingNotice::ACTION);
  }

  public function testItShowsTheNoticeWithTheTurnOnFormForUsersWhoCanManageSettings() {
    $notice = $this->createNotice(['isSendingAllowed' => false, 'getEnvironmentType' => 'staging'], ['currentUserCan' => true]);
    $result = $notice->init(true);
    $this->assertInstanceOf(Notice::class, $result);

    $html = $this->render($result);
    verify($html)->stringContainsString('MailPoet isn’t sending emails because this is a staging site.');
    verify($html)->stringContainsString('Newsletters, post notifications, automation emails and bulk confirmation resends wait in the queue.');
    verify($html)->stringContainsString('Sign-up confirmations, test emails, WordPress and WooCommerce emails, and the Premium “Send notification email” automation step still go out.');
    verify($html)->stringContainsString('Everything waiting in the queue goes out once sending is on.');
    $this->assertHtmlHasTag($html, 'form', ['method' => 'post', 'action' => 'http://example.test/wp-admin/admin-post.php']);
    $this->assertHtmlHasTag($html, 'input', ['type' => 'hidden', 'name' => 'action', 'value' => 'mailpoet_allow_non_production_sending']);
    $this->assertHtmlHasTag($html, 'input', ['type' => 'hidden', 'name' => '_wpnonce', 'value' => 'nonce-123']);
    $this->assertHtmlHasTag($html, 'button', ['type' => 'submit', 'class' => 'button button-primary']);
    verify($html)->stringContainsString('>Turn sending on</button>');
    verify($html)->stringNotContainsString('ask an administrator');
    verify($html)->stringNotContainsString('data-notice=');
  }

  public function testItAsksForAnAdministratorWhenTheUserCannotManageSettings() {
    $notice = $this->createNotice(['isSendingAllowed' => false, 'getEnvironmentType' => 'local'], ['currentUserCan' => false]);
    $result = $notice->init(true);
    $this->assertInstanceOf(Notice::class, $result);

    $html = $this->render($result);
    verify($html)->stringContainsString('because this is a local site.');
    verify($html)->stringContainsString('ask an administrator to turn sending on.');
    verify($html)->stringNotContainsString('<form');
    verify($html)->stringNotContainsString('<button');
  }

  public function testItAllowsSendingAndRedirectsWithAValidNonceAndCapability() {
    $_POST['_wpnonce'] = 'valid';
    $notice = $this->createNotice(['isProduction' => false], ['currentUserCan' => true, 'wpVerifyNonce' => 1]);
    $notice->handleAllowSending();
    verify($this->allowCalls)->equals(1);
    verify($this->redirects)->equals(['http://example.test/back']);
    verify($this->terminated)->equals(1);
    verify($this->dieCalls)->equals([]);
  }

  public function testItDoesNothingWithoutTheCapability() {
    $_POST['_wpnonce'] = 'valid';
    $notice = $this->createNotice(['isProduction' => false], ['currentUserCan' => false, 'wpVerifyNonce' => 1]);
    $notice->handleAllowSending();
    verify($this->allowCalls)->equals(0);
    verify($this->dieCalls)->equals([403]);
    verify($this->redirects)->equals([]);
  }

  public function testItDoesNothingWithAnInvalidNonce() {
    $_POST['_wpnonce'] = 'bad';
    $notice = $this->createNotice(['isProduction' => false], ['currentUserCan' => true, 'wpVerifyNonce' => false]);
    $notice->handleAllowSending();
    verify($this->allowCalls)->equals(0);
    verify($this->dieCalls)->equals([403]);
    verify($this->redirects)->equals([]);
  }

  public function testItDoesNothingWithAMissingNonce() {
    $notice = $this->createNotice(['isProduction' => false], ['currentUserCan' => true, 'wpVerifyNonce' => false]);
    $notice->handleAllowSending();
    verify($this->allowCalls)->equals(0);
    verify($this->dieCalls)->equals([403]);
  }

  public function testItWritesNothingOnProduction() {
    $_POST['_wpnonce'] = 'valid';
    $notice = $this->createNotice(['isProduction' => true], ['currentUserCan' => true, 'wpVerifyNonce' => 1]);
    $notice->handleAllowSending();
    verify($this->allowCalls)->equals(0);
    verify($this->redirects)->equals(['http://example.test/back']);
  }

  private function render(Notice $notice): string {
    ob_start();
    $notice->displayWPNotice();
    return (string)ob_get_clean();
  }

  private function createNotice(array $environment, array $wpOverrides = []): NonProductionSendingNotice {
    $environment = array_merge(['isSendingAllowed' => false, 'isProduction' => false, 'getEnvironmentType' => 'staging'], $environment);
    $environment['allowSending'] = function () {
      $this->allowCalls++;
    };
    $environment['clearOverrideOnProduction'] = function () {
      $this->clearCalls++;
    };
    $sendingEnvironment = $this->make(SendingEnvironment::class, $environment);

    $wp = $this->make(WPFunctions::class, array_merge([
      'addAction' => function ($name) {
        $this->addedActions[] = $name;
        return true;
      },
      'currentUserCan' => function ($capability) use ($wpOverrides) {
        verify($capability)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
        return $wpOverrides['currentUserCan'] ?? false;
      },
      'isAdmin' => $wpOverrides['isAdmin'] ?? false,
      'wpDoingCron' => $wpOverrides['wpDoingCron'] ?? false,
      'wpDoingAjax' => $wpOverrides['wpDoingAjax'] ?? false,
      'adminUrl' => function ($path = '') {
        return 'http://example.test/wp-admin/' . $path;
      },
      'wpCreateNonce' => function ($action = -1) {
        verify($action)->equals(NonProductionSendingNotice::ACTION);
        return 'nonce-123';
      },
      'wpDie' => function ($message = '', $title = '', $args = []) {
        $this->dieCalls[] = $args['response'] ?? null;
      },
      'wpGetReferer' => 'http://example.test/back',
      'wpSafeRedirect' => function ($location) {
        $this->redirects[] = $location;
        return true;
      },
      'wpVerifyNonce' => function ($nonce, $action = -1) use ($wpOverrides) {
        verify($action)->equals(NonProductionSendingNotice::ACTION);
        return ($wpOverrides['wpVerifyNonce'] ?? false) && $nonce === 'valid';
      },
    ]));

    $notice = new class($wp, $sendingEnvironment, $this) extends NonProductionSendingNotice {
      private $test;

      public function __construct(
        $wp,
        $sendingEnvironment,
        $test
      ) {
        parent::__construct($wp, $sendingEnvironment);
        $this->test = $test;
      }

      protected function terminate(): void {
        $this->test->markTerminated();
      }
    };
    return $notice;
  }

  public function markTerminated(): void {
    $this->terminated++;
  }
}
