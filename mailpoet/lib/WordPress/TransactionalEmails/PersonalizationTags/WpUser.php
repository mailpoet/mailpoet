<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails\PersonalizationTags;

use MailPoet\WP\Functions as WPFunctions;

class WpUser {
  const CONTEXT_USER_ID = 'wp_user_id';
  const CONTEXT_NEW_EMAIL_ADDRESS = 'wp_new_email_address';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getDisplayName(array $context, array $args = []): string {
    $user = $this->resolveUser($context);
    return $this->escape($user ? (string)$user->get('display_name') : (string)($args['default'] ?? ''));
  }

  public function getLogin(array $context, array $args = []): string {
    $user = $this->resolveUser($context);
    return $this->escape($user ? (string)$user->get('user_login') : (string)($args['default'] ?? ''));
  }

  public function getEmail(array $context, array $args = []): string {
    $user = $this->resolveUser($context);
    return $this->escape($user ? (string)$user->get('user_email') : (string)($args['default'] ?? ''));
  }

  public function getNewEmailAddress(array $context, array $args = []): string {
    $value = $context[self::CONTEXT_NEW_EMAIL_ADDRESS] ?? null;
    return $this->escape(is_string($value) ? $value : (string)($args['default'] ?? ''));
  }

  private function resolveUser(array $context): ?\WP_User {
    $userId = (int)($context[self::CONTEXT_USER_ID] ?? 0);
    if ($userId === 0) {
      return null;
    }
    $user = $this->wp->getUserdata($userId);
    return $user instanceof \WP_User ? $user : null;
  }

  private function escape(string $value): string {
    return $this->wp->escHtml($value);
  }
}
