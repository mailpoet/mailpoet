<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer as GutenbergRenderer;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Renders an automation template's pre-built email pattern to HTML for previewing
 * in the template library, without persisting a newsletter or mailpoet_email post.
 *
 * The block-editor renderer resolves post content through the core/post-content
 * block, which reads the post via get_post(). To render without a database write
 * we prime the WordPress object cache with an in-memory post under an unused ID,
 * render, then remove it again. Nothing is written to the database.
 */
class TemplateEmailPreviewRenderer {
  // High, implausible-to-exist post ID range used only to back the in-memory
  // preview post. A fresh ID is drawn per render so concurrent previews can't
  // clobber each other's cache entry when a persistent object cache is in use.
  private const PREVIEW_POST_ID_MIN = 2000000000;
  private const PREVIEW_POST_ID_MAX = 2147483646;

  private PatternsController $patternsController;
  private TemplatesController $templatesController;
  private WPFunctions $wp;

  public function __construct(
    PatternsController $patternsController,
    TemplatesController $templatesController,
    WPFunctions $wp
  ) {
    $this->patternsController = $patternsController;
    $this->templatesController = $templatesController;
    $this->wp = $wp;
  }

  public function patternExists(string $patternName): bool {
    return $this->patternsController->getPatternPreviewContent($patternName) !== null;
  }

  public function render(string $patternName, string $subject = '', string $preheader = ''): ?string {
    $content = $this->patternsController->getPatternPreviewContent($patternName);
    if ($content === null) {
      return null;
    }

    $previewPostId = random_int(self::PREVIEW_POST_ID_MIN, self::PREVIEW_POST_ID_MAX);
    $post = new \WP_Post((object)[
      'ID' => $previewPostId,
      'post_author' => 0,
      'post_date' => '2024-01-01 00:00:00',
      'post_date_gmt' => '2024-01-01 00:00:00',
      'post_content' => $content,
      'post_title' => $subject,
      'post_status' => 'publish',
      'post_name' => 'mailpoet-automation-template-email-preview',
      'post_type' => 'mailpoet_email',
      'post_modified' => '2024-01-01 00:00:00',
      'post_modified_gmt' => '2024-01-01 00:00:00',
      'filter' => 'raw',
    ]);

    $contextFilter = function (array $context): array {
      return array_merge($context, [
        'integration' => 'mailpoet',
        'newsletter_id' => 0,
        'queue_id' => 0,
        'email_type' => 'automation',
        'is_real_send' => false,
        'is_preview' => true,
        'is_single_recipient' => false,
        'subscriber_count' => 0,
        'mailpoet_is_automation' => true,
      ]);
    };

    $this->wp->wpCacheSet($previewPostId, $post, 'posts');
    $this->wp->addFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);
    try {
      $rendered = $this->getRenderer()->render(
        $post,
        $subject,
        $preheader,
        (string)$this->wp->getBloginfo('language'),
        '<meta name="robots" content="noindex, nofollow" />',
        $this->templatesController->getDefaultTemplateSlug()
      );
    } finally {
      $this->wp->removeFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);
      $this->wp->wpCacheDelete($previewPostId, 'posts');
    }

    $html = is_string($rendered['html'] ?? null) ? $rendered['html'] : null;
    if ($html === null) {
      return null;
    }

    // Replace personalization tags (e.g. <!--[mailpoet/site-title]-->) with their
    // preview values; without this they render as empty placeholders.
    $personalizer = $this->getPersonalizer();
    $personalizer->set_context([
      'recipient_email' => null,
      'is_user_preview' => true,
      'newsletter_id' => 0,
      'queue_id' => null,
    ]);
    return $personalizer->personalize_content($html);
  }

  private function getRenderer(): GutenbergRenderer {
    return Email_Editor_Container::container()->get(GutenbergRenderer::class);
  }

  private function getPersonalizer(): Personalizer {
    return Email_Editor_Container::container()->get(Personalizer::class);
  }
}
