<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPostType;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetsController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Test\DataFactories\Newsletter;

class EmailEditorPreviewEmailTest extends \MailPoetTest {
  /** @var SendPreviewController&\PHPUnit\Framework\MockObject\MockObject */
  private $sendPreviewController;

  /** @var EmailPresetsController&\PHPUnit\Framework\MockObject\MockObject */
  private $emailPresetsController;

  /** @var EmailPresetPostType&\PHPUnit\Framework\MockObject\MockObject */
  private $emailPresetPostType;

  /** @var EmailEditorPreviewEmail */
  private $previewEmail;

  public function _before() {
    parent::_before();
    $this->sendPreviewController = $this->createMock(SendPreviewController::class);
    $this->emailPresetsController = $this->createMock(EmailPresetsController::class);
    $this->emailPresetPostType = $this->getMockBuilder(EmailPresetPostType::class)
      ->setConstructorArgs([$this->diContainer->get(\MailPoet\Features\FeaturesController::class), $this->diContainer->get(\MailPoet\WP\Functions::class)])
      ->onlyMethods(['isEnabled'])
      ->getMock();
    $this->previewEmail = $this->getServiceWithOverrides(EmailEditorPreviewEmail::class, [
      'sendPreviewController' => $this->sendPreviewController,
      'emailPresetsController' => $this->emailPresetsController,
      'emailPresetPostType' => $this->emailPresetPostType,
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

  public function testItSendsAPresetPreviewWhenTheFeatureIsEnabled(): void {
    $editorId = $this->createUserWithRole('editor');
    (new \WP_User($editorId))->add_cap(AccessControl::PERMISSION_MANAGE_EMAILS);
    $presetId = $this->createPresetOwnedBy($editorId);
    $preset = get_post($presetId);
    wp_set_current_user($editorId);
    $this->emailPresetPostType->method('isEnabled')->willReturn(true);
    $this->emailPresetsController->method('getPreset')->with($presetId)->willReturn($preset);
    $this->emailPresetsController->expects($this->once())->method('sendPreview')->with($preset, 'test@example.com');
    $this->sendPreviewController->expects($this->never())->method('sendPreview');

    $this->assertTrue($this->previewEmail->sendPreviewEmail(['email' => 'test@example.com', 'postId' => $presetId]));
  }

  public function testItLeavesPresetsToOtherHandlersWhenTheFeatureIsDisabled(): void {
    $editorId = $this->createUserWithRole('editor');
    $presetId = $this->createPresetOwnedBy($editorId);
    wp_set_current_user($editorId);
    $this->emailPresetPostType->method('isEnabled')->willReturn(false);
    $this->emailPresetsController->expects($this->never())->method('sendPreview');

    $data = ['email' => 'test@example.com', 'postId' => $presetId];
    $this->assertSame($data, $this->previewEmail->sendPreviewEmail($data));
  }

  private function createPresetOwnedBy(int $userId): int {
    return $this->tester->createPost([
      'post_type' => EmailPresetPostType::NAME,
      'post_status' => EmailPresetPostType::STATUS,
      'post_author' => $userId,
      'post_title' => 'Preset',
    ])->ID;
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
