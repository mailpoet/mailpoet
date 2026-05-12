<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class WpTransactionalEmailRenderer {
  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var Personalizer */
  private $personalizer;

  /** @var Personalization_Tags_Registry */
  private $personalizationTagsRegistry;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $container = Email_Editor_Container::container();
    $this->personalizer = $container->get(Personalizer::class);
    $this->personalizationTagsRegistry = $container->get(Personalization_Tags_Registry::class);
  }

  /**
   * Render a wp_transactional newsletter with WordPress-specific render
   * context merged in. The recipient's locale is applied for the duration
   * of the render so static labels and translated patterns reflect the
   * user's preferred language.
   *
   * @param array<string, mixed> $context
   * @return array{html: string, text: string, subject: string}
   */
  public function render(NewsletterEntity $newsletter, array $context, ?int $userIdForLocale = null): array {
    $contextFilter = function ($existing) use ($context): array {
      $merged = is_array($existing) ? $existing : [];
      return array_merge($merged, $context);
    };
    $this->wp->addFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);

    $localeSwitched = false;
    if ($userIdForLocale !== null) {
      $localeSwitched = $this->wp->switchToUserLocale($userIdForLocale);
    }

    try {
      $rendered = $this->renderer->render($newsletter);
      $rendered = is_array($rendered) ? $rendered : [];
    } finally {
      $this->wp->removeFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);
      if ($localeSwitched) {
        $this->wp->restorePreviousLocale();
      }
    }

    $html = $rendered['html'] ?? '';
    $text = $rendered['text'] ?? '';
    $subject = $newsletter->getSubject();

    $this->personalizer->set_context($context);

    $html = is_string($html) ? $this->personalizer->personalize_content($this->prepareHtmlPersonalizationTags($html)) : '';
    $text = is_string($text) ? $this->personalizer->personalize_content($this->preparePlainTextPersonalizationTags($text)) : '';
    $subject = $this->personalizer->personalize_content($this->preparePlainTextPersonalizationTags($subject));

    return [
      'html' => $html,
      'text' => $text,
      'subject' => $subject,
    ];
  }

  private function preparePlainTextPersonalizationTags(string $content): string {
    $converted = preg_replace_callback(
      '/(?<!<!--)\[((?:mailpoet|woocommerce)\/[a-zA-Z0-9\-\/]+)(\s+[^\]]+)?\](?!-->)/',
      function (array $matches): string {
        $token = '[' . $matches[1] . ']';
        if (!$this->personalizationTagsRegistry->get_by_token($token)) {
          return $matches[0];
        }

        return '<!--[' . $matches[1] . ($matches[2] ?? '') . ']-->';
      },
      $content
    );

    return is_string($converted) ? $converted : $content;
  }

  private function prepareHtmlPersonalizationTags(string $html): string {
    $converted = preg_replace_callback(
      '/(<title\b[^>]*>)(.*?)(<\/title>)/is',
      function (array $matches): string {
        return $matches[1] . $this->preparePlainTextPersonalizationTags($matches[2]) . $matches[3];
      },
      $html
    );

    return is_string($converted) ? $converted : $html;
  }
}
