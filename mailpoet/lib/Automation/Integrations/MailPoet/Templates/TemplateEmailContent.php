<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class TemplateEmailContent {
  private ClassicTemplateEmails $classicTemplateEmails;

  private PatternsController $patternsController;

  private TemplatesController $templatesController;

  private NewslettersRepository $newslettersRepository;

  private WPFunctions $wp;

  public function __construct(
    ClassicTemplateEmails $classicTemplateEmails,
    PatternsController $patternsController,
    TemplatesController $templatesController,
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->classicTemplateEmails = $classicTemplateEmails;
    $this->patternsController = $patternsController;
    $this->templatesController = $templatesController;
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  /**
   * Fill a newly created automation email with the content of a template email.
   */
  public function apply(NewsletterEntity $newsletter, string $pattern): void {
    // Only block editor emails have a WP post; classic emails keep their content in the newsletter body.
    $wpPostId = $newsletter->getWpPostId();
    if ($wpPostId) {
      $this->applyBlockPattern($wpPostId, $pattern);
      return;
    }
    $this->applyClassicBody($newsletter, $pattern);
  }

  private function applyBlockPattern(int $wpPostId, string $pattern): void {
    $patternContent = $this->patternsController->getPatternContent($pattern);
    if ($patternContent === null) {
      throw new NotFoundException('Email pattern not found: ' . $pattern);
    }

    $this->wp->wpUpdatePost([
      'ID' => $wpPostId,
      'post_content' => $patternContent,
    ]);
    $this->wp->updatePostMeta($wpPostId, '_wp_page_template', $this->templatesController->getDefaultTemplateSlug());
  }

  private function applyClassicBody(NewsletterEntity $newsletter, string $pattern): void {
    $body = $this->classicTemplateEmails->getBody($pattern);
    if ($body === null) {
      throw new NotFoundException('Classic template email not found: ' . $pattern);
    }

    $newsletter->setBody($body);
    $this->newslettersRepository->flush();
  }
}
