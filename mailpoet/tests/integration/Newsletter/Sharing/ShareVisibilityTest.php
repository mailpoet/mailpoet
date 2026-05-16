<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sharing;

use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;

class ShareVisibilityTest extends \MailPoetTest {
  public function testItAllowsSentStandardNewslettersByDefault() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getConfiguredVisibility($newsletter))->equals(ShareVisibility::VISIBILITY_DEFAULT);
    verify($shareVisibility->getDefaultVisibility())->equals(ShareVisibility::VISIBILITY_PUBLIC);
    verify($shareVisibility->canShare($newsletter))->true();
  }

  public function testItBlocksSentStandardNewslettersWhenGlobalDefaultIsPrivate() {
    $this->diContainer->get(SettingsController::class)->set(
      ShareVisibility::SETTING_DEFAULT_VISIBILITY,
      ShareVisibility::VISIBILITY_PRIVATE
    );
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getDefaultVisibility())->equals(ShareVisibility::VISIBILITY_PRIVATE);
    verify($shareVisibility->canShare($newsletter))->false();
  }

  public function testItAllowsNewslettersExplicitlyMarkedPublic() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->canShare($newsletter))->true();
  }

  public function testItBlocksPrivateNewsletters() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->canShare($newsletter))->false();
  }

  public function testItUsesGlobalDefaultForDefaultVisibility() {
    $this->diContainer->get(SettingsController::class)->set(
      ShareVisibility::SETTING_DEFAULT_VISIBILITY,
      ShareVisibility::VISIBILITY_PUBLIC
    );
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_DEFAULT,
      ])
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->canShare($newsletter))->true();
  }

  public function testItBlocksUnsupportedNewsletterTypesAndStatuses() {
    $draft = (new Newsletter())
      ->withDraftStatus()
      ->create();
    $notificationHistory = (new Newsletter())
      ->withPostNotificationHistoryType()
      ->withSentStatus()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->canShare($draft))->false();
    verify($shareVisibility->canShare($notificationHistory))->false();
  }

  public function testItReportsNoReasonWhenShareable() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getUnavailableReason($newsletter))->equals('');
  }

  public function testItReportsReasonForPrivateNewsletter() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getUnavailableReason($newsletter))
      ->equals('Sharing is turned off for this email.');
  }

  public function testItReportsReasonForUnsentNewsletter() {
    $newsletter = (new Newsletter())
      ->withDraftStatus()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getUnavailableReason($newsletter))
      ->equals('Only sent emails can be shared.');
  }

  public function testItReportsReasonForDeletedNewsletter() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withDeleted()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getUnavailableReason($newsletter))
      ->equals('Deleted emails cannot be shared.');
  }

  public function testItReportsReasonForUnsupportedType() {
    $newsletter = (new Newsletter())
      ->withPostNotificationHistoryType()
      ->withSentStatus()
      ->create();

    $shareVisibility = $this->diContainer->get(ShareVisibility::class);

    verify($shareVisibility->getUnavailableReason($newsletter))
      ->equals('Only standard emails can be shared for now.');
  }
}
