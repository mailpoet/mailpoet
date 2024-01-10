<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  /** @var FeatureFlagsController */
  private $featureFlagsController;
  
  public function _before() {
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
    $this->featureFlagsController = $this->diContainer->get(FeatureFlagsController::class);
    $this->featureFlagsController->set(FeaturesController::GUTENBERG_EMAIL_EDITOR, true);
  }

  public function testItRegistersMailPoetEmailPostType() {
    $this->emailEditor->initialize();
    $this->diContainer->get(\MailPoet\EmailEditor\Engine\EmailEditor::class)->initialize();
    $postTypes = get_post_types();
    $this->assertArrayHasKey('mailpoet_email', $postTypes);
  }

  public function _after() {
    parent::_after();
    remove_filter('mailpoet_email_editor_post_types', [$this->emailEditor, 'addEmailPostType']);
    $this->truncateEntity(NewsletterEntity::class);
    $this->featureFlagsController->set(FeaturesController::GUTENBERG_EMAIL_EDITOR, false);
  }
}
