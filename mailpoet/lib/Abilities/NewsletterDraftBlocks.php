<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Turns the flat blocks an agent sends into the block markup of a newsletter.
 * The markup has to match what the core blocks save, otherwise the editor offers block recovery.
 *
 * @phpstan-type DraftError array{field: string, message: string}
 * @phpstan-type BlockColors array{attributes: array<string, string>, classes: string[], styles: string[], errors: string[]}
 */
class NewsletterDraftBlocks {
  public const TYPE_HEADING = 'heading';
  public const TYPE_PARAGRAPH = 'paragraph';
  public const TYPE_BUTTON = 'button';
  public const TYPE_IMAGE = 'image';
  public const TYPE_LIST = 'list';
  public const TYPE_QUOTE = 'quote';
  public const TYPE_SPACER = 'spacer';
  public const TYPE_SECTION = 'section';
  public const ALIGNMENTS = ['left', 'center', 'right'];

  private const NESTED_TYPES = [
    self::TYPE_HEADING,
    self::TYPE_PARAGRAPH,
    self::TYPE_BUTTON,
    self::TYPE_IMAGE,
    self::TYPE_LIST,
    self::TYPE_QUOTE,
    self::TYPE_SPACER,
  ];
  private const HEADING_MAX_LENGTH = 300;
  private const BUTTON_TEXT_MAX_LENGTH = 100;
  private const DEFAULT_HEADING_LEVEL = 2;
  private const SECTION_PADDING = '20px';
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
    self::TYPE_LIST => ['items'],
    self::TYPE_QUOTE => ['text'],
    self::TYPE_SPACER => ['height'],
    self::TYPE_SECTION => ['blocks'],
  ];
  private const OPTIONAL_FIELDS = [
    self::TYPE_HEADING => ['level', 'align', 'text_color', 'background_color'],
    self::TYPE_PARAGRAPH => ['align', 'text_color', 'background_color'],
    self::TYPE_BUTTON => ['align', 'text_color', 'background_color'],
    self::TYPE_IMAGE => [],
    self::TYPE_LIST => ['ordered', 'text_color', 'background_color'],
    self::TYPE_QUOTE => ['text_color', 'background_color'],
    self::TYPE_SPACER => [],
    self::TYPE_SECTION => ['text_color', 'background_color'],
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
   * The schema of the blocks field. Sections hold one level of other blocks, so the inner blocks
   * are spelled out once: the core validator has no recursive references.
   */
  public function getBlocksSchema(): array {
    $nestedBlock = $this->getBlockSchema(self::NESTED_TYPES);
    $block = $this->getBlockSchema(array_merge(self::NESTED_TYPES, [self::TYPE_SECTION]));
    $block['properties']['type']['description'] = __('heading: text (at most 300 characters), optional level, align. paragraph: text, which may use strong, em and a href, optional align. button: text (at most 100 characters), url, optional align. image: url, alt. list: items, each may use strong, em and a href, optional ordered. quote: text. spacer: height in pixels. section: blocks, one level, shown on a shared background. text_color and background_color work on all but image and spacer. Other fields are rejected.', 'mailpoet');
    $block['properties']['blocks'] = ['type' => 'array', 'minItems' => 1, 'maxItems' => 20, 'items' => $nestedBlock];
    return ['type' => 'array', 'minItems' => 1, 'maxItems' => 50, 'items' => $block];
  }

  /** @param string[] $types */
  private function getBlockSchema(array $types): array {
    $color = ['type' => 'string', 'maxLength' => 7];
    return [
      'type' => 'object',
      'required' => ['type'],
      'additionalProperties' => false,
      'properties' => [
        'type' => ['type' => 'string', 'enum' => $types],
        'text' => ['type' => 'string', 'maxLength' => 5000],
        'level' => ['type' => 'integer', 'enum' => [1, 2, 3]],
        'url' => ['type' => 'string', 'format' => 'uri'],
        'alt' => ['type' => 'string', 'maxLength' => 300],
        'align' => ['type' => 'string', 'enum' => self::ALIGNMENTS],
        'text_color' => $color,
        'background_color' => $color,
        'items' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 50, 'items' => ['type' => 'string', 'maxLength' => 1000]],
        'ordered' => ['type' => 'boolean'],
        'height' => ['type' => 'integer', 'minimum' => 4, 'maximum' => 120],
      ],
    ];
  }

  /**
   * @param array<int, array<string, mixed>> $blocks
   * @return array{markup: string, errors: DraftError[], warnings: string[]}
   */
  public function prepare(array $blocks): array {
    $errors = [];
    $warnings = [];
    $markup = $this->prepareBlocks($blocks, 'blocks', $errors, $warnings);
    return [
      'markup' => $this->wrapInGroup(implode("\n\n", $markup)),
      'errors' => $errors,
      'warnings' => $warnings,
    ];
  }

  /**
   * @param array<int, array<string, mixed>> $blocks
   * @param DraftError[] $errors
   * @param string[] $warnings
   * @return string[]
   */
  private function prepareBlocks(array $blocks, string $parentField, array &$errors, array &$warnings): array {
    $markup = [];
    foreach ($blocks as $index => $block) {
      $field = $parentField . "[$index]";
      $removed = false;
      $result = $this->prepareBlock($block, $field, $removed, $errors, $warnings);
      if ($removed) {
        $warnings[] = $this->getRemovedContentWarning($field);
      }
      if (is_array($result)) {
        $errors[] = ['field' => $field, 'message' => implode(' ', $result)];
      } else {
        $markup[] = $result;
      }
    }
    return $markup;
  }

  public function sanitizeText(string $text, bool &$removed): string {
    return $this->sanitize($text, [$this->wp, 'sanitizeTextField'], $removed);
  }

  public function getRemovedContentWarning(string $field): string {
    // translators: %s is the name of an input field, for example blocks[2].
    return sprintf(__('%s: personalization tags, shortcodes and HTML comments are not supported in drafts, so they were removed.', 'mailpoet'), $field);
  }

  /**
   * @param array<string, mixed> $block
   * @param DraftError[] $errors
   * @param string[] $warnings
   * @return string|string[] the block markup, or the errors
   */
  private function prepareBlock(array $block, string $field, bool &$removed, array &$errors, array &$warnings) {
    $type = $this->stringField($block, 'type');
    $fieldErrors = $this->validateFields($type, $block);
    if ($fieldErrors) {
      return $fieldErrors;
    }
    $colors = $this->prepareColors($block);
    if ($colors['errors']) {
      return $colors['errors'];
    }
    switch ($type) {
      case self::TYPE_HEADING:
        return $this->buildHeading($block, $colors, $removed);
      case self::TYPE_PARAGRAPH:
        return $this->buildParagraph($block, $colors, $removed);
      case self::TYPE_BUTTON:
        return $this->buildButton($block, $colors, $removed);
      case self::TYPE_LIST:
        return $this->buildList($block, $colors, $removed);
      case self::TYPE_QUOTE:
        return $this->buildQuote($block, $colors, $removed);
      case self::TYPE_SPACER:
        return $this->buildSpacer($block);
      case self::TYPE_SECTION:
        /** @var array<int, array<string, mixed>> $innerBlocks The input schema allows only objects here. */
        $innerBlocks = is_array($block['blocks']) ? $block['blocks'] : [];
        $inner = $this->prepareBlocks($innerBlocks, $field . '.blocks', $errors, $warnings);
        return $this->buildSection($inner, $colors);
      default:
        return $this->buildImage($block, $removed);
    }
  }

  /**
   * @param array<string, mixed> $block
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
   * @param array<string, mixed> $block
   * @return BlockColors
   */
  private function prepareColors(array $block): array {
    $colors = ['attributes' => [], 'classes' => [], 'styles' => [], 'errors' => []];
    $fields = [
      'text_color' => ['text', 'has-text-color', 'color'],
      'background_color' => ['background', 'has-background', 'background-color'],
    ];
    foreach ($fields as $field => [$attribute, $class, $property]) {
      if (!isset($block[$field])) {
        continue;
      }
      $color = $this->wp->sanitizeHexColor($this->stringField($block, $field));
      // The core check lets a trailing newline through.
      if ($color === '' || $color !== trim($color)) {
        // translators: %s is a field name.
        $colors['errors'][] = sprintf(__('%s must be a hex color like #1a2b3c.', 'mailpoet'), $field);
        continue;
      }
      $colors['attributes'][$attribute] = $color;
      $colors['classes'][] = $class;
      $colors['styles'][] = "$property:$color";
    }
    return $colors;
  }

  /** @param array<string, mixed> $block */
  private function stringField(array $block, string $field): string {
    return is_string($block[$field] ?? null) ? $block[$field] : '';
  }

  /** @param array<string, mixed> $block */
  private function intField(array $block, string $field, int $default): int {
    // The input schema also accepts integers written as strings.
    return is_numeric($block[$field] ?? null) ? (int)$block[$field] : $default;
  }

  /** @param array<string, mixed> $attributes */
  private function openingDelimiter(string $name, array $attributes): string {
    $json = $attributes ? ' ' . json_encode($attributes, JSON_UNESCAPED_SLASHES) : '';
    return "<!-- wp:$name$json -->";
  }

  /**
   * @param string[] $classes
   * @param string[] $styles
   */
  private function htmlAttributes(array $classes, array $styles): string {
    $html = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
    return $html . $this->styleAttribute($styles);
  }

  /** @param string[] $styles */
  private function styleAttribute(array $styles): string {
    return $styles ? ' style="' . implode(';', $styles) . '"' : '';
  }

  /**
   * @param BlockColors $colors
   * @return array<string, mixed>
   */
  private function colorAttributes(array $colors): array {
    return $colors['attributes'] ? ['style' => ['color' => $colors['attributes']]] : [];
  }

  /**
   * @param array<string, mixed> $block
   * @param BlockColors $colors
   * @return string|string[]
   */
  private function buildHeading(array $block, array $colors, bool &$removed) {
    $text = $this->sanitizeText($this->stringField($block, 'text'), $removed);
    $errors = $this->validateText($text, self::HEADING_MAX_LENGTH);
    if ($errors) {
      return $errors;
    }
    $level = $this->intField($block, 'level', self::DEFAULT_HEADING_LEVEL);
    $attributes = isset($block['align']) ? ['textAlign' => $this->stringField($block, 'align')] : [];
    if ($level !== self::DEFAULT_HEADING_LEVEL) {
      $attributes['level'] = $level;
    }
    $classes = array_merge(['wp-block-heading'], $this->alignClass($block), $colors['classes']);
    return $this->openingDelimiter('heading', array_merge($attributes, $this->colorAttributes($colors))) . "\n"
      . "<h$level" . $this->htmlAttributes($classes, $colors['styles']) . '>' . $this->wp->escHtml($text) . "</h$level>\n"
      . '<!-- /wp:heading -->';
  }

  /**
   * @param array<string, mixed> $block
   * @param BlockColors $colors
   * @return string|string[]
   */
  private function buildParagraph(array $block, array $colors, bool &$removed) {
    $text = $this->sanitizeInlineHtml($this->stringField($block, 'text'), $removed);
    $errors = $this->validateText($text, null);
    if ($errors) {
      return $errors;
    }
    $attributes = isset($block['align']) ? ['align' => $this->stringField($block, 'align')] : [];
    $classes = array_merge($this->alignClass($block), $colors['classes']);
    return $this->openingDelimiter('paragraph', array_merge($attributes, $this->colorAttributes($colors))) . "\n"
      . '<p' . $this->htmlAttributes($classes, $colors['styles']) . ">$text</p>\n"
      . '<!-- /wp:paragraph -->';
  }

  /**
   * @param array<string, mixed> $block
   * @param BlockColors $colors
   * @return string|string[]
   */
  private function buildButton(array $block, array $colors, bool &$removed) {
    $text = $this->sanitizeText($this->stringField($block, 'text'), $removed);
    $url = $this->sanitizeUrl($this->stringField($block, 'url'), $removed);
    $errors = array_merge($this->validateText($text, self::BUTTON_TEXT_MAX_LENGTH), $this->validateUrl($url));
    if ($errors) {
      return $errors;
    }
    $buttonsAttributes = isset($block['align']) ? ['layout' => ['type' => 'flex', 'justifyContent' => $this->stringField($block, 'align')]] : [];
    $classes = array_merge(['wp-block-button__link'], $colors['classes'], ['wp-element-button']);
    $styles = $this->styleAttribute($colors['styles']);
    return $this->openingDelimiter('buttons', $buttonsAttributes) . "\n"
      . '<div class="wp-block-buttons">' . $this->openingDelimiter('button', $this->colorAttributes($colors)) . "\n"
      . '<div class="wp-block-button"><a class="' . implode(' ', $classes) . '" href="' . $this->wp->escAttr($url) . '"' . $styles . '>' . $this->wp->escHtml($text) . "</a></div>\n"
      . "<!-- /wp:button --></div>\n"
      . '<!-- /wp:buttons -->';
  }

  /**
   * @param array<string, mixed> $block
   * @return string|string[]
   */
  private function buildImage(array $block, bool &$removed) {
    $url = $this->sanitizeUrl($this->stringField($block, 'url'), $removed);
    $alt = $this->sanitizeText($this->stringField($block, 'alt'), $removed);
    $errors = $this->validateUrl($url);
    if ($errors) {
      return $errors;
    }
    return $this->openingDelimiter('image', []) . "\n"
      . '<figure class="wp-block-image"><img src="' . $this->wp->escAttr($url) . '" alt="' . $this->wp->escAttr($alt) . "\"/></figure>\n"
      . '<!-- /wp:image -->';
  }

  /**
   * @param array<string, mixed> $block
   * @param BlockColors $colors
   * @return string|string[]
   */
  private function buildList(array $block, array $colors, bool &$removed) {
    $items = [];
    $blockItems = is_array($block['items']) ? $block['items'] : [];
    foreach ($blockItems as $item) {
      $text = $this->sanitizeInlineHtml(is_string($item) ? $item : '', $removed);
      $errors = $this->validateText($text, null);
      if ($errors) {
        return $errors;
      }
      $items[] = "<!-- wp:list-item -->\n<li>$text</li>\n<!-- /wp:list-item -->";
    }
    $ordered = !empty($block['ordered']);
    $tag = $ordered ? 'ol' : 'ul';
    $attributes = $ordered ? ['ordered' => true] : [];
    $classes = implode(' ', array_merge(['wp-block-list'], $colors['classes']));
    // The list block saves its style before its class.
    $style = $this->styleAttribute($colors['styles']);
    return $this->openingDelimiter('list', array_merge($attributes, $this->colorAttributes($colors))) . "\n"
      . "<$tag$style class=\"$classes\">" . implode("\n\n", $items) . "</$tag>\n"
      . '<!-- /wp:list -->';
  }

  /**
   * @param array<string, mixed> $block
   * @param BlockColors $colors
   * @return string|string[]
   */
  private function buildQuote(array $block, array $colors, bool &$removed) {
    $text = $this->sanitizeInlineHtml($this->stringField($block, 'text'), $removed);
    $errors = $this->validateText($text, null);
    if ($errors) {
      return $errors;
    }
    $classes = array_merge(['wp-block-quote'], $colors['classes']);
    return $this->openingDelimiter('quote', $this->colorAttributes($colors)) . "\n"
      . '<blockquote' . $this->htmlAttributes($classes, $colors['styles']) . "><!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph --></blockquote>\n"
      . '<!-- /wp:quote -->';
  }

  /** @param array<string, mixed> $block */
  private function buildSpacer(array $block): string {
    $height = $this->intField($block, 'height', 0) . 'px';
    return $this->openingDelimiter('spacer', ['height' => $height]) . "\n"
      . '<div style="height:' . $height . "\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n"
      . '<!-- /wp:spacer -->';
  }

  /**
   * @param string[] $inner
   * @param BlockColors $colors
   */
  private function buildSection(array $inner, array $colors): string {
    $style = $colors['attributes'] ? ['color' => $colors['attributes']] : [];
    $style['spacing'] = ['padding' => ['top' => self::SECTION_PADDING, 'right' => self::SECTION_PADDING, 'bottom' => self::SECTION_PADDING, 'left' => self::SECTION_PADDING]];
    $styles = array_merge($colors['styles'], array_map(function (string $side): string {
      return "padding-$side:" . self::SECTION_PADDING;
    }, ['top', 'right', 'bottom', 'left']));
    $classes = array_merge(['wp-block-group'], $colors['classes']);
    return $this->openingDelimiter('group', ['style' => $style, 'layout' => ['type' => 'constrained']]) . "\n"
      . '<div' . $this->htmlAttributes($classes, $styles) . '>' . implode("\n\n", $inner) . "</div>\n"
      . '<!-- /wp:group -->';
  }

  /**
   * @param array<string, mixed> $block
   * @return string[]
   */
  private function alignClass(array $block): array {
    return isset($block['align']) ? ['has-text-align-' . $this->stringField($block, 'align')] : [];
  }

  private function sanitizeInlineHtml(string $text, bool &$removed): string {
    return $this->sanitize($text, function (string $text): string {
      // Unbalanced tags would make the editor offer block recovery.
      return $this->wp->forceBalanceTags($this->wp->wpKses($text, self::PARAGRAPH_HTML, self::PARAGRAPH_LINK_PROTOCOLS));
    }, $removed);
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
    $padding = ['right' => 'var:preset|spacing|40', 'left' => 'var:preset|spacing|40'];
    return $this->openingDelimiter('group', ['style' => ['spacing' => ['padding' => $padding]], 'layout' => ['type' => 'constrained']]) . "\n"
      . '<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">'
      . $content
      . "</div>\n"
      . '<!-- /wp:group -->';
  }
}
