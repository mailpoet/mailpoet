<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;

class PermanentNotices {

  public function init() {
    $php_version_warnings = new PHPVersionWarnings();
    $php_version_warnings->init(phpversion(), Menu::isOnMailPoetAdminPage());
  }

}

