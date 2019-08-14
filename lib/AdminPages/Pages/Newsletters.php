<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\Config\Menu;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Util\Installation;
use MailPoet\Util\License\License;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Newsletters {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $user_flags;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var Installation */
  private $installation;

  function __construct(
    PageRenderer $page_renderer,
    PageLimit $listing_page_limit,
    WPFunctions $wp,
    SettingsController $settings,
    UserFlagsController $user_flags,
    WooCommerceHelper $woocommerce_helper,
    Installation $installation
  ) {
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->user_flags = $user_flags;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->installation = $installation;
  }

  function render() {
    global $wp_roles;

    $data = [];

    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('newsletters');
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = $this->wp->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;
    $data['settings'] = $this->settings->getAll();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();
    $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
    $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
    $data['site_url'] = $this->wp->siteUrl();
    $data['roles'] = $wp_roles->get_names();
    $data['roles']['mailpoet_all'] = $this->wp->__('In any WordPress role', 'mailpoet');

    $installedAtDateTime = new \DateTime($data['settings']['installed_at']);
    $data['installed_days_ago'] = (int)$installedAtDateTime->diff(new \DateTime())->format('%a');

    $date_time = new DateTime();
    $data['current_date'] = $date_time->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $date_time->getCurrentTime();
    $data['schedule_time_of_day'] = $date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      24
    );
    $data['mailpoet_main_page'] = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    $data['show_congratulate_after_first_newsletter'] = isset($data['settings']['show_congratulate_after_first_newsletter']) ? $data['settings']['show_congratulate_after_first_newsletter'] : 'false';

    $data['tracking_enabled'] = $this->settings->get('tracking.enabled');
    $data['premium_plugin_active'] = License::getLicense();
    $data['is_woocommerce_active'] = $this->woocommerce_helper->isWooCommerceActive();
    $data['is_mailpoet_update_available'] = array_key_exists(Env::$plugin_path, $this->wp->getPluginUpdates());
    if (!$data['premium_plugin_active']) {
      $data['subscribers_count'] = Subscriber::getTotalSubscribers();
      $data['free_premium_subscribers_limit'] = License::FREE_PREMIUM_SUBSCRIBERS_LIMIT;
    }

    $last_announcement_date = $this->settings->get('last_announcement_date');
    $last_announcement_seen = $this->user_flags->get('last_announcement_seen');
    $data['feature_announcement_has_news'] = (
      empty($last_announcement_seen) ||
      $last_announcement_seen < $last_announcement_date
    );
    $data['last_announcement_seen'] = $last_announcement_seen;

    $data['automatic_emails'] = [
      [
        'slug' => 'woocommerce',
        'premium' => true,
        'title' => $this->wp->__('WooCommerce', 'mailpoet'),
        'description' => $this->wp->__('Automatically send an email when there is a new WooCommerce product, order and some other action takes place.', 'mailpoet'),
        'events' => [
          [
            'slug' => 'woocommerce_abandoned_shopping_cart',
            'title' => $this->wp->__('Abandoned Shopping Cart', 'mailpoet'),
            'description' => $this->wp->__('Send an email to identified visitors who have items in their shopping carts but left your website without checking out. Can convert up to 20% of abandoned carts.', 'mailpoet'),
            'soon' => true,
            'badge' => [
              'text' => $this->wp->__('Must-have', 'mailpoet'),
              'style' => 'red',
            ],
          ],
          [
            'slug' => 'woocommerce_first_purchase',
            'title' => $this->wp->__('First Purchase', 'mailpoet'),
            'description' => $this->wp->__('Let MailPoet send an email to customers who make their first purchase.', 'mailpoet'),
            'badge' => [
              'text' => $this->wp->__('Must-have', 'mailpoet'),
              'style' => 'red',
            ],
          ],
          [
            'slug' => 'woocommerce_product_purchased_in_category',
            'title' => $this->wp->__('Purchased In This Category', 'mailpoet'),
            'description' => $this->wp->__('Let MailPoet send an email to customers who purchase a product from a specific category.', 'mailpoet'),
            'soon' => true,
          ],
          [
            'slug' => 'woocommerce_product_purchased',
            'title' => $this->wp->__('Purchased This Product', 'mailpoet'),
            'description' => $this->wp->__('Let MailPoet send an email to customers who purchase a specific product.', 'mailpoet'),
          ],
        ],
      ],
    ];

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->wp->wpEnqueueScript('jquery-ui');
    $this->wp->wpEnqueueScript('jquery-ui-datepicker');

    $this->page_renderer->displayPage('newsletters.html', $data);
  }
}
