<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\AdminUserSubscription;
use MailPoet\WP\Functions as WPFunctions;

class AdminUserSubscriptionTest extends \MailPoetTest {
  /** @var AdminUserSubscription */
  private $adminUserSubscription;

  /** @var WPFunctions */
  private $wpMock;

  /** @var SettingsController */
  private $settingsMock;

  public function _before() {
    parent::_before();

    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->settingsMock = $this->createMock(SettingsController::class);

    $this->adminUserSubscription = new AdminUserSubscription(
      $this->wpMock,
      $this->settingsMock
    );
  }

  public function testItRegistersHooksOnSetupHooks() {
    $this->wpMock->expects($this->exactly(3))
      ->method('addAction')
      ->withConsecutive(
        ['user_new_form', [$this->adminUserSubscription, 'displaySubscriberStatusField']],
        ['edit_user_created_user', [$this->adminUserSubscription, 'processNewUserStatus'], 10, 1],
        ['user_register', [$this->adminUserSubscription, 'processNewUserStatus'], 20, 1]
      );

    // Call setupHooks to register the hooks
    $this->adminUserSubscription->setupHooks();
  }

  public function testItDisplaysNothingForWrongContext() {
    $this->expectOutputString('');
    
    $this->adminUserSubscription->displaySubscriberStatusField('wrong-context');
  }

  public function testProcessNewUserStatusAddsFilters() {
    // Setup the POST data
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_SUBSCRIBED;

    // Expect the filter to be added
    $this->wpMock->expects($this->exactly(1))
      ->method('addFilter')
      ->with('mailpoet_subscriber_data_before_save', $this->anything());

    $this->adminUserSubscription->processNewUserStatus(1);
  }

  public function testProcessNewUserStatusAddsAdditionalFilterForUnconfirmed() {
    // Setup the POST data
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_UNCONFIRMED;

    // Expect two filters to be added
    $this->wpMock->expects($this->exactly(2))
      ->method('addFilter')
      ->withConsecutive(
        ['mailpoet_subscriber_data_before_save', $this->anything()],
        ['mailpoet_should_send_confirmation_email', $this->anything()]
      );

    $this->adminUserSubscription->processNewUserStatus(1);
  }

  public function testProcessNewUserStatusDoesNothingWithoutPOSTData() {
    // Clear the POST data
    unset($_POST['mailpoet_subscriber_status']);

    // Expect no filters to be added
    $this->wpMock->expects($this->never())
      ->method('addFilter');

    $this->adminUserSubscription->processNewUserStatus(1);
  }

  public function testProcessNewUserStatusDoesNothingWithInvalidStatus() {
    // Set invalid status
    $_POST['mailpoet_subscriber_status'] = 'invalid_status';

    // Expect no filters to be added
    $this->wpMock->expects($this->never())
      ->method('addFilter');

    $this->adminUserSubscription->processNewUserStatus(1);
  }
} 