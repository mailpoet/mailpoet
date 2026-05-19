<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\FormsResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Form\Listing\FormListingRepository;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\WP\Functions as WPFunctions;

class FormsListingEndpoint extends AbstractListingEndpoint {
  /** @var FormListingRepository */
  private $formListingRepository;

  /** @var FormsResponseBuilder */
  private $formsResponseBuilder;

  public function __construct(
    ListingHandler $listingHandler,
    FormListingRepository $formListingRepository,
    FormsResponseBuilder $formsResponseBuilder
  ) {
    parent::__construct($listingHandler);
    $this->formListingRepository = $formListingRepository;
    $this->formsResponseBuilder = $formsResponseBuilder;
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_FORMS);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->formListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    return $this->formsResponseBuilder->buildForListing($rows);
  }

  protected function getDefaultSortBy(): string {
    return 'updated_at';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }
}
