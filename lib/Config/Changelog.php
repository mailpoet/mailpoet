<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class Changelog {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;
  
  function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

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
    $version = $this->settings->get('version');
    $redirect_url = null;

    $mp2_migrator = new MP2Migrator();
    if(!in_array($_GET['page'], array('mailpoet-migration', 'mailpoet-settings')) && $mp2_migrator->isMigrationStartedAndNotCompleted()) {
      // Force the redirection if the migration has started but is not completed
      $redirect_url = admin_url('admin.php?page=mailpoet-migration');
    } else {
      if($version === null) {
        // new install
        if($mp2_migrator->isMigrationNeeded()) {
          // Migration from MP2
          $redirect_url = admin_url('admin.php?page=mailpoet-migration');
        } else {
          $skip_wizard = $this->wp->applyFilters('mailpoet_skip_welcome_wizard', false);
          $redirect_url = $skip_wizard ? null : admin_url('admin.php?page=mailpoet-welcome-wizard');

          // ensure there was no MP2 migration (migration resets $version so it must be checked)
          if($this->settings->get('mailpoet_migration_started') === null) {
            $this->settings->set('show_intro', true);
          }
        }
        $this->settings->set('show_congratulate_after_first_newsletter', true);
        $this->settings->set('show_poll_success_delivery_preview', true);
      }
    }

    if($redirect_url !== null) {
      // save version number
      $this->settings->set('version', Env::$version);

      Url::redirectWithReferer($redirect_url);
    }
  }
}
