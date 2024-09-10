<?php declare(strict_types = 1);

namespace EmailEditor\Engine;

use MailPoet\EmailEditor\Engine\EmailEditor;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  /** @var callable */
  private $postRegisterCallback;

  public function _before() {
    parent::_before();
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
    $this->postRegisterCallback = function ($postTypes) {
      $postTypes[] = [
        'name' => 'custom_email_type',
        'args' => [],
        'meta' => [],
      ];
      return $postTypes;
    };
    add_filter('mailpoet_email_editor_post_types', $this->postRegisterCallback);
    $this->emailEditor->initialize();
  }

  public function testItRegistersCustomPostTypeAddedViaHook() {
    $postTypes = get_post_types();
    $this->assertArrayHasKey('custom_email_type', $postTypes);
  }

  public function _after() {
    parent::_after();
    remove_filter('mailpoet_email_editor_post_types', $this->postRegisterCallback);
  }
}
