<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;

class WpTransactionalEmails {
  const KIND_PASSWORD_RESET = 'password_reset';
  const KIND_NEW_USER = 'new_user';
  const KIND_EMAIL_CHANGE = 'email_change';
  const KIND_PASSWORD_CHANGE = 'password_change';

  const ALL_KINDS = [
    self::KIND_PASSWORD_RESET,
    self::KIND_NEW_USER,
    self::KIND_EMAIL_CHANGE,
    self::KIND_PASSWORD_CHANGE,
  ];

  const SETTING_PREFIX = 'wp_transactional_emails';

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SettingsController $settings
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->settings = $settings;
  }

  public function isValidKind(string $kind): bool {
    return in_array($kind, self::ALL_KINDS, true);
  }

  public function findByKind(string $kind): ?NewsletterEntity {
    if (!$this->isValidKind($kind)) {
      return null;
    }
    $id = (int)$this->settings->get($this->getSettingKey($kind), 0);
    if (!$id) {
      return null;
    }
    $newsletter = $this->newslettersRepository->findOneById($id);
    if (!$newsletter || $newsletter->getType() !== NewsletterEntity::TYPE_WP_TRANSACTIONAL_EMAIL) {
      return null;
    }
    if ($newsletter->getDeletedAt() !== null) {
      return null;
    }
    return $newsletter;
  }

  public function isActive(string $kind): bool {
    $newsletter = $this->findByKind($kind);
    return $newsletter !== null && $newsletter->getStatus() === NewsletterEntity::STATUS_ACTIVE;
  }

  public function setNewsletterId(string $kind, int $newsletterId): void {
    if (!$this->isValidKind($kind)) {
      return;
    }
    $this->settings->set($this->getSettingKey($kind), $newsletterId);
  }

  public function clearNewsletterId(string $kind): void {
    if (!$this->isValidKind($kind)) {
      return;
    }
    $this->settings->set($this->getSettingKey($kind), 0);
  }

  private function getSettingKey(string $kind): string {
    return self::SETTING_PREFIX . '.' . $kind . '_id';
  }
}
