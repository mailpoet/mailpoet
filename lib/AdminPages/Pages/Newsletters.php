<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\Config\Env;
use MailPoet\Config\Installer;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\Features\FeaturesController;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Newsletter;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Util\Installation;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\AutocompletePostListLoader as WPPostListLoader;
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

  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  /** @var AutomaticEmails */
  private $automaticEmails;

  /** @var WPPostListLoader */
  private $wpPostListLoader;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

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
    ServicesChecker $servicesChecker,
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    WPPostListLoader $wpPostListLoader,
    AutomaticEmails $automaticEmails,
    SegmentSubscribersRepository $segmentSubscribersRepository
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
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->automaticEmails = $automaticEmails;
    $this->wpPostListLoader = $wpPostListLoader;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
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
    $segments = $this->segmentSubscribersRepository->getSimpleSegmentListWithSubscribersCounts();
    $data['segments'] = $segments;
    $data['settings'] = $this->settings->getAll();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();
    $data['has_mss_key_specified'] = Bridge::isMSSKeySpecified();
    $data['mss_key_pending_approval'] = $this->servicesChecker->isMailPoetAPIKeyPendingApproval();
    $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
    $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
    $data['site_url'] = $this->wp->siteUrl();
    $data['roles'] = $wp_roles->get_names(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data['roles']['mailpoet_all'] = $this->wp->__('In any WordPress role', 'mailpoet');

    $installedAtDiff = (new \DateTime($this->settings->get('installed_at')))->diff(new \DateTime());
    $data['installed_days_ago'] = (int)$installedAtDiff->format('%a');

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
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();
    $data['newsletters_count'] = Newsletter::count();
    $data['mailpoet_feature_flags'] = $this->featuresController->getAllFlags();
    $data['transactional_emails_opt_in_notice_dismissed'] = $this->userFlags->get('transactional_emails_opt_in_notice_dismissed');

    if (!$data['premium_plugin_active']) {
      $data['free_premium_subscribers_limit'] = License::FREE_PREMIUM_SUBSCRIBERS_LIMIT;
    }

    $data['mss_key_invalid'] = ($this->servicesChecker->isMailPoetAPIKeyValid() === false);

    $data['automatic_emails'] = $this->automaticEmails->getAutomaticEmails();
    $data['woocommerce_optin_on_checkout'] = $this->settings->get('woocommerce.optin_on_checkout.enabled', false);

    $data['is_new_user'] = $this->installation->isNewInstallation();
    $data['sent_newsletters_count'] = (int)Newsletter::where('status', Newsletter::STATUS_SENT)->count();
    $data['woocommerce_transactional_email_id'] = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID);
    $data['display_detailed_stats'] = Installer::getPremiumStatus()['premium_plugin_initialized'];
    $data['newsletters_templates_recently_sent_count'] = $this->newsletterTemplatesRepository->getRecentlySentCount();

    $data['product_categories'] = $this->wpPostListLoader->getWooCommerceCategories();

    $data['products'] = $this->wpPostListLoader->getProducts();

    $this->pageRenderer->displayPage('newsletters.html', $data);
  }
}
