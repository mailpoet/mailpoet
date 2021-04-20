<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\CustomField;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

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

  /** @var SettingsController */
  private $settings;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    SubscribersFeature $subscribersFeature,
    WPFunctions $wp,
    ServicesChecker $servicesChecker,
    Block\Date $dateBlock,
    SettingsController $settings,
    SegmentsSimpleListRepository $segmentsListRepository
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->wp = $wp;
    $this->dateBlock = $dateBlock;
    $this->servicesChecker = $servicesChecker;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->settings = $settings;
  }

  public function render() {
    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $data['segments'] = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();

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
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();
    $data['has_premium_support'] = $this->subscribersFeature->hasPremiumSupport();
    $data['link_premium'] = $this->wp->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-premium');
    $data['tracking_enabled'] = $this->settings->get('tracking.enabled');

    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
