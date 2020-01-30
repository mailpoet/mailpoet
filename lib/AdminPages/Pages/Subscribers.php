<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
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

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    SubscribersFeature $subscribersFeature,
    WPFunctions $wp,
    Block\Date $dateBlock
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->wp = $wp;
    $this->dateBlock = $dateBlock;
  }

  public function render() {
    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = $this->wp->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;

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

    $data['max_confirmation_emails'] = ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS;

    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();
    $data['subscriber_count'] = Subscriber::getTotalSubscribers();

    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
