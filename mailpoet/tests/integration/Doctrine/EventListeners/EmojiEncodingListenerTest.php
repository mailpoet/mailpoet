<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EventListeners\EmojiEncodingListener;
use MailPoet\Entities\FormEntity;
use MailPoet\WP\Emoji;
use MailPoetVendor\Doctrine\ORM\Events;

class EmojiEncodingListenerTest extends \MailPoetTest {
  public function testItSanitizeFormEntityOnPersistAndUpdate() {
    $form = new FormEntity('Form' );
    $form->setBody(['body']);
    $emojiMock = $this->createMock(Emoji::class);
    $emojiMock->expects($this->exactly(2))
      ->method('sanitizeEmojisInFormBody')
      ->willReturn(['sanitizedBody']);
    $emojiEncodingListenerWithMockedEmoji = new EmojiEncodingListener($emojiMock);
    $originalListener = $this->diContainer->get(EmojiEncodingListener::class);
    $this->replaceListeners($originalListener, $emojiEncodingListenerWithMockedEmoji);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    expect($form->getBody())->equals(['sanitizedBody']);
    $form->setBody(['updatedBody']);
    $this->entityManager->flush();
    expect($form->getBody())->equals(['sanitizedBody']);
    $this->replaceListeners($emojiEncodingListenerWithMockedEmoji, $originalListener);
  }

  /**
   * We have to replace event listeners since EventManager
   * is shared for all entity managers using same DB connection
   */
  private function replaceListeners($original, $replacement) {
    $this->entityManager->getEventManager()->removeEventListener(
      [Events::prePersist, Events::preUpdate],
      $original
    );

    $this->entityManager->getEventManager()->addEventListener(
      [Events::prePersist, Events::preUpdate],
      $replacement
    );
  }
}
