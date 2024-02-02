<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20240202_130053_App_Test extends \MailPoetTest {
  /** @var Migration_20240202_130053_App */
  private $migration;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20240202_130053_App($this->diContainer);
  }

  public function testItMigratesIncorrectlyMarkedSentNewslettersAsSent() {
    $incorrectStandardNewsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->create();
    $incorrectPostNotification = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->create();
    $correctlySendingNewsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => null]) // Running task
      ->create();
    $correctlySentNewsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->create();
    $draftNewsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();
    $welcomeEmailNewsletter = (new NewsletterFactory())
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->create();

    $this->migration->run();

    $this->entityManager->refresh($incorrectStandardNewsletter);
    $this->entityManager->refresh($incorrectPostNotification);
    $this->entityManager->refresh($correctlySendingNewsletter);
    $this->entityManager->refresh($correctlySentNewsletter);
    $this->entityManager->refresh($draftNewsletter);
    $this->entityManager->refresh($welcomeEmailNewsletter);

    verify($incorrectStandardNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    verify($incorrectPostNotification->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    verify($correctlySendingNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    verify($correctlySentNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    verify($draftNewsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($welcomeEmailNewsletter->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);
  }
}
