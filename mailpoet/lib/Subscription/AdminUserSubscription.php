<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class AdminUserSubscription {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    ConfirmationEmailMailer $confirmationEmailMailer,
    LoggerFactory $loggerFactory
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->subscribersRepository = $subscribersRepository;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Set up hooks for the Add New User form in WordPress admin
   */
  public function setupHooks(): void {
    // Set up hooks for the Add New User form
    $this->wp->addAction('user_new_form', [$this, 'displaySubscriberStatusField']);

    // Use the lower priority filter instead of an action - this is simpler and more reliable
    $this->wp->addFilter('mailpoet_subscriber_data_before_save', [$this, 'modifySubscriberData'], 10, 1);

    // Add action to send confirmation email after user registration is complete
    $this->wp->addAction('user_register', [$this, 'maybeSendConfirmationEmail'], 30, 1);
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
   * Modify subscriber data before save during user creation
   *
   * @param array $data The subscriber data
   * @return array Modified subscriber data
   */
  public function modifySubscriberData($data): array {
    // Only process during user creation in admin
    if (!$this->isAdminUserCreation()) {
      return $data;
    }

    // Check if our field was submitted
    if (!isset($_POST['mailpoet_subscriber_status'])) {
      return $data;
    }

    $status = sanitize_text_field($_POST['mailpoet_subscriber_status']);

    // Validate the status value
    $validStatuses = [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_UNSUBSCRIBED,
    ];

    if (!in_array($status, $validStatuses)) {
      return $data;
    }

    // Update the subscriber data
    $data['status'] = $status;
    $data['source'] = 'administrator';

    return $data;
  }

  /**
   * Send confirmation email if needed after user registration
   *
   * @param int $userId The WordPress user ID
   */
  public function maybeSendConfirmationEmail(int $userId): void {
    // Only process during user creation in admin
    if (!$this->isAdminUserCreation()) {
      return;
    }

    // Check if our field was submitted and is set to unconfirmed
    if (
        !isset($_POST['mailpoet_subscriber_status']) ||
        $_POST['mailpoet_subscriber_status'] !== SubscriberEntity::STATUS_UNCONFIRMED
    ) {
      return;
    }

    // Find the subscriber
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $userId]);

    if (!$subscriber || $subscriber->getStatus() !== SubscriberEntity::STATUS_UNCONFIRMED) {
      return;
    }

    // Send confirmation email
    try {
      $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber);
    } catch (\Exception $e) {
      // Log the error
      $logger = $this->loggerFactory->getLogger();
      $logger->error(
        __('Failed to send confirmation email for admin-created user', 'mailpoet'),
        ['user_id' => $userId, 'error' => $e->getMessage()]
      );
    }
  }

  /**
   * Check if we're in the context of admin user creation
   *
   * @return bool
   */
  private function isAdminUserCreation(): bool {
    // Check if we're in admin
    if (!$this->wp->isAdmin()) {
      return false;
    }

    // Check for the WordPress new user admin page
    global $pagenow;
    if ($pagenow !== 'user-new.php') {
      return false;
    }

    return true;
  }
}
