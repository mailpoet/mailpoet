<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\DI\ContainerWrapper;

/**
 * @group woo
 */
class AdminNotificationRecipientsSyncTest extends \MailPoetTest {

  /** @var AdminNotificationRecipientsSync */
  private $service;

  /** @var int[] */
  private $createdUserIds = [];

  public function _before() {
    $this->service = ContainerWrapper::getInstance()->get(AdminNotificationRecipientsSync::class);
    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      delete_option("woocommerce_{$id}_settings");
    }
    $this->createdUserIds = [];
  }

  public function _after() {
    foreach ($this->createdUserIds as $userId) {
      wp_delete_user($userId);
    }
    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      delete_option("woocommerce_{$id}_settings");
    }
  }

  public function testGetRecipientsReturnsEmptyArrayWhenOptionNotSet(): void {
    $result = $this->service->getRecipients('new_order');
    $this->assertSame([], $result);
  }

  public function testSetAndGetRecipients(): void {
    $emails = ['alice@example.com', 'bob@example.com'];
    $this->service->setRecipients('new_order', $emails);
    $result = $this->service->getRecipients('new_order');
    $this->assertSame($emails, $result);
  }

  public function testSetRecipientsDeduplicates(): void {
    $this->service->setRecipients('new_order', ['alice@example.com', 'alice@example.com']);
    $result = $this->service->getRecipients('new_order');
    $this->assertCount(1, $result);
    $this->assertSame(['alice@example.com'], $result);
  }

  public function testAddEmailToRecipientsUpdatesAllAdminEmailIds(): void {
    $email = 'newadmin@example.com';
    $this->service->addEmailToRecipients($email);
    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertContains($email, $this->service->getRecipients($id), "Email missing from $id");
    }
  }

  public function testAddEmailToRecipientsDoesNotDuplicate(): void {
    $email = 'once@example.com';
    $this->service->addEmailToRecipients($email);
    $this->service->addEmailToRecipients($email);
    $result = $this->service->getRecipients('new_order');
    $this->assertCount(1, $result);
  }

  public function testRemoveEmailFromRecipientsUpdatesAllAdminEmailIds(): void {
    $emailToRemove = 'remove@example.com';
    $emailToKeep = 'keep@example.com';
    $this->service->addEmailToRecipients($emailToRemove);
    $this->service->addEmailToRecipients($emailToKeep);
    $this->service->removeEmailFromRecipients($emailToRemove);
    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $recipients = $this->service->getRecipients($id);
      $this->assertNotContains($emailToRemove, $recipients, "Email should be removed from $id");
      $this->assertContains($emailToKeep, $recipients, "Email should remain in $id");
    }
  }

  public function testRemoveEmailFromRecipientsIsNoopWhenEmailNotPresent(): void {
    $existingEmail = 'existing@example.com';
    $this->service->addEmailToRecipients($existingEmail);
    $this->service->removeEmailFromRecipients('nothere@example.com');
    $result = $this->service->getRecipients('new_order');
    $this->assertContains($existingEmail, $result);
  }

  public function testOnUserRegisteredAddsAdminEmail(): void {
    $userId = wp_create_user('testadmin_reg', 'password', 'testadmin_reg@example.com');
    $this->assertIsInt($userId);
    $this->createdUserIds[] = $userId;

    $user = new \WP_User($userId);
    $user->set_role('administrator');

    $this->service->onUserRegistered($userId);

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertContains('testadmin_reg@example.com', $this->service->getRecipients($id), "Email missing from $id");
    }
  }

  public function testOnUserRegisteredIgnoresNonAdminUsers(): void {
    $userId = wp_create_user('testeditor_reg', 'password', 'testeditor_reg@example.com');
    $this->assertIsInt($userId);
    $this->createdUserIds[] = $userId;

    $user = new \WP_User($userId);
    $user->set_role('editor');

    $this->service->onUserRegistered($userId);

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertNotContains('testeditor_reg@example.com', $this->service->getRecipients($id), "Non-admin email should not appear in $id");
    }
  }

  public function testOnUserRoleChangedAddsEmailWhenPromotedToAdmin(): void {
    $userId = wp_create_user('promoted_user', 'password', 'promoted@example.com');
    $this->assertIsInt($userId);
    $this->createdUserIds[] = $userId;

    $user = new \WP_User($userId);
    $user->set_role('editor');

    $this->service->onUserRoleChanged($userId, 'administrator', ['editor']);

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertContains('promoted@example.com', $this->service->getRecipients($id), "Email missing from $id after promotion");
    }
  }

  public function testOnUserRoleChangedRemovesEmailWhenDemotedFromAdmin(): void {
    $userId = wp_create_user('demoted_user', 'password', 'demoted@example.com');
    $this->assertIsInt($userId);
    $this->createdUserIds[] = $userId;

    $user = new \WP_User($userId);
    $user->set_role('administrator');

    // Pre-populate recipients
    $this->service->addEmailToRecipients('demoted@example.com');

    $this->service->onUserRoleChanged($userId, 'editor', ['administrator']);

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertNotContains('demoted@example.com', $this->service->getRecipients($id), "Email should be removed from $id after demotion");
    }
  }

  public function testOnUserDeletedRemovesEmail(): void {
    $userId = wp_create_user('deleted_user', 'password', 'deleted@example.com');
    $this->assertIsInt($userId);

    $this->service->addEmailToRecipients('deleted@example.com');

    $this->service->onUserDeleted($userId);

    // Delete the WP user now (after the hook has read the user data)
    wp_delete_user($userId);

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $this->assertNotContains('deleted@example.com', $this->service->getRecipients($id), "Deleted user email should be removed from $id");
    }
  }

  public function testSyncAllAdminsFillsRecipientsWithCurrentAdmins(): void {
    $this->service->syncAllAdmins();

    $adminEmails = $this->service->getAdminEmails();
    $this->assertNotEmpty($adminEmails, 'There should be at least one administrator in the test environment');

    foreach (['new_order', 'cancelled_order', 'failed_order'] as $id) {
      $recipients = $this->service->getRecipients($id);
      foreach ($adminEmails as $adminEmail) {
        $this->assertContains($adminEmail, $recipients, "Admin email $adminEmail should be in recipients for $id");
      }
    }
  }
}
