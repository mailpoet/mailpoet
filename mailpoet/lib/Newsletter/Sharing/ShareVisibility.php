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

  public function getEffectiveVisibility(NewsletterEntity $newsletter): string {
    $visibility = $this->getConfiguredVisibility($newsletter);
    if ($visibility === self::VISIBILITY_DEFAULT) {
      return $this->getDefaultVisibility();
    }
    return $visibility;
  }

  public function getConfiguredVisibility(NewsletterEntity $newsletter): string {
    $visibility = (string)$newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY);
    return in_array($visibility, self::ALLOWED_VISIBILITIES, true)
      ? $visibility
      : self::VISIBILITY_DEFAULT;
  }

  public function getDefaultVisibility(): string {
    $visibility = (string)$this->settings->get(self::SETTING_DEFAULT_VISIBILITY, self::VISIBILITY_PRIVATE);
    return in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE], true)
      ? $visibility
      : self::VISIBILITY_PRIVATE;
  }
}
