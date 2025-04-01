<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\AdminUserSubscription;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class AdminUserSubscriptionTest extends \MailPoetTest {
  /** @var AdminUserSubscription */
  private $adminUserSubscription;

  /** @var WPFunctions&MockObject */
  private $wpMock;

  /** @var SettingsController&MockObject */
  private $settingsMock;
  
  /** @var SubscribersRepository&MockObject */
  private $subscribersRepositoryMock;
  
  /** @var ConfirmationEmailMailer&MockObject */
  private $confirmationEmailMailerMock;
  
  /** @var LoggerFactory&MockObject */
  private $loggerFactoryMock;

  public function _before() {
    parent::_before();

    $this->wpMock = $this->getMockBuilder(WPFunctions::class)
      ->disableOriginalConstructor()
      ->getMock();
    
    $this->settingsMock = $this->getMockBuilder(SettingsController::class)
      ->disableOriginalConstructor()
      ->getMock();
      
    $this->subscribersRepositoryMock = $this->getMockBuilder(SubscribersRepository::class)
      ->disableOriginalConstructor()
      ->getMock();
      
    $this->confirmationEmailMailerMock = $this->getMockBuilder(ConfirmationEmailMailer::class)
      ->disableOriginalConstructor()
      ->getMock();
      
    $this->loggerFactoryMock = $this->getMockBuilder(LoggerFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->adminUserSubscription = new AdminUserSubscription(
      $this->wpMock,
      $this->settingsMock,
      $this->subscribersRepositoryMock,
      $this->confirmationEmailMailerMock,
      $this->loggerFactoryMock
    );
  }

  public function testItRegistersHooksOnSetupHooks() {
    $this->wpMock
      ->expects($this->exactly(2))
      ->method('addAction')
      ->withConsecutive(
        ['user_new_form', [$this->adminUserSubscription, 'displaySubscriberStatusField']],
        ['user_register', [$this->adminUserSubscription, 'maybeSendConfirmationEmail'], 30, 1]
      );
    
    $this->wpMock
      ->expects($this->exactly(1))
      ->method('addFilter')
      ->with('mailpoet_subscriber_data_before_save', [$this->adminUserSubscription, 'modifySubscriberData'], 10, 1);

    // Call setupHooks to register the hooks
    $this->adminUserSubscription->setupHooks();
  }

  public function testItDisplaysNothingForWrongContext() {
    $this->expectOutputString('');
    
    $this->adminUserSubscription->displaySubscriberStatusField('wrong-context');
  }

  public function testModifySubscriberDataWithSubscribedStatus() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Setup the POST data
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_SUBSCRIBED;

    // Initial data
    $data = ['status' => SubscriberEntity::STATUS_UNSUBSCRIBED];

    // Expected result
    $expected = [
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'source' => 'administrator',
    ];

    $result = $this->adminUserSubscription->modifySubscriberData($data);
    $this->assertEquals($expected, $result);
  }

  public function testModifySubscriberDataWithUnconfirmedStatus() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Setup the POST data
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_UNCONFIRMED;

    // Initial data
    $data = ['status' => SubscriberEntity::STATUS_UNSUBSCRIBED];

    // Expected result
    $expected = [
      'status' => SubscriberEntity::STATUS_UNCONFIRMED,
      'source' => 'administrator',
    ];

    $result = $this->adminUserSubscription->modifySubscriberData($data);
    $this->assertEquals($expected, $result);
  }

  public function testModifySubscriberDataDoesNothingWithoutPOSTData() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Clear the POST data
    unset($_POST['mailpoet_subscriber_status']);

    // Initial data
    $data = ['status' => SubscriberEntity::STATUS_UNSUBSCRIBED];

    $result = $this->adminUserSubscription->modifySubscriberData($data);
    $this->assertEquals($data, $result);
  }

  public function testModifySubscriberDataDoesNothingWithInvalidStatus() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Set invalid status
    $_POST['mailpoet_subscriber_status'] = 'invalid_status';

    // Initial data
    $data = ['status' => SubscriberEntity::STATUS_UNSUBSCRIBED];

    $result = $this->adminUserSubscription->modifySubscriberData($data);
    $this->assertEquals($data, $result);
  }
  
  public function testMaybeSendConfirmationEmail() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Setup the POST data
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_UNCONFIRMED;
    
    $userId = 123;
    $subscriber = $this->createMock(SubscriberEntity::class);
    
    $subscriber
      ->expects($this->once())
      ->method('getStatus')
      ->willReturn(SubscriberEntity::STATUS_UNCONFIRMED);
    
    $this->subscribersRepositoryMock
      ->expects($this->once())
      ->method('findOneBy')
      ->with(['wpUserId' => $userId])
      ->willReturn($subscriber);
    
    $this->confirmationEmailMailerMock
      ->expects($this->once())
      ->method('sendConfirmationEmailOnce')
      ->with($subscriber);
    
    $this->adminUserSubscription->maybeSendConfirmationEmail($userId);
  }
  
  public function testMaybeSendConfirmationEmailDoesNothingForNonUnconfirmedStatus() {
    // Set up admin page context
    global $pagenow;
    $pagenow = 'user-new.php';
    
    $this->wpMock
      ->expects($this->once())
      ->method('isAdmin')
      ->willReturn(true);
    
    // Set different status
    $_POST['mailpoet_subscriber_status'] = SubscriberEntity::STATUS_SUBSCRIBED;
    
    $this->subscribersRepositoryMock
      ->expects($this->never())
      ->method('findOneBy');
    
    $this->confirmationEmailMailerMock
      ->expects($this->never())
      ->method('sendConfirmationEmailOnce');
    
    $this->adminUserSubscription->maybeSendConfirmationEmail(123);
  }
}
