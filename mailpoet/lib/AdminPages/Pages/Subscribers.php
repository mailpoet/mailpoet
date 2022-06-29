<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Cache\TransientCache;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\CustomField;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Tags\TagRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Subscribers {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var WPFunctions */
  private $wp;

  /** @var Block\Date */
  private $dateBlock;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var TransientCache */
  private $transientCache;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    SubscribersFeature $subscribersFeature,
    WPFunctions $wp,
    ServicesChecker $servicesChecker,
    Block\Date $dateBlock,
    SegmentsSimpleListRepository $segmentsListRepository,
    TagRepository $tagRepository,
    TransientCache $transientCache,
    TrackingConfig $trackingConfig
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->wp = $wp;
    $this->dateBlock = $dateBlock;
    $this->servicesChecker = $servicesChecker;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->tagRepository = $tagRepository;
    $this->transientCache = $transientCache;
    $this->trackingConfig = $trackingConfig;
  }

  public function render() {
    $installer = new Installer(Installer::PREMIUM_PLUGIN_SLUG);
    $pluginInformation = $installer->retrievePluginInformation();

    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $data['segments'] = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();

    $data['tags'] = array_map(function (TagEntity $tag): array {
      return [
        'id' => $tag->getId(),
        'name' => $tag->getName(),
      ];
    }, $this->tagRepository->findAll());

    $data['custom_fields'] = array_map(function($field) {
      $field['params'] = unserialize($field['params']);

      if (!empty($field['params']['values'])) {
        $values = [];

        foreach ($field['params']['values'] as $value) {
          $values[$value['value']] = $value['value'];
        }
        $field['params']['values'] = $values;
      }
      return $field;
    }, CustomField::findArray());

    $data['date_formats'] = $this->dateBlock->getDateFormats();
    $data['month_names'] = $this->dateBlock->getMonthNames();

    $data['premium_plugin_active'] = License::getLicense();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();

    $data['mss_key_invalid'] = ($this->servicesChecker->isMailPoetAPIKeyValid() === false);

    $data['max_confirmation_emails'] = ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS;

    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();
    $data['has_valid_premium_key'] = $this->subscribersFeature->hasValidPremiumKey();
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();
    $data['has_premium_support'] = $this->subscribersFeature->hasPremiumSupport();
    $data['link_premium'] = $this->wp->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-upgrade');
    $data['tracking_config'] = $this->trackingConfig->getConfig();

    $data['current_wp_user_email'] = $this->wp->wpGetCurrentUser()->user_email;
    $data['premium_plugin_installed'] = $data['premium_plugin_active'] || Installer::isPluginInstalled(Installer::PREMIUM_PLUGIN_SLUG);
    $data['premium_plugin_download_url'] = $pluginInformation->download_link ?? null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $data['premium_plugin_activation_url'] = $installer->generatePluginActivationUrl(Installer::PREMIUM_PLUGIN_PATH);
    $data['plugin_partial_key'] = $this->servicesChecker->generatePartialApiKey();
    $data['email_volume_limit_reached'] = $this->subscribersFeature->checkEmailVolumeLimitIsReached();
    $data['email_volume_limit'] = $this->subscribersFeature->getEmailVolumeLimit();

    $subscribersCacheCreatedAt = $this->transientCache->getOldestCreatedAt(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    $subscribersCacheCreatedAt = $subscribersCacheCreatedAt ?: Carbon::now();
    $data['subscribers_counts_cache_created_at'] = $subscribersCacheCreatedAt->format('Y-m-d\TH:i:sO');
    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
