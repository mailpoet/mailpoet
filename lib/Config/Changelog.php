<?php
namespace MailPoet\Config;
use \MailPoet\Models\Setting;

class Changelog {
  function init() {
    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);

    // don't run any check when it's an ajax request
    if($doing_ajax) {
      return;
    }

    // don't run any check when we're not on our pages
    if(
      !(isset($_GET['page']))
      or
      (isset($_GET['page']) && strpos($_GET['page'], 'mailpoet') !== 0)
    ) {
      return;
    }

    add_action(
      'admin_init',
      array($this, 'check')
    );
  }

  function check() {
    $version = Setting::getValue('version', null);
    $redirect_url = null;

    if($version === null) {
      // new install
      $redirect_url = admin_url('admin.php?page=mailpoet-welcome');
    } else if($version !== Env::$version) {
      // update
      $redirect_url = admin_url('admin.php?page=mailpoet-update');
    }

    if($redirect_url !== null) {
      // save version number
      Setting::setValue('version', Env::$version);

      global $wp;
      $current_url = home_url(add_query_arg($wp->query_string, $wp->request));

      if($redirect_url !== $current_url) {
        wp_safe_redirect(
          add_query_arg(
            array(
              'mailpoet_redirect' => urlencode($current_url)
            ),
            $redirect_url
          )
        );
        exit;
      }
    }
  }
}
