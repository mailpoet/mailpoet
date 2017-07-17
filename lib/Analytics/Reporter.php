<?php
namespace MailPoet\Analytics;

use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronTrigger;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\Pages;

class Reporter {

  function getData() {

    $mta = Setting::getValue('mta', array());
    $premium_status = Installer::getPremiumStatus();
    $newsletters = Newsletter::getAnalytics();
    $isCronTriggerMethodWP = Setting::getValue('cron_trigger.method') === CronTrigger::$available_methods["wordpress"];
    $checker = new ServicesChecker();


    return array(
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'Premium Plugin Active' => $premium_status['premium_plugin_active'],
      'Total number of subscribers' =>  Subscriber::getTotalSubscribers(),
      'Sending Method' => $mta['method'],
      'Date of plugin installation' => Setting::getValue('installed_at'),
      'Subscribe in comments' => (boolean) Setting::getValue('subscribe.on_comment.enabled', false),
      'Subscribe in registration form' => (boolean) Setting::getValue('subscribe.on_register.enabled', false),
      'Manage Subscription page > MailPoet page' => (boolean) Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.manage'))),
      'Unsubscribe page > MailPoet page' => (boolean) Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.unsubscribe'))),
      'Sign-up confirmation' => (boolean) Setting::getValue('signup_confirmation.enabled', false),
      'Sign-up confirmation: Confirmation page > MailPoet page' => (boolean) Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.confirmation'))),
      'Bounce email address' => !empty(Setting::getValue('bounce.address')),
      'Newsletter task scheduler (cron)' => $isCronTriggerMethodWP ? "visitors" : "script",
      'Open and click tracking' => (boolean) Setting::getValue('tracking.enabled', false),
      'Premium key valid' => $checker->isPremiumKeyValid(),




      'Number of standard newsletters sent in last 3 months' => $newsletters['sent_newsletters'],
      'Number of active post notifications' => $newsletters['notifications_count'],
      'Number of active welcome emails' => $newsletters['welcome_newsletters_count'],
      'Is WooCommerce plugin installed' => is_plugin_active("woocommerce/woocommerce.php"),
      'Plugin > MailPoet Premium' => is_plugin_active("mailpoet-premium/mailpoet-premium.php"),
      'Plugin > bounce add-on' => is_plugin_active("mailpoet-bounce-handler.php"),
      'Plugin > Bloom' => is_plugin_active("bloom-for-publishers/bloom.php"),
    );
  }

}
