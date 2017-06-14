<?php
namespace MailPoet\Analytics;

use MailPoet\Config\Installer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

class Reporter {

  function getData() {

    $mta = Setting::getValue('mta', array());
    $premium_status = Installer::getPremiumStatus();
    $newsletters = Newsletter::getAnalytics();


    return array(
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'Premium Plugin Installed' => $premium_status['premium_plugin_installed'],
      'Premium Plugin Active' => $premium_status['premium_plugin_active'],
      'Total number of subscribers' =>  Subscriber::getTotalSubscribers(),
      'Sending Method' => $mta['method'],
      'Date of plugin installation' => Setting::getValue('installed_at'),
      'Number of standard newsletters sent in last 3 months' => $newsletters['sent_newsletters'],
      'Number of active post notifications' => $newsletters['notifications_count'],
      'Number of active welcome emails' => $newsletters['welcome_newsletters_count'],
      'Is WooCommerce plugin installed' => is_plugin_active("woocommerce/woocommerce.php"),
    );
  }

}
