<?php

namespace MailPoet\Util\Notices;

use MailPoet\Models\Setting;
use MailPoet\Util\Helpers;

class AfterMigrationNotice {

  const OPTION_NAME = 'mailpoet_display_after_migration_notice';

  function enable() {
    Setting::setValue(self::OPTION_NAME, true);
  }

  function disable() {
    Setting::setValue(self::OPTION_NAME, false);
  }

  function init($should_display) {
    if($should_display && Setting::getValue(self::OPTION_NAME, false)) {
      return $this->display();
    }
  }

  private function display() {
    $message = Helpers::replaceLinkTags(
      __('Congrats! You’re progressing well so far. Complete your upgrade thanks to this [link]checklist[/link].', 'mailpoet'),
      'https://beta.docs.mailpoet.com/article/199-checklist-after-migrating-to-mailpoet3',
      array('target' => '_blank')
    );

    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';
    $data_notice_name = self::OPTION_NAME;

    \MailPoet\WP\Notice::displaySuccess($message, $extra_classes, $data_notice_name);
    return $message;
  }

}
