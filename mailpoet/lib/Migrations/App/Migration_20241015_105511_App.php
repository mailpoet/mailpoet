<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailCustomizer;
use MailPoet\Util\Security;

/**
 * Fixes confirmation emails with missing hash.
 * These emails were created by plugin before we fixed [MAILPOET-6273]
 */
class Migration_20241015_105511_App extends AppMigration {
  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    $confirmationEmailTemplateId = (int)$settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, null);
    if (!$confirmationEmailTemplateId) {
      return;
    }

    $repository = $this->container->get(NewslettersRepository::class);
    $confirmationEmail = $repository->findOneById($confirmationEmailTemplateId);
    if (!$confirmationEmail instanceof NewsletterEntity || $confirmationEmail->getHash()) {
      return;
    }

    $confirmationEmail->setHash(Security::generateHash());
    $repository->flush();
  }
}
