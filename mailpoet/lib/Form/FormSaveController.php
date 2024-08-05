<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FormSaveController {
  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function duplicate(FormEntity $formEntity): FormEntity {
    $duplicate = clone $formEntity;
    // translators: %s is name of the form which has been duplicated.
    $duplicate->setName(sprintf(__('Copy of %s', 'mailpoet'), $formEntity->getName()));

    // reset timestamps
    $now = Carbon::now()->millisecond(0);
    $duplicate->setCreatedAt($now);
    $duplicate->setUpdatedAt($now);
    $duplicate->setDeletedAt(null);

    $this->entityManager->persist($duplicate);
    $this->entityManager->flush();

    return $duplicate;
  }
}
