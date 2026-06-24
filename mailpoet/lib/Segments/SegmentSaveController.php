<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments;

use MailPoet\ConflictException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\NotFoundException;
use MailPoet\Subscribers\SegmentsCountRecalculator;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\ORMException;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentsCountRecalculator */
  private $segmentsCountRecalculator;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    EntityManager $entityManager,
    SegmentsCountRecalculator $segmentsCountRecalculator
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->entityManager = $entityManager;
    $this->segmentsCountRecalculator = $segmentsCountRecalculator;
  }

  /**
   * @throws ConflictException
   * @throws NotFoundException
   * @throws ORMException
   */
  public function save(array $data = []): SegmentEntity {
    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $displayInManageSubPage = isset($data['show_in_manage_subscription_page']) ? (int)$data['show_in_manage_subscription_page'] : false;
    $confirmationEmailId = isset($data['confirmation_email_id']) ? (int)$data['confirmation_email_id'] : null;
    if ($confirmationEmailId === 0) {
      $confirmationEmailId = null;
    }
    $confirmationPageId = isset($data['confirmation_page_id']) ? (int)$data['confirmation_page_id'] : null;
    if ($confirmationPageId === 0) {
      $confirmationPageId = null;
    }
    $publicDescription = array_key_exists('public_description', $data) ? (string)$data['public_description'] : null;

    return $this->segmentsRepository->createOrUpdate($name, $description, SegmentEntity::TYPE_DEFAULT, [], $id, (bool)$displayInManageSubPage, $confirmationEmailId, $confirmationPageId, $publicDescription);
  }

  /**
   * @throws ConflictException
   */
  public function duplicate(SegmentEntity $segmentEntity): SegmentEntity {
    $duplicate = clone $segmentEntity;
    // translators: %s is the name of the segment
    $duplicate->setName(sprintf(__('Copy of %s', 'mailpoet'), $segmentEntity->getName()));

    $this->segmentsRepository->verifyNameIsUnique($duplicate->getName(), $duplicate->getId());

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($duplicate, $segmentEntity) {
      $entityManager->persist($duplicate);
      $entityManager->flush();

      $subscriberSegmentTable = $entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $conn = $this->entityManager->getConnection();
      $stmt = $conn->prepare("
        INSERT INTO $subscriberSegmentTable (segment_id, subscriber_id, status, created_at)
        SELECT :duplicateId, subscriber_id, status, NOW()
        FROM $subscriberSegmentTable
        WHERE segment_id = :segmentId
      ");
      $stmt->bindValue('duplicateId', $duplicate->getId());
      $stmt->bindValue('segmentId', $segmentEntity->getId());
      $stmt->executeQuery();
    });

    // The bulk INSERT above copies subscribed memberships from the original
    // segment. Only subscribed members gain a new counted membership, so only
    // they need their segments_count refreshed.
    $this->segmentsCountRecalculator->recalculateForSegment((int)$duplicate->getId());

    return $duplicate;
  }
}
