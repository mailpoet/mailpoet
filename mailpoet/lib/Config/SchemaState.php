<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use Throwable;

/**
 * Answers whether the database schema matches the running plugin code, and why not.
 *
 * The plugin files change the moment an update finishes; the database catches up on
 * the next request that runs the activator. Until then, and on every request that runs
 * while the activator holds its lock or after a migration failed, new code must not
 * touch the database. Initializer reads this state to decide whether to wire the plugin
 * at all, and SchemaNotReadyResponder reads it to explain the refusal.
 */
class SchemaState {
  public const STATUS_READY = 'ready';
  public const STATUS_UPDATING = 'updating';
  public const STATUS_FAILED = 'failed';

  private SettingsController $settings;

  private ?Throwable $failure = null;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  /**
   * Answers from the settings cache; call refresh() first to see a db_version another
   * process wrote meanwhile.
   *
   * @phpstan-impure the answer changes after refresh() or a successful activation
   */
  public function isReady(): bool {
    try {
      $dbVersion = $this->settings->get('db_version');
    } catch (Throwable $e) {
      return false;
    }
    return version_compare((string)$dbVersion, (string)Env::$version) === 0;
  }

  /**
   * Re-reads db_version from the database, bypassing both the settings cache and
   * Doctrine's identity map, so a request refused by the activation lock can notice
   * that the lock holder has finished.
   */
  public function refresh(): void {
    try {
      $this->settings->fetch('db_version');
    } catch (Throwable $e) {
      // the settings table may not exist yet; isReady() reports that as not ready
    }
  }

  public function markFailed(Throwable $failure): void {
    $this->failure = $failure;
  }

  public function getStatus(): string {
    if ($this->isReady()) {
      return self::STATUS_READY;
    }
    return $this->failure ? self::STATUS_FAILED : self::STATUS_UPDATING;
  }

  /**
   * Full detail, including the migration error. Show it to administrators only;
   * see getPublicMessage() for everyone else.
   */
  public function getMessage(): string {
    if ($this->getStatus() === self::STATUS_FAILED) {
      return sprintf(
        // translators: %s is the error reported by the failed database migration
        __('MailPoet database update failed: %s', 'mailpoet'),
        $this->failure ? $this->failure->getMessage() : ''
      );
    }
    return $this->getPublicMessage();
  }

  public function getPublicMessage(): string {
    switch ($this->getStatus()) {
      case self::STATUS_READY:
        return '';
      case self::STATUS_FAILED:
        return __('MailPoet database update failed. Please contact the site administrator.', 'mailpoet');
      default:
        return __('MailPoet version update is in progress, please refresh the page in a minute.', 'mailpoet');
    }
  }
}
