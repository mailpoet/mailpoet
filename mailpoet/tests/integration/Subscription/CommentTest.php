<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Settings\SettingsController;

class CommentTest extends \MailPoetTest {
  private Comment $comment;

  private SettingsController $settings;

  public function _before() {
    parent::_before();
    $this->comment = $this->diContainer->get(Comment::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersTheSavedLabel() {
    $this->settings->set('subscribe.on_comment.label', 'Join our list');
    verify($this->comment->extendLoggedInForm(''))->stringContainsString('Join our list');
  }

  public function testItRendersTheDefaultLabelWhenTheSavedLabelIsEmpty() {
    $this->settings->set('subscribe.on_comment.label', '');
    verify($this->comment->extendLoggedInForm(''))
      ->stringContainsString('Yes, please add me to your mailing list.');
  }

  public function testItRendersTheDefaultLabelWhenTheSavedLabelIsWhitespace() {
    $this->settings->set('subscribe.on_comment.label', '   ');
    verify($this->comment->extendLoggedInForm(''))
      ->stringContainsString('Yes, please add me to your mailing list.');
  }
}
