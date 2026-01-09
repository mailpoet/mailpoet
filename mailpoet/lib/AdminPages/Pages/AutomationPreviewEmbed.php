<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

class AutomationPreviewEmbed extends AbstractAutomationEmbed {
  public function render(): void {
    $this->renderEmbed();
  }

  protected function setupDependencies(): void {
    $this->assetsController->setupAutomationPreviewEmbedDependencies();
  }

  protected function getTemplateName(): string {
    return 'automation/preview-embed.html';
  }

  protected function getCustomData(): array {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $templateSlug = isset($_GET['template']) ? sanitize_key(wp_unslash($_GET['template'])) : '';

    return [
      'template_slug' => $templateSlug,
    ];
  }
}
