<?php
namespace MailPoet\Listing;

if (!defined('ABSPATH')) exit;

use MailPoet\WP\Functions as WPFunctions;

class PageLimit {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  /** @var WPFunctions */
  private $wp;

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function getLimitPerPage($model = null) {
    if ($model === null) {
      return self::DEFAULT_LIMIT_PER_PAGE;
    }

    $listing_per_page = $this->wp->getUserMeta(
      $this->wp->getCurrentUserId(), 'mailpoet_' . $model . '_per_page', true
    );
    return (!empty($listing_per_page))
      ? (int)$listing_per_page
      : self::DEFAULT_LIMIT_PER_PAGE;
  }
}
