<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;

class PermanentNotices {

  /** @var PHPVersionWarnings */
  private $php_version_warnings;

  /** @var AfterMigrationNotice */
  private $after_migration_notice;

  public function __construct() {
    $this->php_version_warnings = new PHPVersionWarnings();
    $this->after_migration_notice = new AfterMigrationNotice();
  }

  public function init() {
    add_action('wp_ajax_dismissed_notice_handler', array(
      $this,
      'ajaxDismissNoticeHandler'
    ));


    $this->php_version_warnings->init(phpversion(), Menu::isOnMailPoetAdminPage());
    $this->after_migration_notice->init(
      Menu::isOnMailPoetAdminPage()
      && $_GET['page'] !== 'mailpoet-welcome-wizard'
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
    }
  }

}

