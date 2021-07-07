<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Cache\TransientCache;
use MailPoet\Config\ServicesChecker;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Newsletter;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\AutocompletePostListLoader as WPPostListLoader;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Segments {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPPostListLoader */
  private $wpPostListLoader;

  /** @var SettingsController */
  private $settings;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  /** @var TransientCache */
  private $transientCache;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    ServicesChecker $servicesChecker,
    WPFunctions $wp,
    WooCommerceHelper $woocommerceHelper,
    WPPostListLoader $wpPostListLoader,
    SubscribersFeature $subscribersFeature,
    SettingsController $settings,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    SegmentDependencyValidator $segmentDependencyValidator,
    TransientCache $transientCache
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wpPostListLoader = $wpPostListLoader;
    $this->settings = $settings;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->transientCache = $transientCache;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');

    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();

    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();
    $data['has_premium_support'] = $this->subscribersFeature->hasPremiumSupport();
    $data['mss_key_invalid'] = ($this->servicesChecker->isMailPoetAPIKeyValid() === false);
    $customFields = $this->customFieldsRepository->findBy([], ['name' => 'asc']);
    $data['custom_fields'] = $this->customFieldsResponseBuilder->buildBatch($customFields);

    $wpRoles = $this->wp->getEditableRoles();
    $data['wordpress_editable_roles_list'] = array_map(function($roleId, $role) {
      return [
        'role_id' => $roleId,
        'role_name' => $role['name'],
      ];
    }, array_keys($wpRoles), $wpRoles);

    $data['newsletters_list'] = Newsletter::select(['id', 'subject', 'sent_at'])
      ->whereNull('deleted_at')
      ->where('type', Newsletter::TYPE_STANDARD)
      ->orderByExpr('ISNULL(sent_at) DESC, sent_at DESC')->findArray();

    $data['product_categories'] = $this->wpPostListLoader->getWooCommerceCategories();

    $data['products'] = $this->wpPostListLoader->getProducts();
    $data['subscription_products'] = $this->wpPostListLoader->getSubscriptionProducts();
    $data['is_woocommerce_active'] = $this->woocommerceHelper->isWooCommerceActive();
    $wcCountries = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getAllowedCountries() : [];
    $data['woocommerce_countries'] = array_map(function ($code, $name) {
      return [
        'name' => $name,
        'code' => $code,
      ];
    }, array_keys($wcCountries), $wcCountries);
    $data['can_use_woocommerce_subscriptions'] = $this->segmentDependencyValidator->canUseDynamicFilterType(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION
    );
    $wooCurrencySymbol = $this->woocommerceHelper->isWooCommerceActive() ? $this->woocommerceHelper->getWoocommerceCurrencySymbol() : '';
    $data['woocommerce_currency_symbol'] = html_entity_decode($wooCurrencySymbol);
    $data['tracking_enabled'] = $this->settings->get('tracking.enabled');
    $subscribersCacheCreatedAt = $this->transientCache->getOldestCreatedAt(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    $subscribersCacheCreatedAt = $subscribersCacheCreatedAt ?: Carbon::now();
    $data['subscribers_counts_cache_created_at'] = $subscribersCacheCreatedAt->format('Y-m-d\TH:i:sO');
    $this->pageRenderer->displayPage('segments.html', $data);
  }
}
