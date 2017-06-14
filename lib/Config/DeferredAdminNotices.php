<?php

namespace MailPoet\Config;

use MailPoet\WP\Notice;

class DeferredAdminNotices {

  const OPTIONS_KEY_NAME = 'mailpoet_deferred_admin_notices';

  /**
   * @param string $message
   */
  public function addNetworkAdminNotice($message) {
    $notices = get_option(DeferredAdminNotices::OPTIONS_KEY_NAME, array());
    $notices[] = array(
      "message" => $message,
      "networkAdmin" => true,// if we'll need to display the notice to anyone else
    );
    update_option(DeferredAdminNotices::OPTIONS_KEY_NAME, $notices);
  }

  public function printAndClean() {
    $notices = get_option(DeferredAdminNotices::OPTIONS_KEY_NAME, array());

    foreach($notices as $notice) {
      $notice = new Notice(Notice::TYPE_WARNING, $notice["message"]);
      add_action('network_admin_notices', array($notice, 'displayWPNotice'));
    }

    if(!empty($notices)) {
      delete_option(DeferredAdminNotices::OPTIONS_KEY_NAME);
    }
  }

}