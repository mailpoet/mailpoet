<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Core;

class EmailEditorTest extends \MailPoetTest {
  public function testItRegistersHookedCustomPostTypes() {
    $callback = function ($postTypes) {
      $postTypes[] = [
        'name' => 'custom_email_type',
        'args' => [],
      ];
      return $postTypes;
    };
    add_filter('mailpoet_email_editor_post_types', $callback);
    $emailEditor = $this->diContainer->get(EmailEditor::class);
    $emailEditor->initialize();
    $postTypes = get_post_types();
    $this->assertArrayHasKey('custom_email_type', $postTypes);
    remove_filter('mailpoet_email_editor_post_types', $callback);
  }
}
