<?php declare(strict_types = 1);

namespace MailPoet\API\MP\v1;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentsRepository;

class Segments {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct (
    NewsletterSegmentRepository $newsletterSegmentRepository,
    FormsRepository $formsRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
    $this->formsRepository = $formsRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function getAll(): array {
    $segments = $this->segmentsRepository->findBy(['type' => SegmentEntity::TYPE_DEFAULT], ['id' => 'asc']);
    $result = [];
    foreach ($segments as $segment) {
      $result[] = $this->buildItem($segment);
    }
    return $result;
  }

  public function addList(array $data): array {
    $this->validateSegmentName($data);

    try {
      $segment = $this->segmentsRepository->createOrUpdate($data['name'], $data['description'] ?? '');
    } catch (\Exception $e) {
      throw new APIException(
        __('The list couldn’t be created in the database', 'mailpoet'),
        APIException::FAILED_TO_SAVE_LIST
      );
    }

    return $this->buildItem($segment);
  }

  public function updateList(array $data): array {
    // firstly validation on list id
    $this->validateSegmentId((string)($data['id'] ?? ''));

    // secondly validation on list name
    $this->validateSegmentName($data);

    try {
      $segment = $this->segmentsRepository->createOrUpdate(
        $data['name'],
        $data['description'] ?? '',
        SegmentEntity::TYPE_DEFAULT,
        [],
        (int)$data['id']
      );
    } catch (\Exception $e) {
      throw new APIException(
        __('The list couldn’t be updated in the database', 'mailpoet'),
        APIException::FAILED_TO_UPDATE_LIST
      );
    }

    return $this->buildItem($segment);
  }

  public function deleteList(string $listId): bool {
    $this->validateSegmentId($listId);

    $activelyUsedNewslettersSubjects = $this->newsletterSegmentRepository->getSubjectsOfActivelyUsedEmailsForSegments([$listId]);
    if (isset($activelyUsedNewslettersSubjects[$listId])) {
      throw new APIException(
        str_replace(
          '%1$s',
          "'" . join("', '", $activelyUsedNewslettersSubjects[$listId] ) . "'",
          // translators: %1$s is a comma-seperated list of emails for which the segment is used.
          _x('List cannot be deleted because it’s used for %1$s email', 'Alert shown when trying to delete segment, which is assigned to any automatic emails.', 'mailpoet')
        ),
        APIException::LIST_USED_IN_EMAIL
      );
    }

    $activelyUsedFormNames = $this->formsRepository->getNamesOfFormsForSegments();
    if (isset($activelyUsedFormNames[$listId])) {
      throw new APIException(
        str_replace(
          '%1$s',
          "'" . join("', '", $activelyUsedFormNames[$listId] ) . "'",
          // translators: %1$s is a comma-seperated list of forms for which the segment is used.
          _nx(
            'List cannot be deleted because it’s used for %1$s form',
            'List cannot be deleted because it’s used for %1$s forms',
            count($activelyUsedFormNames[$listId]),
            'Alert shown when trying to delete segment, when it is assigned to a form.',
            'mailpoet'
          )
        ),
        APIException::LIST_USED_IN_FORM
      );
    }

    try {
      $this->segmentsRepository->bulkDelete([$listId]);
      return true;
    } catch (\Exception $e) {
      throw new APIException(
        __('The list couldn’t be deleted from the database', 'mailpoet'),
        APIException::FAILED_TO_DELETE_LIST
      );
    }
  }

  private function validateSegmentId(string $segmentId): void {
    if (empty($segmentId)) {
      throw new APIException(
        __('List id is required.', 'mailpoet'),
        APIException::LIST_ID_REQUIRED
      );
    }

    if (!$this->segmentsRepository->findOneById($segmentId)) {
      throw new APIException(
        __('The list does not exist.', 'mailpoet'),
        APIException::LIST_NOT_EXISTS
      );
    }
  }

  /**
   * Throws an exception when the segment's name is invalid
   * @return void
   */
  private function validateSegmentName(array $data): void {
    if (empty($data['name'])) {
      throw new APIException(
        __('List name is required.', 'mailpoet'),
        APIException::LIST_NAME_REQUIRED
      );
    }

    if (!$this->segmentsRepository->isNameUnique($data['name'], null)) {
      throw new APIException(
        __('This list already exists.', 'mailpoet'),
        APIException::LIST_EXISTS
      );
    }
  }

  /**
   * @param SegmentEntity $segment
   * @return array
   */
  private function buildItem(SegmentEntity $segment): array {
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => ($createdAt = $segment->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }
}
