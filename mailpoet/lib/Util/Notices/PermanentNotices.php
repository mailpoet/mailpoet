<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class PermanentNotices {

  /** @var WPFunctions */
  private $wp;

  /** @var PHPVersionWarnings */
  private $phpVersionWarnings;

  /** @var AfterMigrationNotice */
  private $afterMigrationNotice;

  /** @var UnauthorizedEmailNotice */
  private $unauthorizedEmailsNotice;

  /** @var UnauthorizedEmailInNewslettersNotice */
  private $unauthorizedEmailsInNewslettersNotice;

  /** @var InactiveSubscribersNotice */
  private $inactiveSubscribersNotice;

  /** @var BlackFridayNotice */
  private $blackFridayNotice;

  /** @var HeadersAlreadySentNotice */
  private $headersAlreadySentNotice;

  /** @var EmailWithInvalidSegmentNotice */
  private $emailWithInvalidListNotice;

  /** @var ChangedTrackingNotice */
  private $changedTrackingNotice;

  /** @var DeprecatedFilterNotice */
  private $deprecatedFilterNotice;

  /** @var DisabledMailFunctionNotice */
  private $disabledMailFunctionNotice;

  public function __construct(
    WPFunctions $wp,
    TrackingConfig $trackingConfig,
    SubscribersRepository $subscribersRepository,
    SettingsController $settings,
    SubscribersFeature $subscribersFeature,
    MailerFactory $mailerFactory
  ) {
    $this->wp = $wp;
    $this->phpVersionWarnings = new PHPVersionWarnings();
    $this->afterMigrationNotice = new AfterMigrationNotice();
    $this->unauthorizedEmailsNotice = new UnauthorizedEmailNotice($wp, $settings);
    $this->unauthorizedEmailsInNewslettersNotice = new UnauthorizedEmailInNewslettersNotice($settings, $wp);
    $this->inactiveSubscribersNotice = new InactiveSubscribersNotice($settings, $subscribersRepository, $wp);
    $this->blackFridayNotice = new BlackFridayNotice($subscribersRepository);
    $this->headersAlreadySentNotice = new HeadersAlreadySentNotice($settings, $trackingConfig, $wp);
    $this->emailWithInvalidListNotice = new EmailWithInvalidSegmentNotice($wp);
    $this->changedTrackingNotice = new ChangedTrackingNotice($wp);
    $this->deprecatedFilterNotice = new DeprecatedFilterNotice($wp);
    $this->disabledMailFunctionNotice = new DisabledMailFunctionNotice($wp, $settings, $subscribersFeature, $mailerFactory);
  }

  public function init() {
    $excludeWizard = [
      'mailpoet-welcome-wizard',
      'mailpoet-woocommerce-setup',
    ];
    $this->wp->addAction('wp_ajax_dismissed_notice_handler', [
      $this,
      'ajaxDismissNoticeHandler',
    ]);

    $this->phpVersionWarnings->init(
      phpversion(),
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->afterMigrationNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->unauthorizedEmailsNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->unauthorizedEmailsInNewslettersNotice->init(
      Menu::isOnMailPoetAdminPage($exclude = null, $pageId = 'mailpoet-newsletters')
    );
    $this->inactiveSubscribersNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->blackFridayNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->headersAlreadySentNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->emailWithInvalidListNotice->init(
      Menu::isOnMailPoetAdminPage($exclude = null, $pageId = 'mailpoet-newsletters')
    );
    $this->changedTrackingNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->deprecatedFilterNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->disabledMailFunctionNotice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
  }

  public function ajaxDismissNoticeHandler() {
    if (!isset($_POST['type'])) return;
    switch ($_POST['type']) {
      case (PHPVersionWarnings::OPTION_NAME):
        $this->phpVersionWarnings->disable();
        break;
      case (AfterMigrationNotice::OPTION_NAME):
        $this->afterMigrationNotice->disable();
        break;
      case (BlackFridayNotice::OPTION_NAME):
        $this->blackFridayNotice->disable();
        break;
      case (HeadersAlreadySentNotice::OPTION_NAME):
        $this->headersAlreadySentNotice->disable();
        break;
      case (InactiveSubscribersNotice::OPTION_NAME):
        $this->inactiveSubscribersNotice->disable();
        break;
      case (EmailWithInvalidSegmentNotice::OPTION_NAME):
        $this->emailWithInvalidListNotice->disable();
        break;
      case (ChangedTrackingNotice::OPTION_NAME):
        $this->changedTrackingNotice->disable();
        break;
      case (DeprecatedFilterNotice::OPTION_NAME):
        $this->deprecatedFilterNotice->disable();
        break;
    }
  }
}
