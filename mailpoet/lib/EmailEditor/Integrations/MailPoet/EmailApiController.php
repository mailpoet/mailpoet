<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\NotFoundException;
use MailPoet\UnexpectedValueException;
use MailPoet\Validator\Builder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailApiController {
  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    NewslettersRepository $newsletterRepository,
    NewsletterUrl $newsletterUrl,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterSegmentRepository $newsletterSegmentRepository,
    EntityManager $entityManager
  ) {
    $this->newsletterRepository = $newsletterRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
    $this->entityManager = $entityManager;
  }

  /**
   * @param array $postEmailData - WP_Post data
   * @return array - MailPoet specific email data that will be attached to the post API response
   */
  public function getEmailData($postEmailData): array {
    $newsletter = $this->newsletterRepository->findOneBy(['wpPost' => $postEmailData['id']]);
    return [
      'id' => $newsletter ? $newsletter->getId() : null,
      'subject' => $newsletter ? $newsletter->getSubject() : '',
      'preheader' => $newsletter ? $newsletter->getPreheader() : '',
      'preview_url' => $this->newsletterUrl->getViewInBrowserUrl($newsletter),
      'deleted_at' => $newsletter && $newsletter->getDeletedAt() !== null ? $newsletter->getDeletedAt()->format('c') : null,
      'scheduled_at' => $newsletter ? $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULED_AT) : null,
      'utm_campaign' => $newsletter ? $newsletter->getGaCampaign() : '',
      'segment_ids' => $newsletter ? $newsletter->getSegmentIds() : [],
    ];
  }

  /**
   * Update MailPoet specific data we store with Emails.
   */
  public function saveEmailData(array $data, \WP_Post $emailPost): void {
    $newsletter = $this->newsletterRepository->findOneById($data['id']);
    if (!$newsletter) {
      throw new NotFoundException('Newsletter was not found');
    }
    if ($newsletter->getWpPostId() !== $emailPost->ID) {
      throw new UnexpectedValueException('Newsletter ID does not match the post ID');
    }

    $newsletter->setSubject($data['subject']);
    $newsletter->setPreheader($data['preheader']);

    if (isset($data['utm_campaign'])) {
      $newsletter->setGaCampaign($data['utm_campaign']);
    }

    if (isset($data['deleted_at'])) {
      if (empty($data['deleted_at'])) {
        $data['deleted_at'] = null;
      } else {
        $data['deleted_at'] = new \DateTime($data['deleted_at']);
      }
      $newsletter->setDeletedAt($data['deleted_at']);
    }

    if (isset($data['scheduled_at'])) {
      $this->updateScheduledAtOption($newsletter, $data['scheduled_at']);
    }

    if (isset($data['segment_ids']) && is_array($data['segment_ids'])) {
      $this->updateSegments($newsletter, $data['segment_ids']);
      $this->entityManager->refresh($newsletter);
    }

    $this->newsletterRepository->flush();
  }

  private function updateScheduledAtOption($newsletter, $scheduledAtValue): void {
    $optionField = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => NewsletterOptionFieldEntity::NAME_SCHEDULED_AT,
      'newsletterType' => $newsletter->getType(),
    ]);

    if (!$optionField) {
      // If the option field doesn't exist for this newsletter type, skip
      return;
    }

    $option = $this->newsletterOptionsRepository->findOneBy([
      'newsletter' => $newsletter,
      'optionField' => $optionField,
    ]);

    if (!$option) {
      $option = new NewsletterOptionEntity($newsletter, $optionField);
      $this->newsletterOptionsRepository->persist($option);
      $newsletter->getOptions()->add($option);
    }

    // Set the value (null or the datetime string)
    $option->setValue($scheduledAtValue);
  }

  /**
   * @param array $segmentIds Array of segment IDs as strings
   */
  private function updateSegments($newsletter, array $segmentIds): void {
    // Remove existing segments that are not in the new list
    $existingSegments = $newsletter->getNewsletterSegments();
    foreach ($existingSegments as $newsletterSegment) {
      $segment = $newsletterSegment->getSegment();
      if (!$segment || !in_array((string)$segment->getId(), $segmentIds, true)) {
        $this->entityManager->remove($newsletterSegment);
      }
    }

    // Add new segments
    foreach ($segmentIds as $segmentId) {
      $segmentIdInt = (int)$segmentId;
      $segment = $this->entityManager->getReference(SegmentEntity::class, $segmentIdInt);
      if (!$segment) {
        continue;
      }

      // Check if the newsletter-segment relationship already exists
      $existingRelation = $this->newsletterSegmentRepository->findOneBy([
        'newsletter' => $newsletter,
        'segment' => $segment,
      ]);

      if (!$existingRelation) {
        $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
        $this->entityManager->persist($newsletterSegment);
      }
    }
    $this->entityManager->flush();
  }

  public function trashEmail(\WP_Post $wpPost) {
    $newsletter = $this->newsletterRepository->findOneBy(['wpPost' => $wpPost->ID]);
    if (!$newsletter) {
      throw new NotFoundException('Newsletter was not found');
    }
    if ($newsletter->getWpPostId() !== $wpPost->ID) {
      throw new UnexpectedValueException('Newsletter ID does not match the post ID');
    }
    $this->newsletterRepository->bulkTrash([$newsletter->getId()]);
  }

  public function getEmailDataSchema(): array {
    return Builder::object([
      'id' => Builder::integer()->nullable(),
      'subject' => Builder::string(),
      'preheader' => Builder::string(),
      'preview_url' => Builder::string(),
      'deleted_at' => Builder::string()->nullable(),
      'scheduled_at' => Builder::string()->nullable(),
      'utm_campaign' => Builder::string(),
      'segment_ids' => Builder::array(),
    ])->toArray();
  }
}
