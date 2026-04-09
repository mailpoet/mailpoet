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

  public function setupHooks(): void {
    $this->wp->addAction( 'user_register', [ $this, 'onUserRegistered' ], 10, 1 );
    $this->wp->addAction( 'set_user_role', [ $this, 'onUserRoleChanged' ], 10, 3 );
    $this->wp->addAction( 'delete_user', [ $this, 'onUserDeleted' ], 10, 1 );
  }

  public function syncAllAdmins(): void {
    $emails = $this->getAdminEmails();
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $this->setRecipients( $emailId, $emails );
    }
  }

  public function onUserRegistered( int $userId ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user || ! in_array( 'administrator', (array) $user->roles, true ) ) {
      return;
    }
    $this->addEmailToRecipients( $user->user_email );
  }

  public function onUserRoleChanged( int $userId, string $newRole, array $oldRoles ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user ) {
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

  public function onUserDeleted( int $userId ): void {
    $user = $this->wp->getUserdata( $userId );
    if ( ! $user ) {
      return;
    }
    $this->removeEmailFromRecipients( $user->user_email );
  }

  public function addEmailToRecipients( string $email ): void {
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $recipients = $this->getRecipients( $emailId );
      if ( ! in_array( $email, $recipients, true ) ) {
        $recipients[] = $email;
        $this->setRecipients( $emailId, $recipients );
      }
    }
  }

  public function removeEmailFromRecipients( string $email ): void {
    foreach ( self::ADMIN_EMAIL_IDS as $emailId ) {
      $recipients = $this->getRecipients( $emailId );
      $updated    = array_values( array_filter( $recipients, fn( $r ) => $r !== $email ) );
      if ( count( $updated ) !== count( $recipients ) ) {
        $this->setRecipients( $emailId, $updated );
      }
    }
  }

  public function getRecipients( string $emailId ): array {
    $settings  = $this->wp->getOption( "woocommerce_{$emailId}_settings", [] );
    $recipient = $settings['recipient'] ?? '';
    if ( $recipient === '' ) {
      return [];
    }
    return array_map( 'trim', explode( ',', $recipient ) );
  }

  public function setRecipients( string $emailId, array $emails ): void {
    $settings              = $this->wp->getOption( "woocommerce_{$emailId}_settings", [] );
    $settings['recipient'] = implode( ', ', array_unique( array_filter( $emails ) ) );
    $this->wp->updateOption( "woocommerce_{$emailId}_settings", $settings );
  }

  public function getAdminEmails(): array {
    $users = $this->wp->getUsers( [ 'role' => 'administrator', 'fields' => [ 'user_email' ] ] );
    return array_column( $users, 'user_email' );
  }
}
