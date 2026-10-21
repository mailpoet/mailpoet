<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\WP\Functions as WPFunctions;

/**
 * Tracks WordPress users linked to an existing subscriber whose email address
 * they haven't confirmed yet.
 *
 * Stores the address the user was linked with, as a per-site user option
 * because subscribers are per site. A confirmation counts only while the user
 * still has that address.
 */
class WPUserSubscriberLink {
  private const UNCONFIRMED_OPTION = 'mailpoet_subscriber_link_unconfirmed';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function markUnconfirmed(int $wpUserId, string $email): void {
    $this->wp->updateUserOption($wpUserId, self::UNCONFIRMED_OPTION, strtolower($email));
  }

  public function isUnconfirmed(int $wpUserId): bool {
    return (bool)$this->wp->getUserOption(self::UNCONFIRMED_OPTION, $wpUserId);
  }

  public function confirm(int $wpUserId): void {
    $linkedEmail = $this->wp->getUserOption(self::UNCONFIRMED_OPTION, $wpUserId);
    if (!is_string($linkedEmail) || $linkedEmail === '') {
      return;
    }
    $wpUser = $this->wp->getUserdata($wpUserId);
    if (!$wpUser instanceof \WP_User || strtolower($wpUser->user_email) !== $linkedEmail) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }
    $this->clear($wpUserId);
  }

  /**
   * The user meta key the option is stored under on the current site.
   */
  public function getUnconfirmedMetaKey(): string {
    global $wpdb;
    return $wpdb->get_blog_prefix() . self::UNCONFIRMED_OPTION;
  }

  public function clear(int $wpUserId): void {
    $this->wp->deleteUserOption($wpUserId, self::UNCONFIRMED_OPTION);
  }

  /**
   * @param \WP_User $wpUser
   */
  public function confirmOnPasswordReset($wpUser): void {
    if (!$wpUser instanceof \WP_User) {
      return;
    }
    $this->confirm((int)$wpUser->ID); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  /**
   * @param int|string $wpUserId
   */
  public function confirmOnEmailVerified($wpUserId): void {
    $this->confirm((int)$wpUserId);
  }
}
