<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class WpTransactionalEmailRenderer {
  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(Renderer $renderer, WPFunctions $wp) {
    $this->renderer = $renderer;
    $this->wp = $wp;
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

    return [
      'html' => is_string($html) ? $html : '',
      'text' => is_string($text) ? $text : '',
      'subject' => $newsletter->getSubject(),
    ];
  }
}
