<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Test\DataFactories\Newsletter;

class EmailEditorPreviewEmailTest extends \MailPoetTest {
  /** @var SendPreviewController&\PHPUnit\Framework\MockObject\MockObject */
  private $sendPreviewController;

  /** @var EmailEditorPreviewEmail */
  private $previewEmail;

  public function _before() {
    parent::_before();
    $this->sendPreviewController = $this->createMock(SendPreviewController::class);
    $this->previewEmail = $this->getServiceWithOverrides(EmailEditorPreviewEmail::class, [
      'sendPreviewController' => $this->sendPreviewController,
    ]);
  }

  public function testItLeavesPostsThatAreNotEmailsToOtherHandlers(): void {
    $authorId = $this->createUserWithRole('author');
    $postId = $this->tester->createPost([
      'post_type' => 'post',
      'post_author' => $authorId,
      'post_title' => 'Not an email',
    ])->ID;
    wp_set_current_user($authorId);
    $this->sendPreviewController->expects($this->never())->method('sendPreview');

    $data = ['email' => 'test@example.com', 'postId' => $postId];

    $this->assertSame($data, $this->previewEmail->sendPreviewEmail($data));
  }

  public function testItRejectsUsersWithoutTheManageEmailsCapability(): void {
    $authorId = $this->createUserWithRole('author');
    $postId = $this->createEmailOwnedBy($authorId);
    (new Newsletter())->withWpPostId($postId)->create();
    wp_set_current_user($authorId);
    $this->sendPreviewController->expects($this->never())->method('sendPreview');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('You do not have permission to perform this action.');
    $this->previewEmail->sendPreviewEmail(['email' => 'test@example.com', 'postId' => $postId]);
  }

  public function testItSendsThePreviewForUsersWithTheManageEmailsCapability(): void {
    $editorId = $this->createUserWithRole('editor');
    (new \WP_User($editorId))->add_cap(AccessControl::PERMISSION_MANAGE_EMAILS);
    $postId = $this->createEmailOwnedBy($editorId);
    $newsletter = (new Newsletter())->withWpPostId($postId)->create();
    wp_set_current_user($editorId);
    $this->sendPreviewController->expects($this->once())
      ->method('sendPreview')
      ->with($this->callback(function (NewsletterEntity $sent) use ($newsletter) {
        return $sent->getId() === $newsletter->getId();
      }), 'test@example.com');

    $this->assertTrue($this->previewEmail->sendPreviewEmail(['email' => 'test@example.com', 'postId' => $postId]));
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    $this->truncateEntity(NewsletterEntity::class);
  }

  private function createUserWithRole(string $role): int {
    $suffix = uniqid();
    return (int)$this->tester->createWordPressUser("preview-email-{$role}-{$suffix}@localhost.test", $role);
  }

  private function createEmailOwnedBy(int $userId): int {
    return $this->tester->createPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_author' => $userId,
      'post_title' => 'Preview email',
    ])->ID;
  }
}
