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

  /** @var DiscountsAnnouncement */
  private $discounts_announcement;

  /** @var UnauthorizedEmailNotice */
  private $unauthorized_emails_notice;

  /** @var UnauthorizedEmailInNewslettersNotice */
  private $unauthorized_emails_in_newsletters_notice;

  /** @var InactiveSubscribersNotice */
  private $inactive_subscribers_notice;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
    $this->php_version_warnings = new PHPVersionWarnings();
    $this->after_migration_notice = new AfterMigrationNotice();
    $this->discounts_announcement = new DiscountsAnnouncement();
    $this->unauthorized_emails_notice = new UnauthorizedEmailNotice(new SettingsController, $wp);
    $this->unauthorized_emails_in_newsletters_notice = new UnauthorizedEmailInNewslettersNotice(new SettingsController, $wp);
    $this->inactive_subscribers_notice = new InactiveSubscribersNotice(new SettingsController, $wp);
  }

  public function init() {
    $this->wp->addAction('wp_ajax_dismissed_notice_handler', [
      $this,
      'ajaxDismissNoticeHandler',
    ]);

    $this->php_version_warnings->init(
      phpversion(),
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->after_migration_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->unauthorized_emails_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->unauthorized_emails_in_newsletters_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = null, $page_id = 'mailpoet-newsletters')
    );
    $this->inactive_subscribers_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->discounts_announcement->init(
      empty($_GET['page'])
      && $this->wp->isAdmin()
      && strpos($_SERVER['SCRIPT_NAME'], 'wp-admin/index.php') !== false
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
      case (DiscountsAnnouncement::OPTION_NAME):
        $this->discounts_announcement->disable();
        break;
      case (InactiveSubscribersNotice::OPTION_NAME):
        $this->inactive_subscribers_notice->disable();
        break;
    }
  }

}

