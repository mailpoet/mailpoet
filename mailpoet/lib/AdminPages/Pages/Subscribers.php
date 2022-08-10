<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Cache\TransientCache;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Tags\TagRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Carbon\Carbon;

class Subscribers {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var Block\Date */
  private $dateBlock;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var TransientCache */
  private $transientCache;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    SubscribersFeature $subscribersFeature,
    Block\Date $dateBlock,
    SegmentsSimpleListRepository $segmentsListRepository,
    TagRepository $tagRepository,
    TransientCache $transientCache,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->dateBlock = $dateBlock;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->tagRepository = $tagRepository;
    $this->transientCache = $transientCache;
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
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();

    $subscribersCacheCreatedAt = $this->transientCache->getOldestCreatedAt(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    $subscribersCacheCreatedAt = $subscribersCacheCreatedAt ?: Carbon::now();
    $data['subscribers_counts_cache_created_at'] = $subscribersCacheCreatedAt->format('Y-m-d\TH:i:sO');
    $this->pageRenderer->displayPage('subscribers/subscribers.html', $data);
  }
}
