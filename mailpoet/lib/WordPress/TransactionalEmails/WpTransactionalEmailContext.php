<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\WordPress\TransactionalEmails\PersonalizationTags\WpLink;
use MailPoet\WordPress\TransactionalEmails\PersonalizationTags\WpUser;
use MailPoet\WP\Functions as WPFunctions;

class WpTransactionalEmailContext {
  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  /**
   * Build context for the password-reset email. Receives the reset key and
   * user_login from the retrieve_password_notification_email filter.
   *
   * @return array<string, mixed>
   */
  public function passwordReset(\WP_User $user, string $key): array {
    return [
      WpUser::CONTEXT_USER_ID => $user->ID,
      WpLink::CONTEXT_PASSWORD_RESET_LINK => $this->buildResetLink($key, $user->user_login),
      WpLink::CONTEXT_LOGIN_URL => $this->wp->wpLoginUrl(),
    ];
  }

  /**
   * Build context for the new-user welcome email. Generates a fresh password
   * reset key so the recipient can land on the set-password screen.
   *
   * @return array<string, mixed>
   */
  public function newUser(\WP_User $user): array {
    $key = $this->wp->getPasswordResetKey($user);
    $setPasswordLink = '';
    if (is_string($key)) {
      $setPasswordLink = $this->buildResetLink($key, $user->user_login);
    }
    return [
      WpUser::CONTEXT_USER_ID => $user->ID,
      WpLink::CONTEXT_SET_PASSWORD_LINK => $setPasswordLink,
      WpLink::CONTEXT_LOGIN_URL => $this->wp->wpLoginUrl(),
    ];
  }

  /**
   * Build context for the email-change confirmation. Hash comes from the
   * profile flow (stored in the user's _new_email meta).
   *
   * @return array<string, mixed>
   */
  public function emailChange(\WP_User $user, string $newEmail, string $hash): array {
    return [
      WpUser::CONTEXT_USER_ID => $user->ID,
      WpUser::CONTEXT_NEW_EMAIL_ADDRESS => $newEmail,
      WpLink::CONTEXT_EMAIL_CHANGE_CONFIRM_LINK => $this->buildEmailChangeConfirmLink($hash),
    ];
  }

  /**
   * Build context for the password-change notification.
   *
   * @return array<string, mixed>
   */
  public function passwordChange(\WP_User $user): array {
    return [
      WpUser::CONTEXT_USER_ID => $user->ID,
      WpLink::CONTEXT_LOGIN_URL => $this->wp->wpLoginUrl(),
    ];
  }

  private function buildResetLink(string $key, string $userLogin): string {
    $url = $this->wp->wpLoginUrl();
    $url = $this->wp->addQueryArg('action', 'rp', $url);
    $url = $this->wp->addQueryArg('key', $key, $url);
    $url = $this->wp->addQueryArg('login', rawurlencode($userLogin), $url);
    return is_string($url) ? $url : '';
  }

  private function buildEmailChangeConfirmLink(string $hash): string {
    return $this->wp->selfAdminUrl('profile.php?newuseremail=' . $hash);
  }
}
