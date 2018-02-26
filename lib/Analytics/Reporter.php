<?php
namespace MailPoet\Analytics;

use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronTrigger;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\Pages;

class Reporter {

  function getData() {
    global $wpdb, $wp_version;
    $mta = Setting::getValue('mta', array());
    $newsletters = Newsletter::getAnalytics();
    $isCronTriggerMethodWP = Setting::getValue('cron_trigger.method') === CronTrigger::$available_methods['wordpress'];
    $checker = new ServicesChecker();
    $bounceAddress = Setting::getValue('bounce.address');
    $segments = Segment::getAnalytics();

    return array(
      'PHP version' => PHP_VERSION,
      'MySQL version' => $wpdb->db_version(),
      'WordPress version' => $wp_version,
      'Multisite environment' => is_multisite() ? 'yes' : 'no',
      'RTL' => is_rtl() ? 'yes' : 'no',
      'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
      'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
      'PHP memory_limit' => ini_get('memory_limit'),
      'PHP max_execution_time' => ini_get('max_execution_time'),
      'users_can_register' => get_option('users_can_register') ? 'yes' : 'no',
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'Total number of subscribers' =>  Subscriber::getTotalSubscribers(),
      'Sending Method' => isset($mta['method']) ? $mta['method'] : null,
      'Date of plugin installation' => Setting::getValue('installed_at'),
      'Subscribe in comments' => (boolean)Setting::getValue('subscribe.on_comment.enabled', false),
      'Subscribe in registration form' => (boolean)Setting::getValue('subscribe.on_register.enabled', false),
      'Manage Subscription page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.manage'))),
      'Unsubscribe page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.unsubscribe'))),
      'Sign-up confirmation' => (boolean)Setting::getValue('signup_confirmation.enabled', false),
      'Sign-up confirmation: Confirmation page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval(Setting::getValue('subscription.pages.confirmation'))),
      'Bounce email address' => !empty($bounceAddress),
      'Newsletter task scheduler (cron)' => $isCronTriggerMethodWP ? 'visitors' : 'script',
      'Open and click tracking' => (boolean)Setting::getValue('tracking.enabled', false),
      'Premium key valid' => $checker->isPremiumKeyValid(),
      'Number of standard newsletters sent in last 3 months' => $newsletters['sent_newsletters'],
      'Number of active post notifications' => $newsletters['notifications_count'],
      'Number of active welcome emails' => $newsletters['welcome_newsletters_count'],
      'Number of segments' => isset($segments['dynamic']) ? (int)$segments['dynamic'] : 0,
      'Number of lists' => isset($segments['default']) ? (int)$segments['default'] : 0,
      'Plugin > MailPoet Premium' => is_plugin_active('mailpoet-premium/mailpoet-premium.php'),
      'Plugin > bounce add-on' => is_plugin_active('mailpoet-bounce-handler/mailpoet-bounce-handler.php'),
      'Plugin > Bloom' => is_plugin_active('bloom-for-publishers/bloom.php'),
      'Plugin > WP Holler' => is_plugin_active('holler-box/holler-box.php'),
      'Plugin > WP-SMTP' => is_plugin_active('wp-mail-smtp/wp_mail_smtp.php'),
      'Plugin > WooCommerce' => is_plugin_active('woocommerce/woocommerce.php'),
      'Plugin > WooCommerce Subscription' => is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php'),
      'Plugin > WooCommerce Follow Up Emails' => is_plugin_active('woocommerce-follow-up-emails/woocommerce-follow-up-emails.php'),
      'Plugin > WooCommerce Email Customizer' => is_plugin_active('woocommerce-email-customizer/woocommerce-email-customizer.php'),
      'Plugin > WooCommerce Memberships' => is_plugin_active('woocommerce-memberships/woocommerce-memberships.php'),
      'Plugin > WooCommerce MailChimp' => is_plugin_active('woocommerce-mailchimp/woocommerce-mailchimp.php'),
      'Plugin > MailChimp for WooCommerce' => is_plugin_active('mailchimp-for-woocommerce/mailchimp-woocommerce.php'),
      'Plugin > The Event Calendar' => is_plugin_active('the-events-calendar/the-events-calendar.php'),
      'Plugin > Gravity Forms' => is_plugin_active('gravityforms/gravityforms.php'),
      'Plugin > Ninja Forms' => is_plugin_active('ninja-forms/ninja-forms.php'),
      'Plugin > WPForms' => is_plugin_active('wpforms-lite/wpforms.php'),
      'Plugin > Formidable Forms' => is_plugin_active('formidable/formidable.php'),
      'Plugin > Contact Form 7' => is_plugin_active('contact-form-7/wp-contact-form-7.php'),
      'Plugin > Easy Digital Downloads' => is_plugin_active('easy-digital-downloads/easy-digital-downloads.php'),
    );
  }

}
