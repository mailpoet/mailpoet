<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\Config\Installer;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\Features\FeaturesController;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Util\Installation;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;

class Newsletters {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var Installation */
  private $installation;

  /** @var FeaturesController */
  private $featuresController;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    WPFunctions $wp,
    SettingsController $settings,
    UserFlagsController $userFlags,
    WooCommerceHelper $woocommerceHelper,
    Installation $installation,
    FeaturesController $featuresController,
    SubscribersFeature $subscribersFeature,
    ServicesChecker $servicesChecker
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->installation = $installation;
    $this->featuresController = $featuresController;
    $this->subscribersFeature = $subscribersFeature;
    $this->servicesChecker = $servicesChecker;
  }

  public function render() {
    global $wp_roles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

    if (isset($_GET['stats'])) {
      $this->wp->wpSafeRedirect(
        $this->wp->getSiteUrl(null,
          '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $_GET['stats']
        )
      );
      exit;
    }

    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('newsletters');
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = $this->wp->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;
    $data['settings'] = $this->settings->getAll();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();
    $data['has_mss_key_specified'] = Bridge::isMSSKeySpecified();
    $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
    $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
    $data['site_url'] = $this->wp->siteUrl();
    $data['roles'] = $wp_roles->get_names(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data['roles']['mailpoet_all'] = $this->wp->__('In any WordPress role', 'mailpoet');

    $mssActive = Bridge::isMPSendingServiceEnabled();
    $mssKeyValid = $this->servicesChecker->isMailPoetAPIKeyValid();
    $mssKeyPendingApproval = $this->settings->get('mta.mailpoet_api_key_state.data.is_approved') === false;
    $data['mss_key_pending_approval'] = $mssActive && $mssKeyValid && $mssKeyPendingApproval;

    $installedAtDateTime = new \DateTime($data['settings']['installed_at']);
    $data['installed_days_ago'] = (int)$installedAtDateTime->diff(new \DateTime())->format('%a');
    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();

    $dateTime = new DateTime();
    $data['current_date'] = $dateTime->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $dateTime->getCurrentTime();
    $data['schedule_time_of_day'] = $dateTime->getTimeInterval(
      '00:00:00',
      '+1 hour',
      24
    );
    $data['mailpoet_main_page'] = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    $data['show_congratulate_after_first_newsletter'] = isset($data['settings']['show_congratulate_after_first_newsletter']) ? $data['settings']['show_congratulate_after_first_newsletter'] : 'false';

    $data['tracking_enabled'] = $this->settings->get('tracking.enabled');
    $data['premium_plugin_active'] = License::getLicense();
    $data['is_woocommerce_active'] = $this->woocommerceHelper->isWooCommerceActive();
    $data['is_mailpoet_update_available'] = array_key_exists(Env::$pluginPath, $this->wp->getPluginUpdates());
    $data['subscriber_count'] = Subscriber::getTotalSubscribers();
    $data['newsletters_count'] = Newsletter::count();
    $data['mailpoet_feature_flags'] = $this->featuresController->getAllFlags();

    if (!$data['premium_plugin_active']) {
      $data['free_premium_subscribers_limit'] = License::FREE_PREMIUM_SUBSCRIBERS_LIMIT;
    }

    $lastAnnouncementDate = $this->settings->get('last_announcement_date');
    $lastAnnouncementSeen = $this->userFlags->get('last_announcement_seen');
    $data['feature_announcement_has_news'] = (
      empty($lastAnnouncementSeen) ||
      $lastAnnouncementSeen < $lastAnnouncementDate
    );
    $data['last_announcement_seen'] = $lastAnnouncementSeen;

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
    $data['sent_newsletters_count'] = (int)Newsletter::where('status', Newsletter::STATUS_SENT)->count();
    $data['woocommerce_transactional_email_id'] = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID);
    $data['display_detailed_stats'] = Installer::getPremiumStatus()['premium_plugin_initialized'];
    $this->wp->wpEnqueueScript('jquery-ui');
    $this->wp->wpEnqueueScript('jquery-ui-datepicker');

    $this->pageRenderer->displayPage('newsletters.html', $data);
  }
}
