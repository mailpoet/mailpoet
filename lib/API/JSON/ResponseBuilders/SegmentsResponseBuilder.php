<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\WP\Functions;

class SegmentsResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var Functions */
  private $wp;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  public function __construct(
    Functions $wp,
    NewsletterSegmentRepository $newsletterSegmentRepository
  ) {
    $this->wp = $wp;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
  }

  /**
   * @return array
   */
  public function build(SegmentEntity $segment) {
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => $segment->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }

  public function buildForListing(array $segments): array {
    $data = [];
    $segmendIds = array_map(function(SegmentEntity $segment): int {
      return (int)$segment->getId();
    }, $segments);
    $scheduledNewsletterSubjectsMap = $this->newsletterSegmentRepository->getScheduledNewsletterSubjectsBySegmentIds($segmendIds);
    $automatedNewsletterSubjectsMap = $this->newsletterSegmentRepository->getAutomatedEmailSubjectsBySegmentIds($segmendIds);
    foreach ($segments as $segment) {
      $data[] = $this->buildListingItem($segment, $scheduledNewsletterSubjectsMap, $automatedNewsletterSubjectsMap);
    }
    return $data;
  }

  private function buildListingItem(SegmentEntity $segment, array $scheduledNewsletterSubjectsMap, array $automatedNewsletterSubjectsMap): array {
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => $segment->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'automated_emails_subjects' => $automatedNewsletterSubjectsMap[$segment->getId()] ?? [],
      'scheduled_emails_subjects' => $scheduledNewsletterSubjectsMap[$segment->getId()] ?? [],
      'subscribers_count' => [], // TODO
      'subscribers_url' => $this->wp->adminUrl(
        'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->getId() . ']'
      ),
    ];
  }
}
