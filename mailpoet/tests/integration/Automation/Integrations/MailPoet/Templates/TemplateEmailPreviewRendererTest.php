<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Integrations\MailPoet\Templates\TemplateEmailPreviewRenderer;
use MailPoetTest;

class TemplateEmailPreviewRendererTest extends MailPoetTest {
  /** @var TemplateEmailPreviewRenderer */
  private $renderer;

  public function _before(): void {
    parent::_before();
    $this->renderer = $this->diContainer->get(TemplateEmailPreviewRenderer::class);
  }

  public function testItRendersPatternContentToHtml(): void {
    $html = $this->renderer->render('welcome-email-content', 'Welcome subject', 'Preheader');
    verify($html)->notNull();
    verify($html)->stringContainsString('<!DOCTYPE html>');
    verify($html)->stringContainsString('Welcome to');
  }

  public function testItWrapsContentInTheEmailTemplate(): void {
    $html = (string)$this->renderer->render('welcome-email-content', 'Welcome subject', 'Preheader');
    // The default email template adds a footer with an unsubscribe link; the bare
    // fallback template (email-general) does not.
    verify(stripos($html, 'unsubscribe') !== false)->true();
  }

  public function testItRendersPersonalizationTags(): void {
    $html = (string)$this->renderer->render('welcome-email-content', 'Welcome subject', 'Preheader');
    verify($html)->stringNotContainsString('<!--[mailpoet/');
  }

  public function testItDoesNotPersistAnyPost(): void {
    $countBefore = $this->countPreviewPosts();
    $this->renderer->render('welcome-email-content', 'Welcome subject', 'Preheader');
    $countAfter = $this->countPreviewPosts();
    verify($countAfter)->equals(0);
    verify($countAfter)->equals($countBefore);
  }

  public function testItReturnsNullForUnknownPattern(): void {
    verify($this->renderer->render('this-pattern-does-not-exist'))->null();
  }

  public function testPatternExistsReflectsAvailability(): void {
    verify($this->renderer->patternExists('welcome-email-content'))->true();
    verify($this->renderer->patternExists('this-pattern-does-not-exist'))->false();
  }

  private function countPreviewPosts(): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_name = %s",
        'mailpoet-automation-template-email-preview'
      )
    );
  }
}
