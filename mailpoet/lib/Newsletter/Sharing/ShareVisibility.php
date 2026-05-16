<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sharing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Settings\SettingsController;

class ShareVisibility {
  public const SETTING_DEFAULT_VISIBILITY = 'sharing.default_visibility';
  public const VISIBILITY_DEFAULT = 'default';
  public const VISIBILITY_PUBLIC = 'public';
  public const VISIBILITY_PRIVATE = 'private';

  private const ALLOWED_VISIBILITIES = [
    self::VISIBILITY_DEFAULT,
    self::VISIBILITY_PUBLIC,
    self::VISIBILITY_PRIVATE,
  ];

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function canShare(NewsletterEntity $newsletter): bool {
    if (!$this->isSupported($newsletter)) {
      return false;
    }
    return $this->getEffectiveVisibility($newsletter) === self::VISIBILITY_PUBLIC;
  }

  public function isSupported(NewsletterEntity $newsletter): bool {
    return $newsletter->getType() === NewsletterEntity::TYPE_STANDARD
      && $newsletter->getStatus() === NewsletterEntity::STATUS_SENT
      && $newsletter->getDeletedAt() === null
      && (bool)$newsletter->getHash();
  }

  public function getUnavailableReason(NewsletterEntity $newsletter): string {
    if ($newsletter->getDeletedAt() !== null) {
      return __('Deleted emails cannot be shared.', 'mailpoet');
    }
    if ($newsletter->getType() !== NewsletterEntity::TYPE_STANDARD) {
      return __('Only standard emails can be shared for now.', 'mailpoet');
    }
    if ($newsletter->getStatus() !== NewsletterEntity::STATUS_SENT) {
      return __('Only sent emails can be shared.', 'mailpoet');
    }
    if (!$newsletter->getHash()) {
      return __('This email does not have a public sharing identifier yet.', 'mailpoet');
    }
    if ($this->getEffectiveVisibility($newsletter) !== self::VISIBILITY_PUBLIC) {
      return __('Sharing is turned off for this email.', 'mailpoet');
    }
    return '';
  }

  public function getEffectiveVisibility(NewsletterEntity $newsletter): string {
    $visibility = $this->getConfiguredVisibility($newsletter);
    if ($visibility === self::VISIBILITY_DEFAULT) {
      return $this->getDefaultVisibility();
    }
    return $visibility;
  }

  public function getConfiguredVisibility(NewsletterEntity $newsletter): string {
    return $this->sanitize((string)$newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY));
  }

  public function sanitize(string $visibility): string {
    return in_array($visibility, self::ALLOWED_VISIBILITIES, true)
      ? $visibility
      : self::VISIBILITY_DEFAULT;
  }

  public function getDefaultVisibility(): string {
    $visibility = (string)$this->settings->get(self::SETTING_DEFAULT_VISIBILITY, self::VISIBILITY_PUBLIC);
    return in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE], true)
      ? $visibility
      : self::VISIBILITY_PUBLIC;
  }
}
