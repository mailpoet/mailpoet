<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoetVendor\Doctrine\ORM\Events;

class EventListenersBaseTest extends \MailPoetTest {
  /**
   * Replaces event listeners. Needed to test them since EventManager
   * is shared for all entity managers using same DB connection.
   */
  protected function replaceListeners($original, $replacement) {
    $this->entityManager->getEventManager()->removeEventListener(
      [Events::prePersist, Events::preUpdate],
      $original
    );

    $this->entityManager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      $replacement
    );
  }

  protected function replaceEntityListener($replacement): void {
    $this->entityManager->getConfiguration()->getEntityListenerResolver()->clear((string)get_class($replacement));
    $this->entityManager->getConfiguration()->getEntityListenerResolver()->register($replacement);
  }
}
