<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Listing;

use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\WP\Functions as WPFunctions;

class PageLimit {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getLimitPerPage($model = null) {
    if ($model === null) {
      return self::DEFAULT_LIMIT_PER_PAGE;
    }

    $listingPerPage = $this->wp->getUserMeta(
      $this->wp->getCurrentUserId(),
      'mailpoet_' . $model . '_per_page',
      true
    );
    $listingPerPage = is_numeric($listingPerPage) ? (int)$listingPerPage : 0;
    if ($listingPerPage < 1) {
      return self::DEFAULT_LIMIT_PER_PAGE;
    }
    // Older versions let admins save any value here,
    // but the listing endpoints reject a page size over this maximum.
    return min($listingPerPage, AbstractListingEndpoint::MAX_PER_PAGE);
  }
}
