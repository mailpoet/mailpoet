<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\Newsletter\Sending\Placeholders\PlaceholderCollector;

class TemplatePersonalizer {
  public const CONTEXT_HTML = 'html';
  public const CONTEXT_TEXT = 'text';

  private const TAG_NAME_PATTERN = '[a-zA-Z0-9\-\/]+';

  private Personalization_Tags_Registry $tagsRegistry;

  /** @var array<string, mixed> */
  private array $context = [];

  public function __construct() {
    $this->tagsRegistry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
  }

  /**
   * @param array<string, mixed> $context
   */
  public function setContext(array $context): void {
    $this->context = $context;
  }

  public function personalizeContentWithPlaceholders(string $content, PlaceholderCollector $collector, string $contentContext = self::CONTEXT_HTML): string {
    $hrefPlaceholders = [];
    $contentProcessor = new HTML_Tag_Processor($content);
    while ($contentProcessor->next_token()) {
      if ($contentProcessor->get_token_type() === '#comment') {
        $modifiableText = $contentProcessor->get_modifiable_text();
        $token = $this->parseToken($modifiableText);
        $tag = $this->tagsRegistry->get_by_token($token['token']);
        if (!$tag) {
          continue;
        }

        $contentProcessor->replace_token($this->addValue($collector, (string)$tag->execute_callback($this->context, $token['arguments']), $contentContext));
      } elseif ($contentProcessor->get_token_type() === '#tag' && $contentProcessor->get_tag() === 'TITLE') {
        $modifiableText = $contentProcessor->get_modifiable_text();
        $title = $this->personalizeContentWithPlaceholders($modifiableText, $collector, $contentContext);
        $contentProcessor->set_modifiable_text($title);
      } elseif ($contentProcessor->get_token_type() === '#tag' && $contentProcessor->get_tag() === 'A' && $contentProcessor->get_attribute('data-link-href')) {
        $href = (string)$contentProcessor->get_attribute('data-link-href');
        $token = $this->parseToken($href);
        $tag = $this->tagsRegistry->get_by_token($token['token']);
        if (!$tag) {
          continue;
        }

        $resolvedHref = $this->replaceLinkHref($href, $tag->get_token(), (string)$tag->execute_callback($this->context, $token['arguments']));
        $placeholder = $collector->addHtmlUrl($resolvedHref);
        $value = $this->replaceLinkHref($href, $tag->get_token(), $placeholder);
        if ($value) {
          $contentProcessor->set_attribute('href', $value);
          $hrefPlaceholders[$this->getNormalizedHrefPlaceholder($placeholder)] = $placeholder;
          $contentProcessor->remove_attribute('data-link-href');
          $contentProcessor->remove_attribute('contenteditable');
        }
      } elseif ($contentProcessor->get_token_type() === '#tag' && $contentProcessor->get_tag() === 'A') {
        $href = $contentProcessor->get_attribute('href');
        if (!is_string($href)) {
          continue;
        }

        $decodedHref = html_entity_decode(urldecode($href), ENT_QUOTES, 'UTF-8');
        if (!preg_match('/\[' . self::TAG_NAME_PATTERN . '(?:\s+[^\]]+)?\]/', $decodedHref, $matches)) {
          continue;
        }

        $token = $this->parseToken($matches[0]);
        $tag = $this->tagsRegistry->get_by_token($token['token']);
        if (!$tag) {
          continue;
        }

        $resolvedHref = $this->replaceLinkHref($decodedHref, $tag->get_token(), (string)$tag->execute_callback($this->context, $token['arguments']));
        $placeholder = $collector->addHtmlUrl($resolvedHref);
        if ($placeholder) {
          $contentProcessor->set_attribute('href', $placeholder);
          $hrefPlaceholders[$this->getNormalizedHrefPlaceholder($placeholder)] = $placeholder;
        }
      }
    }

    $contentProcessor->flush_updates();
    $content = $contentProcessor->get_updated_html();
    return $hrefPlaceholders ? str_replace(array_keys($hrefPlaceholders), array_values($hrefPlaceholders), $content) : $content;
  }

  /**
   * @return array{token: string, arguments: array<string, string>}
   */
  private function parseToken(string $token): array {
    $result = [
      'token' => '',
      'arguments' => [],
    ];

    if (preg_match('/^\[(' . self::TAG_NAME_PATTERN . ')\s*(.*?)\]$/', trim($token), $matches)) {
      $result['token'] = "[{$matches[1]}]";
      $attributesString = $matches[2];

      if (preg_match_all('/(\w+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+(?:\s+(?!\w+=)[^\s\]]+)*))/', $attributesString, $attributeMatches, PREG_SET_ORDER)) {
        foreach ($attributeMatches as $attribute) {
          $doubleQuotedValue = $attribute[2] ?? '';
          $singleQuotedValue = $attribute[3] ?? '';
          $unquotedValue = $attribute[4] ?? '';

          if ($doubleQuotedValue !== '') {
            $result['arguments'][$attribute[1]] = $doubleQuotedValue;
          } elseif ($singleQuotedValue !== '') {
            $result['arguments'][$attribute[1]] = $singleQuotedValue;
          } else {
            $result['arguments'][$attribute[1]] = $unquotedValue;
          }
        }
      }
    }

    return $result;
  }

  private function replaceLinkHref(string $content, string $token, string $replacement): string {
    $escapedShortcode = preg_quote(substr($token, 1, strlen($token) - 2), '/');
    $pattern = '/\[' . $escapedShortcode . '(?:\s+[^\]]+)?\]/';
    return trim((string)preg_replace($pattern, $replacement, $content));
  }

  private function addValue(PlaceholderCollector $collector, string $value, string $contentContext): string {
    return $contentContext === self::CONTEXT_HTML ? $collector->addHtmlText($value) : $collector->add($value);
  }

  private function getNormalizedHrefPlaceholder(string $placeholder): string {
    return 'http://' . trim($placeholder, '{}');
  }
}
