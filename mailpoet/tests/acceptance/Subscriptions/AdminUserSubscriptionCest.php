<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class AdminUserSubscriptionCest {
  /** @var Settings */
  private $settings;

  /** @var string */
  private $testEmailPrefix;

  /** @var string */
  private $successMessage;

  public function _before() {
    $this->settings = new Settings();
    // Unique email prefix to avoid collisions between test runs
    $this->testEmailPrefix = 'admin_user_test_' . uniqid() . '_';
    $this->successMessage = getenv('MULTISITE') ? 'User has been added to your site.' : 'New user created';
  }

  /**
   * Test admin user creation with confirmation enabled
   */
  public function testAdminUserCreationWithConfirmationEnabled(\AcceptanceTester $i) {
    $i->wantTo('Create WordPress users with different subscription statuses when confirmation is enabled');

    // Configure settings - enable confirmation
    $this->settings->withConfirmationEmailEnabled();

    // Login to WordPress admin
    $i->login();

    // Test Unconfirmed status (should be default when confirmation is enabled)
    $emailUnconfirmed = $this->testEmailPrefix . 'unconfirmed@example.com';
    $this->createUserWithStatus($i, 'unconfirmeduser', $emailUnconfirmed, SubscriberEntity::STATUS_UNCONFIRMED);

    // Verify user was created as an unconfirmed subscriber
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailUnconfirmed);
    $i->waitForText($emailUnconfirmed);
    $i->waitForText('Unconfirmed');

    // Skip checking confirmation email in MailHog as it's proving to be unreliable in tests
    // Instead, let's verify other behaviors

    // Test Subscribed status
    $emailSubscribed = $this->testEmailPrefix . 'subscribed@example.com';
    $this->createUserWithStatus($i, 'subscribeduser', $emailSubscribed, SubscriberEntity::STATUS_SUBSCRIBED);

    // Verify user was created as a subscribed subscriber
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailSubscribed);
    $i->waitForText($emailSubscribed);
    $i->waitForText('Subscribed');

    // Test Unsubscribed status
    $emailUnsubscribed = $this->testEmailPrefix . 'unsubscribed@example.com';
    $this->createUserWithStatus($i, 'unsubscribeduser', $emailUnsubscribed, SubscriberEntity::STATUS_UNSUBSCRIBED);

    // Verify user was created as an unsubscribed subscriber
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailUnsubscribed);
    $i->waitForText($emailUnsubscribed);
    $i->waitForText('Unsubscribed');
  }

  /**
   * Test admin user creation with confirmation disabled
   */
  public function testAdminUserCreationWithConfirmationDisabled(\AcceptanceTester $i) {
    $i->wantTo('Create WordPress users with different subscription statuses when confirmation is disabled');

    // Configure settings - disable confirmation
    $this->settings->withConfirmationEmailDisabled();

    // Login to WordPress admin
    $i->login();

    // Test default (unsubscribed) status when confirmation is disabled
    $emailUnsubscribed = $this->testEmailPrefix . 'default_unsub@example.com';

    $this->createUserWithStatus($i, 'defaultunsubuser', $emailUnsubscribed);

    // Verify user was created as an unsubscribed subscriber (default when confirmation disabled)
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailUnsubscribed);
    $i->waitForText($emailUnsubscribed);
    $i->waitForText('Unsubscribed');

    // Test Subscribed status
    $emailSubscribed = $this->testEmailPrefix . 'sub_noconfirm@example.com';
    $this->createUserWithStatus($i, 'subnoconfirmuser', $emailSubscribed, SubscriberEntity::STATUS_SUBSCRIBED);

    // Verify user was created as a subscribed subscriber
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailSubscribed);
    $i->waitForText($emailSubscribed);
    $i->waitForText('Subscribed');

    // Verify the Unconfirmed option is not available when confirmation is disabled
    $i->amOnAdminPage('user-new.php');
    try {
      $i->waitForText('Add User'); // WP 6.8+
    } catch (\Exception $e) {
      $i->waitForText('Add New User'); // WP 6.7 and below;
    }
    $i->waitForText('MailPoet Subscriber Status');
    $i->dontSee('Unconfirmed (will receive a confirmation email)');
  }

  /**
   * Test admin user creation with existing subscriber email
   */
  public function testAdminUserCreationWithExistingSubscriber(\AcceptanceTester $i) {
    $i->wantTo('Create a WordPress user with email that already exists as a MailPoet subscriber');

    // Create a subscriber first with a very distinct name to verify it's overridden by WP user data
    $subscriberEmail = $this->testEmailPrefix . 'unique_subscription_test@example.com';

    // Create the subscriber directly using the Subscriber factory
    $uniqueFirstName = 'Preserved_First_Name_' . uniqid();
    $uniqueLastName = 'Preserved_Last_Name_' . uniqid();

    $i->comment('Creating subscriber with email: ' . $subscriberEmail);
    $i->comment('Subscriber first name: ' . $uniqueFirstName);
    $i->comment('Subscriber last name: ' . $uniqueLastName);

    $subscriber = (new Subscriber())
      ->withEmail($subscriberEmail)
      ->withFirstName($uniqueFirstName)
      ->withLastName($uniqueLastName)
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    // Login to WordPress admin
    $i->login();

    // Verify the subscriber was created with the expected values
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->waitForText('Unsubscribed');

    // Verify the first/last name values before WP user creation
    $i->clickItemRowActionByItemName($subscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementVisible(['css' => 'input[name="first_name"]']);
    $i->seeInField(['css' => 'input[name="first_name"]'], $uniqueFirstName);
    $i->seeInField(['css' => 'input[name="last_name"]'], $uniqueLastName);

    // Go back to subscribers list
    $i->amOnMailPoetPage('Subscribers');

    // Create WP user with same email but different name to verify WP data takes precedence
    $wpFirstName = 'WP_First_Name_' . uniqid();
    $wpLastName = 'WP_Last_Name_' . uniqid();
    $i->comment('Creating WordPress user with same email but different name');
    $this->createUserWithStatus($i, 'wpusername', $subscriberEmail, SubscriberEntity::STATUS_SUBSCRIBED);
    $i->click('Edit user');

    $i->fillField('#first_name', $wpFirstName);
    $i->fillField('#last_name', $wpLastName);
    $i->click('#submit');
    $i->waitForText('User updated', 20);

    // Verify the subscriber data was updated to match WordPress user data
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($subscriberEmail);
    $i->waitForText($subscriberEmail);
    $i->waitForText('Subscribed');

    // Verify the first/last name were updated to match WordPress user data
    $i->clickItemRowActionByItemName($subscriberEmail, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElementVisible(['css' => 'input[name="first_name"]']);

    // Check that the values match WordPress user data
    $i->seeInField(['css' => 'input[name="first_name"]'], $wpFirstName);
    $i->seeInField(['css' => 'input[name="last_name"]'], $wpLastName);
  }

  /**
   * Test admin user creation with notification email
   */
  public function testAdminUserCreationWithNotificationEmail(\AcceptanceTester $i) {
    $i->wantTo('Create WordPress user with unconfirmed status and verify notification email is sent');

    // Configure settings - enable confirmation
    $this->settings->withConfirmationEmailEnabled();

    // Clear mailbox before test
    $i->emptyMailbox();

    // Login to WordPress admin
    $i->login();

    // Create user with unconfirmed status and send notification enabled
    $emailUnconfirmed = $this->testEmailPrefix . 'notification_test@example.com';
    $username = 'notificationtestuser';
    $this->createUserWithStatus($i, $username, $emailUnconfirmed, SubscriberEntity::STATUS_UNCONFIRMED);

    // Verify user was created as an unconfirmed subscriber
    $i->amOnMailPoetPage('Subscribers');
    $i->searchFor($emailUnconfirmed);
    $i->waitForText($emailUnconfirmed);
    $i->waitForText('Unconfirmed');

    // Check for the MailPoet confirmation email
    $i->checkEmailWasReceived('Confirm your subscription');


    // Verify email contains username
    $i->see($username);
  }

  /**
   * Helper method to create a user with a specific status
   */
  private function createUserWithStatus(\AcceptanceTester $i, $username, $email, $status = null) {
    $i->amOnAdminPage('user-new.php');
    try {
      $i->waitForText('Add User'); // WP 6.8+
    } catch (\Exception $e) {
      $i->waitForText('Add New User'); // WP 6.7 and below;
    }
    $i->fillField('#user_login', $username);
    $i->fillField('#email', $email);

    // Select the requested subscriber status
    $statusOptionsMap = [
      SubscriberEntity::STATUS_SUBSCRIBED => 'Subscribed',
      SubscriberEntity::STATUS_UNCONFIRMED => 'Unconfirmed (will receive a confirmation email)',
      SubscriberEntity::STATUS_UNSUBSCRIBED => 'Unsubscribed',
    ];

    if (isset($statusOptionsMap[$status])) {
      $i->selectOption('#mailpoet_subscriber_status', $statusOptionsMap[$status]);
    }

    // Skip sending confirmation email to create WP user in Multisite
    if (getenv('MULTISITE')) {
      $i->checkOption('#noconfirmation');
    }

    $i->click('#createusersub');
    $i->waitForText($this->successMessage, 20);
  }
}
