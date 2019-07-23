<?php

namespace MailPoet\Doctrine\EventListeners;

use Carbon\Carbon;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\Event\LifecycleEventArgs;
use ReflectionObject;

class TimestampListener {
  /** @var Carbon */
  private $now;

  function __construct(WPFunctions $wp) {
    $this->now = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }

  function prePersist(LifecycleEventArgs $event_args) {
    $entity = $event_args->getEntity();
    $entity_traits = $this->getEntityTraits($entity);

    if (in_array(CreatedAtTrait::class, $entity_traits, true) && method_exists($entity, 'setCreatedAt')) {
      $entity->setCreatedAt($this->now);
    }

    if (in_array(UpdatedAtTrait::class, $entity_traits, true) && method_exists($entity, 'setUpdatedAt')) {
      $entity->setUpdatedAt($this->now);
    }
  }

  function preUpdate(LifecycleEventArgs $event_args) {
    $entity = $event_args->getEntity();
    $entity_traits = $this->getEntityTraits($entity);

    if (in_array(UpdatedAtTrait::class, $entity_traits, true) && method_exists($entity, 'setUpdatedAt')) {
      $entity->setUpdatedAt($this->now);
    }
  }

  private function getEntityTraits($entity) {
    $entity_reflection = new ReflectionObject($entity);
    return $entity_reflection->getTraitNames();
  }
}
