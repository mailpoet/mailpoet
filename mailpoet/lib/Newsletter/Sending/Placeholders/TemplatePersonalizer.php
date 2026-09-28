<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;

/**
 * Personalizes one part of a block email into a template: every personalization tag value is
 * written as a placeholder and recorded in the collector, so one template serves a whole batch
 * with per-recipient substitutions. Tag resolution itself stays with the editor Personalizer;
 * this only intercepts the values it is about to write.
 *
 * A tag that resolves to an empty value still gets a placeholder. The rendered path leaves such
 * a link untouched, but the template has to look the same for every recipient, so the empty
 * value travels in the substitution map instead.
 */
class TemplatePersonalizer {
  private Personalizer $personalizer;

  public function __construct() {
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
  }

  /**
   * @param array<string, mixed> $context
   * @param string $part One of the PlaceholderCollector::PART_* constants
   */
  public function personalize(string $content, array $context, PlaceholderCollector $collector, string $part): string {
    $this->personalizer->set_context($context);
    // The Personalizer is shared, so the interceptor is restored as soon as this part is done.
    $previousInterceptor = $this->personalizer->set_value_interceptor(
      function (string $value, string $source, string $renderingContext) use ($collector, $part): string {
        return $this->collect($value, $source, $renderingContext, $collector, $part);
      }
    );
    try {
      $content = $this->personalizer->personalize_content(
        $content,
        $part === PlaceholderCollector::PART_HTML ? Personalizer::RENDERING_CONTEXT_HTML : Personalizer::RENDERING_CONTEXT_TEXT
      );
    } finally {
      $this->personalizer->set_value_interceptor($previousInterceptor);
    }
    return $this->restoreHrefPlaceholders($content, $collector);
  }

  private function collect(string $value, string $source, string $renderingContext, PlaceholderCollector $collector, string $part): string {
    if ($part === PlaceholderCollector::PART_SUBJECT) {
      return $collector->addSubjectText($value, $source);
    }
    if ($part === PlaceholderCollector::PART_TEXT) {
      return $collector->addText($value, $source);
    }
    if ($renderingContext === Personalizer::RENDERING_CONTEXT_HREF) {
      return $collector->addHtmlUrl($value, $source);
    }
    // Values in the HTML body arrive escaped for markup, except in the <title>, which the
    // Personalizer renders as plain text.
    return $renderingContext === Personalizer::RENDERING_CONTEXT_TEXT
      ? $collector->addHtmlPlainText($value, $source)
      : $collector->addHtml($value, $source);
  }

  /**
   * The Personalizer writes hrefs through esc_url(), which strips the braces of a placeholder and,
   * when the href has no colon anywhere, prepends "http://". Put the placeholders back. One that
   * opens its href stands for the start of the URL, so its value gets the escaping the rendered
   * path applies there. A no-op for parts without links.
   */
  private function restoreHrefPlaceholders(string $content, PlaceholderCollector $collector): string {
    $prefix = preg_quote($collector->getPlaceholderPrefix(), '~');
    $content = (string)preg_replace_callback(
      '~(href=["\'])(http://)?' . $prefix . '(\d+)(?!\d)~',
      function (array $matches) use ($collector): string {
        $placeholder = $collector->makePlaceholder((int)$matches[3]);
        $collector->useFullUrlEscaping($placeholder, $matches[2] !== '');
        return $matches[1] . $placeholder;
      },
      $content
    );
    // Elsewhere only the braces are missing; (?<!\{) skips placeholders that are still intact.
    // A placeholder that also opened an href gets its own component placeholder here.
    return (string)preg_replace_callback(
      '~(?<!\{)' . $prefix . '(\d+)(?!\d)~',
      function (array $matches) use ($collector): string {
        return $collector->getUrlComponentPlaceholder($collector->makePlaceholder((int)$matches[1]));
      },
      $content
    );
  }
}
