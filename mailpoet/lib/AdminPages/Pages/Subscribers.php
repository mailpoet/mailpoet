<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Tags\TagRepository;

class Subscribers {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var Block\Date */
  private $dateBlock;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    Block\Date $dateBlock,
    SegmentsSimpleListRepository $segmentsListRepository,
    TagRepository $tagRepository,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->dateBlock = $dateBlock;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->tagRepository = $tagRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
  }

  public function render() {
    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('subscribers');
    $data['segments'] = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();

    $data['tags'] = array_map(function (TagEntity $tag): array {
      return [
        'id' => $tag->getId(),
        'name' => $tag->getName(),
      ];
    }, $this->tagRepository->findAll());

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
    }, $this->customFieldsRepository->findAll());

    $data['date_formats'] = $this->dateBlock->getDateFormats();
    $data['month_names'] = $this->dateBlock->getMonthNames();
    $data['max_confirmation_emails'] = ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS;
    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
