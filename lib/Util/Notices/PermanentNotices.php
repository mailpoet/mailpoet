<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class PermanentNotices {

  /** @var WPFunctions */
  private $wp;

  /** @var PHPVersionWarnings */
  private $php_version_warnings;

  /** @var AfterMigrationNotice */
  private $after_migration_notice;

  /** @var UnauthorizedEmailNotice */
  private $unauthorized_emails_notice;

  /** @var UnauthorizedEmailInNewslettersNotice */
  private $unauthorized_emails_in_newsletters_notice;

  /** @var InactiveSubscribersNotice */
  private $inactive_subscribers_notice;

  /** @var BlackFridayNotice */
  private $black_friday_notice;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
    $this->php_version_warnings = new PHPVersionWarnings();
    $this->after_migration_notice = new AfterMigrationNotice();
    $this->unauthorized_emails_notice = new UnauthorizedEmailNotice(new SettingsController, $wp);
    $this->unauthorized_emails_in_newsletters_notice = new UnauthorizedEmailInNewslettersNotice(new SettingsController, $wp);
    $this->inactive_subscribers_notice = new InactiveSubscribersNotice(new SettingsController, $wp);
    $this->black_friday_notice = new BlackFridayNotice();
  }

  public function init() {
    $excludeWizard = [
      'mailpoet-welcome-wizard',
      'mailpoet-woocommerce-list-import',
      'mailpoet-revenue-tracking-permission',
    ];
    $this->wp->addAction('wp_ajax_dismissed_notice_handler', [
      $this,
      'ajaxDismissNoticeHandler',
    ]);

    $this->php_version_warnings->init(
      phpversion(),
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->after_migration_notice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->unauthorized_emails_notice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->unauthorized_emails_in_newsletters_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = null, $page_id = 'mailpoet-newsletters')
    );
    $this->inactive_subscribers_notice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
    $this->black_friday_notice->init(
      Menu::isOnMailPoetAdminPage($excludeWizard)
    );
  }

  function ajaxDismissNoticeHandler() {
    if (!isset($_POST['type'])) return;
    switch ($_POST['type']) {
      case (PHPVersionWarnings::OPTION_NAME):
        $this->php_version_warnings->disable();
        break;
      case (AfterMigrationNotice::OPTION_NAME):
        $this->after_migration_notice->disable();
        break;
      case (BlackFridayNotice::OPTION_NAME):
        $this->black_friday_notice->disable();
        break;
      case (InactiveSubscribersNotice::OPTION_NAME):
        $this->inactive_subscribers_notice->disable();
        break;
    }
  }

}

