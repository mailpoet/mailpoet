<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

/**
 * Focused coverage of {@see BulkActionController} for the newsletters
 * listing, in particular that confirmation emails (default and per-list)
 * are kept out of the generic trash/delete actions and can only be removed
 * via `newsletters/deleteConfirmationEmail`.
 */
class BulkActionControllerTest extends \MailPoetTest {
  /** @var BulkActionController */
  private $controller;

  /** @var ListingHandler */
  private $listingHandler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(BulkActionController::class);
    $this->listingHandler = $this->diContainer->get(ListingHandler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testTrashActsOnlyOnStandardNewsletterInMixedSelection(): void {
    $standard = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $confirmationEmail = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)->create();
    $standardId = (int)$standard->getId();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_TRASH,
      $this->definition([$standardId, $confirmationEmailId])
    );

    verify($result['count'])->equals(1);
    verify($result['ids'])->equals([$standardId]);
    verify($this->reload($standardId)->getDeletedAt())->notNull();
    verify($this->reload($confirmationEmailId)->getDeletedAt())->null();
  }

  public function testDeleteActsOnlyOnStandardNewsletterInMixedSelection(): void {
    $standard = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $confirmationEmail = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)->create();
    $standardId = (int)$standard->getId();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_DELETE,
      $this->definition([$standardId, $confirmationEmailId])
    );

    verify($result['count'])->equals(1);
    verify($result['ids'])->equals([$standardId]);
    verify($this->newslettersRepository->findOneById($standardId))->null();
    verify($this->newslettersRepository->findOneById($confirmationEmailId))->notNull();
  }

  public function testSelectAllTrashLeavesConfirmationEmailsUntouched(): void {
    $suffix = uniqid();
    $standard = (new NewsletterFactory())
      ->withSubject("SelectAll_{$suffix}")
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $confirmationEmail = (new NewsletterFactory())
      ->withSubject("SelectAll_{$suffix}_confirmation")
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->create();
    $standardId = (int)$standard->getId();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_TRASH,
      $this->definition([], 'all', "SelectAll_{$suffix}")
    );

    verify($result['count'])->equals(1);
    verify($result['ids'])->equals([$standardId]);
    verify($this->reload($standardId)->getDeletedAt())->notNull();
    verify($this->reload($confirmationEmailId)->getDeletedAt())->null();
  }

  public function testTrashOfOnlyConfirmationEmailReportsEmptyResult(): void {
    $confirmationEmail = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)->create();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_TRASH,
      $this->definition([$confirmationEmailId])
    );

    verify($result['count'])->equals(0);
    verify($result['ids'])->equals([]);
    verify($this->reload($confirmationEmailId)->getDeletedAt())->null();
  }

  public function testDeleteOfOnlyConfirmationEmailReportsEmptyResult(): void {
    $confirmationEmail = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)->create();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_DELETE,
      $this->definition([$confirmationEmailId])
    );

    verify($result['count'])->equals(0);
    verify($result['ids'])->equals([]);
    verify($this->newslettersRepository->findOneById($confirmationEmailId))->notNull();
  }

  public function testRestoreWorksOnATrashedConfirmationEmail(): void {
    $confirmationEmail = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->withDeleted()
      ->create();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_RESTORE,
      $this->definition([$confirmationEmailId])
    );

    verify($result['count'])->equals(1);
    verify($result['ids'])->equals([$confirmationEmailId]);
    verify($this->reload($confirmationEmailId)->getDeletedAt())->null();
  }

  private function reload(int $id): NewsletterEntity {
    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    return $newsletter;
  }

  /**
   * @param int[] $selection
   */
  private function definition(array $selection, string $group = 'all', string $search = ''): ListingDefinition {
    return $this->listingHandler->getListingDefinition([
      'offset' => 0,
      'limit' => 0,
      'sort_by' => 'id',
      'sort_order' => 'desc',
      'group' => $group,
      'search' => $search,
      'filter' => [],
      'selection' => $selection,
      'params' => [],
    ]);
  }
}
