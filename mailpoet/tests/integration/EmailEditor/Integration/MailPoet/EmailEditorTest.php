<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  /** @var FeatureFlagsController */
  private $featureFlagsController;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->featureFlagsController = $this->diContainer->get(FeatureFlagsController::class);
    $this->featureFlagsController->set(FeaturesController::GUTENBERG_EMAIL_EDITOR, true);
  }

  public function testItRegistersMailPoetEmailPostType() {
    $this->emailEditor->initialize();
    $this->diContainer->get(\MailPoet\EmailEditor\Engine\EmailEditor::class)->initialize();
    $postTypes = get_post_types();
    $this->assertArrayHasKey('mailpoet_email', $postTypes);
  }

  public function testItCreatesAssociatedNewsletterEntity() {
    $this->emailEditor->initialize();
    $newsletters = $this->newslettersRepository->findAll();
    verify(count($newsletters))->equals(0);
    $wp = $this->diContainer->get(WPFunctions::class);
    // Add email post
    $postId = $wp->wpInsertPost(['post_type' => 'mailpoet_email']);
    $newsletters = $this->newslettersRepository->findAll();
    verify(count($newsletters))->equals(1);
    verify($newsletters[0]->getWpPostId())->greaterThan(0);
    verify($newsletters[0]->getWpPostId())->equals($postId);
    // Add non-email standard post
    $wp->wpInsertPost(['post_type' => 'post']);
    $newsletters = $this->newslettersRepository->findAll();
    // Newsletters count should not change
    verify(count($newsletters))->equals(1);
  }

  public function _after() {
    parent::_after();
    remove_filter('mailpoet_email_editor_post_types', [$this->emailEditor, 'addEmailPostType']);
    remove_filter('save_post', [$this->emailEditor, 'onEmailSave']);
    $this->truncateEntity(NewsletterEntity::class);
    $this->featureFlagsController->set(FeaturesController::GUTENBERG_EMAIL_EDITOR, false);
  }
}
