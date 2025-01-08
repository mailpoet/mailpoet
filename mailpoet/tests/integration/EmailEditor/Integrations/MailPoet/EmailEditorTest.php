<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  public function _before() {
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
  }

  public function testItRegistersMailPoetEmailPostType() {
    $this->emailEditor->initialize();
    $this->diContainer->get(\MailPoet\EmailEditor\Engine\Email_Editor::class)->initialize();
    $postTypes = get_post_types();
    $this->assertArrayHasKey('mailpoet_email', $postTypes);
  }

  public function _after() {
    parent::_after();
    remove_filter('mailpoet_email_editor_post_types', [$this->emailEditor, 'addEmailPostType']);
    $this->truncateEntity(NewsletterEntity::class);
  }
}
