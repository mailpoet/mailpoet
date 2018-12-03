<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;

class PermanentNotices {

  /** @var PHPVersionWarnings */
  private $php_version_warnings;

  /** @var AfterMigrationNotice */
  private $after_migration_notice;

  private $discounts_announcement;

  public function __construct() {
    $this->php_version_warnings = new PHPVersionWarnings();
    $this->after_migration_notice = new AfterMigrationNotice();
    $this->discounts_announcement = new DiscountsAnnouncement();
  }

  public function init() {
    add_action('wp_ajax_dismissed_notice_handler', array(
      $this,
      'ajaxDismissNoticeHandler'
    ));

    $this->php_version_warnings->init(
      phpversion(),
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->after_migration_notice->init(
      Menu::isOnMailPoetAdminPage($exclude = ['mailpoet-welcome-wizard'])
    );
    $this->discounts_announcement->init(
      empty($_GET['page'])
      && is_admin()
      && strpos($_SERVER['SCRIPT_NAME'], 'wp-admin/index.php') !== false
    );
  }

  function ajaxDismissNoticeHandler() {
    if(!isset($_POST['type'])) return;
    switch($_POST['type']) {
      case (PHPVersionWarnings::OPTION_NAME):
        $this->php_version_warnings->disable();
        break;
      case (AfterMigrationNotice::OPTION_NAME):
        $this->after_migration_notice->disable();
        break;
      case (DiscountsAnnouncement::OPTION_NAME):
        $this->discounts_announcement->disable();
        break;
    }
  }

}

