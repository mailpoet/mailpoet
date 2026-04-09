<?php
declare( strict_types=1 );

namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions as WPFunctions;

class AdminNotificationRecipientsSync {

  private const ADMIN_EMAIL_IDS = [
    'new_order',
    'cancelled_order',
    'failed_order',
  ];

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Register WordPress hooks for user lifecycle events.
   *
   * @return void
   */
  public function setupHooks(): void {
    $this->wp->addAction( 'user_register', [ $this, 'onUserRegistered' ], 10, 1 );
    $this->wp->addAction( 'set_user_role', [ $this, 'onUserRoleChanged' ], 10, 3 );
    $this->wp->addAction( 'delete_user', [ $this, 'onUserDeleted' ], 10, 1 );
    $this->wp->addAction( 'init', [ $this, 'maybeRunInitialSync' ], 10 );
  }

  /**
   * Run the initial admin recipients sync once after MailPoet is activated.
   *
   * Uses a persistent option as a one-time guard so the full sync only
   * executes on the first `init` after a fresh activation or reactivation.
   *
   * @return void
   */
  public function maybeRunInitialSync(): void {
    if ( $this->wp->getOption( 'mailpoet_admin_recipients_synced' ) ) {
      return;
    }
    $this->syncAllAdmins();
    $this->wp->updateOption( 'mailpoet_admin_recipients_synced', true );
  }

  /**
   * Sync all current administrator emails into WooCommerce admin notification settings.
   *
   * @return void
   */
  public function syncAllAdmins(): void {
    $emails = $this->getAdminEmails();
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $this->setRecipients( $emailId, $emails );
    }
  }

  /**
   * Add a newly registered administrator to WooCommerce admin notification recipients.
   *
   * @param int $userId The ID of the newly registered user.
   * @return void
   */
  public function onUserRegistered( int $userId ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user || ! in_array( 'administrator', (array) $user->roles, true ) ) {
      return;
    }
    if ( $user->user_email === '' ) {
      return;
    }
    $this->addEmailToRecipients( $user->user_email );
  }

  /**
   * Update WooCommerce admin notification recipients when a user's role changes.
   *
   * @param int    $userId   The ID of the user whose role changed.
   * @param string $newRole  The new role assigned to the user.
   * @param array  $oldRoles The previous roles the user held.
   * @return void
   */
  public function onUserRoleChanged( int $userId, string $newRole, array $oldRoles ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user ) {
      return;
    }
    if ( $user->user_email === '' ) {
      return;
    }
    $wasAdmin = in_array( 'administrator', $oldRoles, true );
    $isAdmin  = $newRole === 'administrator';

    if ( $isAdmin && ! $wasAdmin ) {
      $this->addEmailToRecipients( $user->user_email );
    } elseif ( ! $isAdmin && $wasAdmin ) {
      $this->removeEmailFromRecipients( $user->user_email );
    }
  }

  /**
   * Remove a deleted user's email from WooCommerce admin notification recipients.
   *
   * @param int $userId The ID of the user being deleted.
   * @return void
   */
  public function onUserDeleted( int $userId ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user ) {
      return;
    }
    if ( $user->user_email === '' ) {
      return;
    }
    $this->removeEmailFromRecipients( $user->user_email );
  }

  /**
   * Add an email address to all WooCommerce admin notification recipient lists.
   *
   * @param string $email The email address to add.
   * @return void
   */
  public function addEmailToRecipients( string $email ): void {
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $recipients = $this->getRecipients( $emailId );
      if ( ! in_array( $email, $recipients, true ) ) {
        $recipients[] = $email;
        $this->setRecipients( $emailId, $recipients );
      }
    }
  }

  /**
   * Remove an email address from all WooCommerce admin notification recipient lists.
   *
   * @param string $email The email address to remove.
   * @return void
   */
  public function removeEmailFromRecipients( string $email ): void {
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $recipients = $this->getRecipients( $emailId );
      $updated    = array_values( array_filter( $recipients, fn( $r ) => $r !== $email ) );
      if ( count( $updated ) !== count( $recipients ) ) {
        $this->setRecipients( $emailId, $updated );
      }
    }
  }

  /**
   * Get the current recipient email addresses for a WooCommerce email notification type.
   *
   * @param string $emailId The WooCommerce email identifier (e.g. 'new_order').
   * @return array<string> List of recipient email addresses.
   */
  public function getRecipients( string $emailId ): array {
    $settings = $this->wp->getOption( "woocommerce_{$emailId}_settings", [] );
    if ( ! is_array( $settings ) ) {
      return [];
    }
    $recipient = $settings['recipient'] ?? '';
    if ( $recipient === '' ) {
      return [];
    }
    return array_map( 'trim', explode( ',', $recipient ) );
  }

  /**
   * Persist a list of recipient email addresses for a WooCommerce email notification type.
   *
   * @param string        $emailId The WooCommerce email identifier (e.g. 'new_order').
   * @param array<string> $emails  List of email addresses to store as recipients.
   * @return void
   */
  public function setRecipients( string $emailId, array $emails ): void {
    $settings = $this->wp->getOption( "woocommerce_{$emailId}_settings", [] );
    if ( ! is_array( $settings ) ) {
      $settings = [];
    }
    $settings['recipient'] = implode( ', ', array_unique( array_filter( $emails ) ) );
    $this->wp->updateOption( "woocommerce_{$emailId}_settings", $settings );
  }

  /**
   * Retrieve all administrator user email addresses from WordPress.
   *
   * @return array<string> List of administrator email addresses.
   */
  public function getAdminEmails(): array {
    $users = $this->wp->getUsers( [ 'role' => 'administrator', 'fields' => [ 'user_email' ] ] );
    return array_column( $users, 'user_email' );
  }
}
