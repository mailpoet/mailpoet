<?php

namespace MailPoet\Util\Notices;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class AfterMigrationNotice {

  const OPTION_NAME = 'mailpoet_display_after_migration_notice';

  /** @var SettingsController */
  private $settings;

  function __construct() {
    $this->settings = new SettingsController();
  }

  function enable() {
    $this->settings->set(self::OPTION_NAME, true);
  }

  function disable() {
    $this->settings->set(self::OPTION_NAME, false);
  }

  function init($should_display) {
    if ($should_display && $this->settings->get(self::OPTION_NAME, false)) {
      return $this->display();
    }
  }

  private function display() {
    $message = Helpers::replaceLinkTags(
      WPFunctions::get()->__('Congrats! You’re progressing well so far. Complete your upgrade thanks to this [link]checklist[/link].', 'mailpoet'),
      'https://kb.mailpoet.com/article/199-checklist-after-migrating-to-mailpoet3',
      ['target' => '_blank']
    );

    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';
    $data_notice_name = self::OPTION_NAME;

    \MailPoet\WP\Notice::displaySuccess($message, $extra_classes, $data_notice_name);
    return $message;
  }

}
