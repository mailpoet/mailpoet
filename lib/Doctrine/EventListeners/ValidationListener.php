<?php

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Doctrine\Validator\ValidationException;
use MailPoetVendor\Doctrine\ORM\Event\OnFlushEventArgs;
use MailPoetVendor\Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationListener {
  /** @var ValidatorInterface */
  private $validator;

  function __construct(ValidatorInterface $validator) {
    $this->validator = $validator;
  }

  function onFlush(OnFlushEventArgs $event_args) {
    $unit_of_work = $event_args->getEntityManager()->getUnitOfWork();

    foreach ($unit_of_work->getScheduledEntityInsertions() as $entity) {
      $this->validate($entity);
    }

    foreach ($unit_of_work->getScheduledEntityUpdates() as $entity) {
      $this->validate($entity);
    }
  }

  private function validate($entity) {
    $violations = $this->validator->validate($entity);
    if ($violations->count() > 0) {
      throw new ValidationException(get_class($entity), $violations);
    }
  }
}
