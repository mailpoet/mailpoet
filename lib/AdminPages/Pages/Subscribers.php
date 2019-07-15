<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Subscribers {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  /** @var WPFunctions */
  private $wp;

  function __construct(PageRenderer $page_renderer, PageLimit $listing_page_limit, WPFunctions $wp) {
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
    $this->wp = $wp;
  }

  function render() {
    $data = [];

    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('subscribers');
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

    $data['date_formats'] = Block\Date::getDateFormats();
    $data['month_names'] = Block\Date::getMonthNames();

    $data['premium_plugin_active'] = License::getLicense();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();

    $this->page_renderer->displayPage('subscribers/subscribers.html', $data);
  }
}
