<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\BulkConfirmationEmailResender;
use MailPoet\WP\Functions as WPFunctions;

class Subscribers {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var AssetsController */
  private $assetsController;

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

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    AssetsController $assetsController,
    PageLimit $listingPageLimit,
    Block\Date $dateBlock,
    SegmentsSimpleListRepository $segmentsListRepository,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->assetsController = $assetsController;
    $this->listingPageLimit = $listingPageLimit;
    $this->dateBlock = $dateBlock;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function render() {
    $data = [];
    $this->assetsController->setupDataViewsDependencies();

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $data['segments'] = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();
    $data['api'] = [
      'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
      'nonce' => $this->wp->wpCreateNonce('wp_rest'),
    ];

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
    $collectSubscriberTimeZones = $this->settings->isSettingEnabled('collect_subscriber_timezones.enabled');
    $data['collect_subscriber_timezones'] = $collectSubscriberTimeZones;
    // Same identifier list SubscriberEntity::isValidTimeZone() accepts when
    // storing browser-detected timezones, so the selector can offer exactly
    // the values that can be saved.
    $data['timezone_list'] = $collectSubscriberTimeZones ? \DateTimeZone::listIdentifiers() : [];
    $this->assetsController->setupDataViewsDependencies();
    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
