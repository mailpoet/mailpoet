<?php
namespace MailPoet\Config;
use \MailPoet\Models\Setting;

class Changelog {
  function init() {
    add_action(
       'admin_init',
      array($this, 'setup')
    );
  }

  function setup() {
    $version = Setting::getValue('version', null);
    if($version === null) {
      // new install
      Setting::setValue('version', Env::$version);
      wp_redirect(admin_url('admin.php?page=mailpoet-welcome'));
      exit;
    } else if($version !== Env::$version) {
      // update
      Setting::setValue('version', Env::$version);
      wp_redirect(admin_url('admin.php?page=mailpoet-update'));
      exit;
    }
  }
}