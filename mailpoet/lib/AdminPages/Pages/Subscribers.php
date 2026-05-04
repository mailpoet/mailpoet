<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\BulkConfirmationEmailResender;

class Subscribers {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var Block\Date */
  private $dateBlock;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    Block\Date $dateBlock,
    SegmentsSimpleListRepository $segmentsListRepository,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    SettingsController $settings
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->dateBlock = $dateBlock;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->settings = $settings;
  }

  public function render() {
    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $data['segments'] = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();

    $data['custom_fields'] = array_map(function(CustomFieldEntity $customField): array {
      $field = $this->customFieldsResponseBuilder->build($customField);

      if (!empty($field['params']['values'])) {
        $values = [];

        foreach ($field['params']['values'] as $value) {
          $values[$value['value']] = $value['value'];
        }
        $field['params']['values'] = $values;
      }
      return $field;
    }, $this->customFieldsRepository->findAllActive());

    $data['date_formats'] = $this->dateBlock->getDateFormats();
    $data['month_names'] = $this->dateBlock->getMonthNames();
    $data['signup_confirmation_enabled'] = (bool)$this->settings->get(
      'signup_confirmation.enabled'
    );
    $data['bulk_confirmation_resend_limit'] = BulkConfirmationEmailResender::BULK_CONFIRMATION_RESEND_LIMIT;
    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
