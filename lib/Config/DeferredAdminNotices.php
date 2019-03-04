<?php

namespace MailPoet\Config;

use MailPoet\WP\Notice;
use MailPoet\WP\Functions as WPFunctions;

class DeferredAdminNotices {

  const OPTIONS_KEY_NAME = 'mailpoet_deferred_admin_notices';

  /**
   * @param string $message
   */
  public function addNetworkAdminNotice($message) {
    $notices = WPFunctions::get()->getOption(DeferredAdminNotices::OPTIONS_KEY_NAME, array());
    $notices[] = array(
      "message" => $message,
      "networkAdmin" => true,// if we'll need to display the notice to anyone else
    );
    WPFunctions::get()->updateOption(DeferredAdminNotices::OPTIONS_KEY_NAME, $notices);
  }

  public function printAndClean() {
    $notices = WPFunctions::get()->getOption(DeferredAdminNotices::OPTIONS_KEY_NAME, array());

    foreach ($notices as $notice) {
      $notice = new Notice(Notice::TYPE_WARNING, $notice["message"]);
      WPFunctions::get()->addAction('network_admin_notices', array($notice, 'displayWPNotice'));
    }

    if (!empty($notices)) {
      WPFunctions::get()->deleteOption(DeferredAdminNotices::OPTIONS_KEY_NAME);
    }
  }

}
