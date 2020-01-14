<?php

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Event\LifecycleEventArgs;
use ReflectionObject;

class TimestampListener {
  /** @var Carbon */
  private $now;

  public function __construct(WPFunctions $wp) {
    $this->now = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }

  public function prePersist(LifecycleEventArgs $eventArgs) {
    $entity = $eventArgs->getEntity();
    $entityTraits = $this->getEntityTraits($entity);

    if (in_array(CreatedAtTrait::class, $entityTraits, true) && method_exists($entity, 'setCreatedAt')) {
      $entity->setCreatedAt($this->now);
    }

    if (in_array(UpdatedAtTrait::class, $entityTraits, true) && method_exists($entity, 'setUpdatedAt')) {
      $entity->setUpdatedAt($this->now);
    }
  }

  public function preUpdate(LifecycleEventArgs $eventArgs) {
    $entity = $eventArgs->getEntity();
    $entityTraits = $this->getEntityTraits($entity);

    if (in_array(UpdatedAtTrait::class, $entityTraits, true) && method_exists($entity, 'setUpdatedAt')) {
      $entity->setUpdatedAt($this->now);
    }
  }

  private function getEntityTraits($entity) {
    $entityReflection = new ReflectionObject($entity);
    return $entityReflection->getTraitNames();
  }
}
