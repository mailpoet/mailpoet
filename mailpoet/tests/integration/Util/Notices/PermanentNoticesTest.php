<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
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
    $wp = Stub::make(new WPFunctions, [
      'currentUserCan' => true,
      'wpVerifyNonce' => Expected::once(function ($nonce, $action) {
        verify($nonce)->equals('valid-nonce');
        verify($action)->equals(Notice::DISMISS_NONCE_ACTION);
        return true;
      }),
      'wpDie' => Expected::never(),
    ], $this);

    $_POST['type'] = PHPVersionWarnings::OPTION_NAME;
    $_POST['nonce'] = 'valid-nonce';
    $this->createPermanentNotices($wp)->ajaxDismissNoticeHandler();

    verify(get_transient(PHPVersionWarnings::OPTION_NAME))->true();
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

  private function createPermanentNotices(WPFunctions $wp): PermanentNotices {
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
      $this->diContainer->get(SenderDomainAuthenticationNotices::class),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->diContainer->get(NewslettersRepository::class)
    );
  }
}
