<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use MailPoet\Captcha\CaptchaDisabledNotice;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class PermanentNoticesTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    delete_transient(PHPVersionWarnings::OPTION_NAME);
  }

  public function _after() {
    parent::_after();
    unset($_POST['type'], $_POST['nonce']);
    delete_transient(PHPVersionWarnings::OPTION_NAME);
  }

  public function testItRefusesDismissWithoutCapability() {
    $wp = Stub::make(new WPFunctions, [
      'currentUserCan' => false,
      'wpVerifyNonce' => Expected::never(),
      'wpDie' => Expected::once(),
    ], $this);

    $_POST['type'] = PHPVersionWarnings::OPTION_NAME;
    $_POST['nonce'] = 'irrelevant';
    $this->createPermanentNotices($wp)->ajaxDismissNoticeHandler();

    verify(get_transient(PHPVersionWarnings::OPTION_NAME))->false();
  }

  public function testItRefusesDismissWithInvalidNonce() {
    $wp = Stub::make(new WPFunctions, [
      'currentUserCan' => true,
      'wpVerifyNonce' => Expected::once(false),
      'wpDie' => Expected::once(),
    ], $this);

    $_POST['type'] = PHPVersionWarnings::OPTION_NAME;
    $_POST['nonce'] = 'invalid';
    $this->createPermanentNotices($wp)->ajaxDismissNoticeHandler();

    verify(get_transient(PHPVersionWarnings::OPTION_NAME))->false();
  }

  public function testItDismissesNoticeWithCapabilityAndValidNonce() {
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    $wp = Stub::make(new WPFunctions, [
      'currentUserCan' => true,
      'wpVerifyNonce' => Expected::once(function ($nonce, $action) {
        verify($nonce)->equals('valid-nonce');
        verify($action)->equals(Notice::DISMISS_NONCE_ACTION);
        return true;
      }),
      'wpDie' => Expected::never(),
      'currentTime' => function () use ($ts) {
        return $ts;
      },
    ], $this);

    $_POST['type'] = PHPVersionWarnings::OPTION_NAME;
    $_POST['nonce'] = 'valid-nonce';
    $this->createPermanentNotices($wp)->ajaxDismissNoticeHandler();

    verify(get_transient(PHPVersionWarnings::OPTION_NAME))->equals($ts);
  }

  public function testDismissibleNoticeMarkupContainsNonce() {
    $wp = Stub::make(new WPFunctions, [
      'wpCreateNonce' => Expected::once(function ($action) {
        verify($action)->equals(Notice::DISMISS_NONCE_ACTION);
        return 'test-nonce';
      }),
    ], $this);
    $notice = new Notice(Notice::TYPE_WARNING, 'A message', '', 'some-notice-name', true, $wp);
    ob_start();
    $notice->displayWPNotice();
    $output = ob_get_clean();

    verify($output)->stringContainsString('data-notice="some-notice-name"');
    verify($output)->stringContainsString('data-nonce="test-nonce"');
  }

  public function testDismissibleNoticeMarkupContainsNonceWithoutParagraph() {
    $wp = Stub::make(new WPFunctions, ['wpCreateNonce' => 'test-nonce']);
    $notice = new Notice(Notice::TYPE_WARNING, 'A message', '', 'some-notice-name', false, $wp);
    ob_start();
    $notice->displayWPNotice();
    $output = ob_get_clean();

    verify($output)->stringContainsString('data-notice="some-notice-name"');
    verify($output)->stringContainsString('data-nonce="test-nonce"');
    verify($output)->stringNotContainsString('<p>');
  }

  /**
   * @dataProvider pausedCampaignsNoticePageProvider
   */
  public function testItPassesPageVisibilityToPausedCampaignsNotice(string $page, bool $expected) {
    $pausedNotice = $this->createMock(SenderDomainPausedCampaignsNotice::class);
    $pausedNotice->expects($this->once())->method('init')->with($expected)->willReturn(null);

    $this->initOnPage($page, $pausedNotice, $this->createMock(SenderDomainAuthenticationNotices::class));
  }

  public static function pausedCampaignsNoticePageProvider(): array {
    return [
      'email editor' => ['mailpoet-newsletter-editor', false],
      'automation editor' => ['mailpoet-automation-editor', false],
      'form editor' => ['mailpoet-form-editor', false],
      'welcome wizard' => ['mailpoet-welcome-wizard', false],
      'settings' => ['mailpoet-settings', true],
      'emails' => ['mailpoet-newsletters', true],
      'automations' => ['mailpoet-automation', true],
      'homepage' => ['mailpoet-homepage', true],
    ];
  }

  public function testItHidesDomainAuthenticationNoticesWhenPausedCampaignsNoticeListsTheDefaultSenderDomain() {
    $pausedNotice = $this->createPausedNotice(['example.com']);
    $authNotices = $this->createMock(SenderDomainAuthenticationNotices::class);
    $authNotices->method('getDefaultFromDomain')->willReturn('example.com');
    $authNotices->expects($this->once())->method('init')->with(false);

    $this->initOnPage('mailpoet-newsletters', $pausedNotice, $authNotices);
  }

  public function testItKeepsDomainAuthenticationNoticesWhenDefaultSenderIsOnAnotherDomain() {
    $pausedNotice = $this->createPausedNotice(['example.com']);
    $authNotices = $this->createMock(SenderDomainAuthenticationNotices::class);
    $authNotices->method('getDefaultFromDomain')->willReturn('other.com');
    $authNotices->expects($this->once())->method('init')->with(true);

    $this->initOnPage('mailpoet-newsletters', $pausedNotice, $authNotices);
  }

  private function createPausedNotice(array $listedDomains): SenderDomainPausedCampaignsNotice {
    $pausedNotice = $this->createMock(SenderDomainPausedCampaignsNotice::class);
    $pausedNotice->method('init')->willReturn(new Notice(Notice::TYPE_ERROR, 'Paused', '', '', true, new WPFunctions()));
    $pausedNotice->method('getListedDomains')->willReturn($listedDomains);
    return $pausedNotice;
  }

  public function testItKeepsDomainAuthenticationNoticesConditionWhenPausedCampaignsNoticeIsNull() {
    $pausedNotice = $this->createMock(SenderDomainPausedCampaignsNotice::class);
    $pausedNotice->method('init')->willReturn(null);
    $authNotices = $this->createMock(SenderDomainAuthenticationNotices::class);
    $authNotices->expects($this->once())->method('init')->with(true);

    $this->initOnPage('mailpoet-newsletters', $pausedNotice, $authNotices);
  }

  public function testItKeepsDomainAuthenticationNoticesHiddenOnSettingsWhenPausedCampaignsNoticeIsNull() {
    $pausedNotice = $this->createMock(SenderDomainPausedCampaignsNotice::class);
    $pausedNotice->method('init')->willReturn(null);
    $authNotices = $this->createMock(SenderDomainAuthenticationNotices::class);
    $authNotices->expects($this->once())->method('init')->with(false);

    $this->initOnPage('mailpoet-settings', $pausedNotice, $authNotices);
  }

  private function initOnPage(string $page, SenderDomainPausedCampaignsNotice $pausedNotice, SenderDomainAuthenticationNotices $authNotices): void {
    global $wp_filter;
    $previousPage = $_REQUEST['page'] ?? null;
    $previousActions = $wp_filter['admin_notices'] ?? null;
    $_REQUEST['page'] = $page;
    try {
      $this->createPermanentNotices(new WPFunctions(), $authNotices, $pausedNotice)->init();
    } finally {
      if ($previousPage === null) {
        unset($_REQUEST['page']);
      } else {
        $_REQUEST['page'] = $previousPage;
      }
      if ($previousActions === null) {
        unset($wp_filter['admin_notices']);
      } else {
        $wp_filter['admin_notices'] = $previousActions;
      }
    }
  }

  private function createPermanentNotices(
    WPFunctions $wp,
    ?SenderDomainAuthenticationNotices $authNotices = null,
    ?SenderDomainPausedCampaignsNotice $pausedNotice = null
  ): PermanentNotices {
    return new PermanentNotices(
      $wp,
      $this->diContainer->get(CronHelper::class),
      $this->entityManager,
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersFeature::class),
      $this->diContainer->get(ServicesChecker::class),
      $this->diContainer->get(MailerFactory::class),
      $authNotices ?? $this->diContainer->get(SenderDomainAuthenticationNotices::class),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(CaptchaDisabledNotice::class),
      $pausedNotice ?? $this->diContainer->get(SenderDomainPausedCampaignsNotice::class)
    );
  }
}
