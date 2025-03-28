<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class AdminUserSubscription {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    // Constructor no longer initializes hooks
  }

  /**
   * Set up hooks for the Add New User form in WordPress admin
   */
  public function setupHooks(): void {
    // Set up hooks for the Add New User form
    // The WordPress user_new_form action is fired with 'add-new-user' as the parameter
    $this->wp->addAction('user_new_form', [$this, 'displaySubscriberStatusField']);

    // Handle users created through the WordPress admin interface
    // user_register hook with lower priority than the default WP sync
    // to ensure we process it after the subscriber is created
    $this->wp->addAction('user_register', [$this, 'processNewUserStatus'], 20, 1);
  }

  /**
   * Display the subscriber status field on the Add New User form
   *
   * @param string $type The form context, 'add-new-user' for single site and network admin
   */
  public function displaySubscriberStatusField($type): void {
    // According to WordPress docs, the parameter is 'add-new-user' for single site and network admin
    if ($type !== 'add-new-user') {
      return;
    }

    $confirmationEnabled = (bool)$this->settings->get('signup_confirmation.enabled', false);
    $defaultStatus = $confirmationEnabled ?
      SubscriberEntity::STATUS_UNCONFIRMED :
      SubscriberEntity::STATUS_UNSUBSCRIBED;

    // Include the template file
    include __DIR__ . '/../../views/subscription/admin_user_status_field.php';
  }

  /**
   * Process the selected status for the new user
   *
   * @param int $userId The ID of the new user
   */
  public function processNewUserStatus(int $userId): void {
    // Check if our field was submitted
    if (!isset($_POST['mailpoet_subscriber_status'])) {
      return;
    }

    $status = sanitize_text_field($_POST['mailpoet_subscriber_status']);

    // Validate the status value
    $validStatuses = [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_UNSUBSCRIBED,
    ];

    if (!in_array($status, $validStatuses)) {
      return;
    }

    // Add filter to modify subscriber data before save
    $this->wp->addFilter('mailpoet_subscriber_data_before_save', function($data) use ($status) {
      $data['status'] = $status;
      $data['source'] = 'administrator';

      return $data;
    });

    // If status is unconfirmed, ensure confirmation email is sent
    if ($status === SubscriberEntity::STATUS_UNCONFIRMED) {
      $this->wp->addFilter('mailpoet_should_send_confirmation_email', function() {
        return true;
      });
    }
  }
}
