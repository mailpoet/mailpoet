<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Turns the flat blocks an agent sends into the block markup of a newsletter.
 * The markup has to match what the core blocks save, otherwise the editor offers block recovery.
 */
class NewsletterDraftBlocks {
  public const TYPE_HEADING = 'heading';
  public const TYPE_PARAGRAPH = 'paragraph';
  public const TYPE_BUTTON = 'button';
  public const TYPE_IMAGE = 'image';

  private const HEADING_MAX_LENGTH = 300;
  private const BUTTON_TEXT_MAX_LENGTH = 100;
  private const DEFAULT_HEADING_LEVEL = 2;
  private const URL_PROTOCOLS = ['http', 'https'];
  private const PARAGRAPH_LINK_PROTOCOLS = ['http', 'https', 'mailto'];
  private const PARAGRAPH_HTML = [
    'strong' => [],
    'em' => [],
    'a' => ['href' => true],
  ];
  // The form of a personalization tag: [namespace/slug], with optional arguments.
  private const PERSONALIZATION_TAG_PATTERN = '/\[[a-zA-Z0-9-]+\/[a-zA-Z0-9\/-]+(?:\s+[^\]]+)?\]/';
  private const REQUIRED_FIELDS = [
    self::TYPE_HEADING => ['text'],
    self::TYPE_PARAGRAPH => ['text'],
    self::TYPE_BUTTON => ['text', 'url'],
    self::TYPE_IMAGE => ['url', 'alt'],
  ];
  private const OPTIONAL_FIELDS = [
    self::TYPE_HEADING => ['level'],
    self::TYPE_PARAGRAPH => [],
    self::TYPE_BUTTON => [],
    self::TYPE_IMAGE => [],
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var Shortcodes */
  private $shortcodes;

  public function __construct(
    WPFunctions $wp,
    Shortcodes $shortcodes
  ) {
    $this->wp = $wp;
    $this->shortcodes = $shortcodes;
  }

  /**
   * @param array<int, array<string, int|string>> $blocks
   * @return array{markup: string, errors: array<int, array{field: string, message: string}>, warnings: string[]}
   */
  public function prepare(array $blocks): array {
    $markup = [];
    $errors = [];
    $warnings = [];
    foreach ($blocks as $index => $block) {
      $field = "blocks[$index]";
      $removed = false;
      $result = $this->prepareBlock($block, $removed);
      if ($removed) {
        $warnings[] = $this->getRemovedContentWarning($field);
      }
      if (is_array($result)) {
        $errors[] = ['field' => $field, 'message' => implode(' ', $result)];
      } else {
        $markup[] = $result;
      }
    }
    return [
      'markup' => $this->wrapInGroup(implode("\n\n", $markup)),
      'errors' => $errors,
      'warnings' => $warnings,
    ];
  }

  public function sanitizeText(string $text, bool &$removed): string {
    return $this->sanitize($text, [$this->wp, 'sanitizeTextField'], $removed);
  }

  public function getRemovedContentWarning(string $field): string {
    // translators: %s is the name of an input field, for example blocks[2].
    return sprintf(__('%s: personalization tags, shortcodes and HTML comments are not supported in drafts, so they were removed.', 'mailpoet'), $field);
  }

  /**
   * @param array<string, int|string> $block
   * @return string|string[] the block markup, or the errors
   */
  private function prepareBlock(array $block, bool &$removed) {
    $type = (string)$block['type'];
    $errors = $this->validateFields($type, $block);
    if ($errors) {
      return $errors;
    }
    switch ($type) {
      case self::TYPE_HEADING:
        return $this->buildHeading($block, $removed);
      case self::TYPE_PARAGRAPH:
        return $this->buildParagraph($block, $removed);
      case self::TYPE_BUTTON:
        return $this->buildButton($block, $removed);
      default:
        return $this->buildImage($block, $removed);
    }
  }

  /**
   * @param array<string, int|string> $block
   * @return string[]
   */
  private function validateFields(string $type, array $block): array {
    $errors = [];
    foreach (self::REQUIRED_FIELDS[$type] as $field) {
      if (!isset($block[$field])) {
        // translators: %1$s is a field name, %2$s is a block type.
        $errors[] = sprintf(__('%1$s is required for the %2$s block.', 'mailpoet'), $field, $type);
      }
    }
    $allowed = array_merge(['type'], self::REQUIRED_FIELDS[$type], self::OPTIONAL_FIELDS[$type]);
    foreach (array_diff(array_keys($block), $allowed) as $field) {
      // translators: %1$s is a field name, %2$s is a block type.
      $errors[] = sprintf(__('%1$s does not belong to the %2$s block.', 'mailpoet'), $field, $type);
    }
    return $errors;
  }

  /**
   * @param array<string, int|string> $block
   * @return string|string[]
   */
  private function buildHeading(array $block, bool &$removed) {
    $text = $this->sanitizeText((string)$block['text'], $removed);
    $errors = $this->validateText($text, self::HEADING_MAX_LENGTH);
    if ($errors) {
      return $errors;
    }
    $level = (int)($block['level'] ?? self::DEFAULT_HEADING_LEVEL);
    $attributes = $level === self::DEFAULT_HEADING_LEVEL ? '' : ' {"level":' . $level . '}';
    return "<!-- wp:heading$attributes -->\n"
      . "<h$level class=\"wp-block-heading\">" . $this->wp->escHtml($text) . "</h$level>\n"
      . '<!-- /wp:heading -->';
  }

  /**
   * @param array<string, int|string> $block
   * @return string|string[]
   */
  private function buildParagraph(array $block, bool &$removed) {
    $text = $this->sanitize((string)$block['text'], function (string $text): string {
      // Unbalanced tags would make the editor offer block recovery.
      return $this->wp->forceBalanceTags($this->wp->wpKses($text, self::PARAGRAPH_HTML, self::PARAGRAPH_LINK_PROTOCOLS));
    }, $removed);
    $errors = $this->validateText($text, null);
    if ($errors) {
      return $errors;
    }
    return "<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->";
  }

  /**
   * @param array<string, int|string> $block
   * @return string|string[]
   */
  private function buildButton(array $block, bool &$removed) {
    $text = $this->sanitizeText((string)$block['text'], $removed);
    $url = $this->sanitizeUrl((string)$block['url'], $removed);
    $errors = array_merge($this->validateText($text, self::BUTTON_TEXT_MAX_LENGTH), $this->validateUrl($url));
    if ($errors) {
      return $errors;
    }
    return "<!-- wp:buttons -->\n"
      . "<div class=\"wp-block-buttons\"><!-- wp:button -->\n"
      . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . $this->wp->escAttr($url) . '">' . $this->wp->escHtml($text) . "</a></div>\n"
      . "<!-- /wp:button --></div>\n"
      . '<!-- /wp:buttons -->';
  }

  /**
   * @param array<string, int|string> $block
   * @return string|string[]
   */
  private function buildImage(array $block, bool &$removed) {
    $url = $this->sanitizeUrl((string)$block['url'], $removed);
    $alt = $this->sanitizeText((string)$block['alt'], $removed);
    $errors = $this->validateUrl($url);
    if ($errors) {
      return $errors;
    }
    return "<!-- wp:image -->\n"
      . '<figure class="wp-block-image"><img src="' . $this->wp->escAttr($url) . '" alt="' . $this->wp->escAttr($alt) . "\"/></figure>\n"
      . '<!-- /wp:image -->';
  }

  /** @return string[] */
  private function validateText(string $text, ?int $maxLength): array {
    if ($text === '') {
      return [__('text is empty after removing unsupported content.', 'mailpoet')];
    }
    if ($maxLength !== null && mb_strlen($text) > $maxLength) {
      // translators: %d is a number of characters.
      return [sprintf(__('text is longer than %d characters.', 'mailpoet'), $maxLength)];
    }
    return [];
  }

  /** @return string[] */
  private function validateUrl(string $url): array {
    $parts = $this->wp->wpParseUrl($url);
    if (is_array($parts) && in_array($parts['scheme'] ?? '', self::URL_PROTOCOLS, true) && !empty($parts['host'])) {
      return [];
    }
    return [__('url must be a full http or https address.', 'mailpoet')];
  }

  private function sanitizeUrl(string $url, bool &$removed): string {
    return $this->sanitize($url, function (string $url): string {
      return $this->wp->escUrlRaw($url, self::URL_PROTOCOLS);
    }, $removed);
  }

  /**
   * Repeats until nothing changes, because removing one piece can form another.
   */
  private function sanitize(string $text, callable $filter, bool &$removed): string {
    do {
      $before = $text;
      $clean = $this->removeTags($this->removeComments($text));
      if ($clean !== $text) {
        $removed = true;
      }
      $text = (string)$filter($clean);
    } while ($text !== $before);
    return trim($text);
  }

  /**
   * Block delimiters and personalization tags are HTML comments, and wp_kses keeps comments.
   * The regular expression matches from "<!--" to the nearest "-->", or to the end of an
   * unterminated comment.
   */
  private function removeComments(string $text): string {
    do {
      $before = $text;
      $text = (string)preg_replace('/<!--.*?(?:-->|\z)/s', '', $text);
    } while ($text !== $before);
    return $text;
  }

  /**
   * Sending replaces shortcodes in the rendered email, and rendering decodes entities. In links,
   * the email editor replaces personalization tags after decoding entities and URL encoding.
   * So the decoded forms count too, but they are used only when they contain a tag, because
   * decoding changes the text around it.
   */
  private function removeTags(string $text): string {
    $candidates = [
      $text,
      html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
      html_entity_decode(urldecode($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    ];
    foreach ($candidates as $candidate) {
      $tags = $this->findTags($candidate);
      if ($tags) {
        return $this->removeTags(str_replace($tags, '', $candidate));
      }
    }
    return $text;
  }

  /** @return string[] */
  private function findTags(string $text): array {
    preg_match_all(self::PERSONALIZATION_TAG_PATTERN, $text, $matches);
    return array_merge($this->shortcodes->extract($text) ?: [], $matches[0]);
  }

  private function wrapInGroup(string $content): string {
    return '<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->' . "\n"
      . '<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">'
      . $content
      . "</div>\n"
      . '<!-- /wp:group -->';
  }
}
