<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails\PersonalizationTags;

use MailPoet\WP\Functions as WPFunctions;

class WpLink {
  const CONTEXT_PASSWORD_RESET_LINK = 'wp_password_reset_link';
  const CONTEXT_LOGIN_URL = 'wp_login_url';
  const CONTEXT_SET_PASSWORD_LINK = 'wp_set_password_link';
  const CONTEXT_EMAIL_CHANGE_CONFIRM_LINK = 'wp_email_change_confirm_link';

  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  public function getPasswordResetLink(array $context, array $args = []): string {
    return $this->readContextString($context, self::CONTEXT_PASSWORD_RESET_LINK, $args);
  }

  public function getLoginUrl(array $context, array $args = []): string {
    $value = $context[self::CONTEXT_LOGIN_URL] ?? null;
    if (is_string($value) && $value !== '') {
      return $value;
    }
    return $this->wp->wpLoginUrl();
  }

  public function getSetPasswordLink(array $context, array $args = []): string {
    return $this->readContextString($context, self::CONTEXT_SET_PASSWORD_LINK, $args);
  }

  public function getEmailChangeConfirmLink(array $context, array $args = []): string {
    return $this->readContextString($context, self::CONTEXT_EMAIL_CHANGE_CONFIRM_LINK, $args);
  }

  private function readContextString(array $context, string $key, array $args): string {
    $value = $context[$key] ?? null;
    if (is_string($value) && $value !== '') {
      return $value;
    }
    return (string)($args['default'] ?? '');
  }
}
