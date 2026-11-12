<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Reads the block markup of a newsletter back into the flat blocks agents work with.
 *
 * An update replaces all the blocks, so a block this format can't hold would be lost. The blocks
 * are therefore returned only when building them again gives the same content, and a draft with
 * anything more, such as a pattern, a personalization tag or extra styling, gives null.
 */
class NewsletterDraftReader {
  /** @var NewsletterDraftBlocks */
  private $draftBlocks;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDraftBlocks $draftBlocks,
    WPFunctions $wp
  ) {
    $this->draftBlocks = $draftBlocks;
    $this->wp = $wp;
  }

  /** @return array<int, array<string, mixed>>|null */
  public function read(string $content): ?array {
    $parsed = $this->parse($content);
    if ($this->hasContentOutsideBlocks($parsed)) {
      return null;
    }
    $parsed = $this->removeNamelessBlocks($parsed);
    if (count($parsed) !== 1 || $parsed[0]['blockName'] !== 'core/group') {
      return null;
    }
    $blocks = $this->toFlatBlocks($this->getInnerBlocks($parsed[0]));
    if ($blocks === null) {
      return null;
    }
    $rebuilt = $this->draftBlocks->prepare($blocks);
    if ($rebuilt['errors'] || $rebuilt['warnings']) {
      return null;
    }
    // The blocks can be built but not sent back, for example a level 4 heading a person added.
    if ($this->wp->restValidateValueFromSchema($blocks, $this->draftBlocks->getBlocksSchema(), 'blocks') !== true) {
      return null;
    }
    return $this->comparable($this->removeNamelessBlocks($this->parse($rebuilt['markup']))) === $this->comparable($parsed) ? $blocks : null;
  }

  /** @return array<int, array<string, mixed>> */
  private function parse(string $content): array {
    /** @var array<int, array<string, mixed>> $blocks parse_blocks() always gives arrays. */
    $blocks = $this->wp->parseBlocks($content);
    return $blocks;
  }

  /**
   * Nameless blocks are the HTML between blocks: usually whitespace, but it can be real content.
   *
   * @param array<int, array<string, mixed>> $blocks
   */
  private function hasContentOutsideBlocks(array $blocks): bool {
    foreach ($blocks as $block) {
      if (empty($block['blockName']) && trim($this->getHtml($block)) !== '') {
        return true;
      }
    }
    return false;
  }

  /**
   * @param array<int, array<string, mixed>> $blocks
   * @return array<int, array<string, mixed>>
   */
  private function removeNamelessBlocks(array $blocks): array {
    return array_values(array_filter($blocks, function (array $block): bool {
      return !empty($block['blockName']);
    }));
  }

  /**
   * @param array<string, mixed> $block
   * @return array<int, array<string, mixed>>
   */
  private function getInnerBlocks(array $block): array {
    /** @var array<int, array<string, mixed>> $innerBlocks parse_blocks() always gives arrays here. */
    $innerBlocks = is_array($block['innerBlocks'] ?? null) ? $block['innerBlocks'] : [];
    return $innerBlocks;
  }

  /**
   * What decides whether two blocks are the same. Editors save block attributes in another order,
   * and the HTML is compared as tags and text, not as it's written: editors don't escape quotes
   * or ">" in text, kses adds a space before "/>", and since WordPress 7.2 kses writes the
   * attributes in another order and leaves out the "/" of tags like <img>. Alignment has several
   * spellings in the attributes across WordPress versions, while its class stays in the HTML.
   *
   * @param array<int, array<string, mixed>> $blocks
   * @return array<int, array<string, mixed>>
   */
  private function comparable(array $blocks): array {
    return array_map(function (array $block): array {
      $attributes = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
      unset($attributes['textAlign'], $attributes['align']);
      $style = is_array($attributes['style'] ?? null) ? $attributes['style'] : [];
      $typography = is_array($style['typography'] ?? null) ? $style['typography'] : [];
      unset($typography['textAlign'], $style['typography'], $attributes['style']);
      if ($typography) {
        $style['typography'] = $typography;
      }
      if ($style) {
        $attributes['style'] = $style;
      }
      return [
        'name' => $block['blockName'] ?? null,
        'attributes' => $this->sortKeys($attributes),
        // Collapses runs of whitespace, which the blocks don't depend on.
        'html' => $this->getHtmlTokens(trim((string)preg_replace('/\s+/', ' ', $this->getHtml($block)))),
        'blocks' => $this->comparable($this->getInnerBlocks($block)),
      ];
    }, $blocks);
  }

  /** @return array<int, mixed> */
  private function getHtmlTokens(string $html): array {
    $processor = new \WP_HTML_Tag_Processor($html);
    $tokens = [];
    while ($processor->next_token()) {
      $attributes = [];
      foreach ($processor->get_attribute_names_with_prefix('') ?? [] as $name) {
        $attributes[$name] = $processor->get_attribute($name);
      }
      ksort($attributes);
      $tokens[] = [$processor->get_token_type(), $processor->get_tag(), $processor->is_tag_closer(), $attributes, $processor->get_modifiable_text()];
    }
    // Unfinished HTML can't be read this way, so it's compared as it is.
    return $processor->paused_at_incomplete_token() ? [$html] : $tokens;
  }

  /**
   * @param array<mixed> $values
   * @return array<mixed>
   */
  private function sortKeys(array $values): array {
    ksort($values);
    return array_map(function ($value) {
      return is_array($value) ? $this->sortKeys($value) : $value;
    }, $values);
  }

  /**
   * All or nothing: one block the format can't hold makes the whole list unreadable.
   *
   * @param array<int, array<string, mixed>> $blocks
   * @return array<int, array<string, mixed>>|null
   */
  private function toFlatBlocks(array $blocks): ?array {
    $flatBlocks = [];
    foreach ($blocks as $block) {
      $flat = $this->toFlatBlock($block);
      if ($flat === null) {
        return null;
      }
      $flatBlocks[] = $flat;
    }
    return $flatBlocks;
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function toFlatBlock(array $block): ?array {
    switch ($block['blockName'] ?? '') {
      case 'core/heading':
        return $this->readHeading($block);
      case 'core/paragraph':
        return $this->readParagraph($block);
      case 'core/image':
        return $this->readImage($block);
      case 'core/buttons':
        return $this->readButton($block);
      case 'core/list':
        return $this->readList($block);
      case 'core/quote':
        return $this->readQuote($block);
      case 'core/spacer':
        return $this->readSpacer($block);
      case 'core/group':
        return $this->readSection($block);
      default:
        return null;
    }
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>
   */
  private function readHeading(array $block): array {
    $flat = ['type' => NewsletterDraftBlocks::TYPE_HEADING, 'text' => $this->decode($this->getContent($this->getHtml($block)))];
    $level = $this->getAttribute($block, ['level']);
    if (is_int($level)) {
      $flat['level'] = $level;
    }
    return array_merge($flat, $this->readAlignment($block), $this->readColors($block));
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>
   */
  private function readParagraph(array $block): array {
    return array_merge(
      ['type' => NewsletterDraftBlocks::TYPE_PARAGRAPH, 'text' => $this->getContent($this->getHtml($block))],
      $this->readAlignment($block),
      $this->readColors($block)
    );
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function readImage(array $block): ?array {
    $processor = new \WP_HTML_Tag_Processor($this->getHtml($block));
    if (!$processor->next_tag('img')) {
      return null;
    }
    $url = $processor->get_attribute('src');
    $alt = $processor->get_attribute('alt');
    return ['type' => NewsletterDraftBlocks::TYPE_IMAGE, 'url' => is_string($url) ? $url : '', 'alt' => is_string($alt) ? $alt : ''];
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function readButton(array $block): ?array {
    $buttons = $this->getInnerBlocks($block);
    if (count($buttons) !== 1 || ($buttons[0]['blockName'] ?? '') !== 'core/button') {
      return null;
    }
    $html = trim($this->getHtml($buttons[0]));
    $processor = new \WP_HTML_Tag_Processor($html);
    if (!$processor->next_tag('a')) {
      return null;
    }
    $url = $processor->get_attribute('href');
    $linkStart = (int)strpos($html, '<a');
    $flat = [
      'type' => NewsletterDraftBlocks::TYPE_BUTTON,
      'text' => $this->decode($this->getContent(substr($html, $linkStart, (int)strrpos($html, '</div>') - $linkStart))),
      'url' => is_string($url) ? $url : '',
    ];
    $align = $this->getAttribute($block, ['layout', 'justifyContent']);
    if (is_string($align)) {
      $flat['align'] = $align;
    }
    return array_merge($flat, $this->readColors($buttons[0]));
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function readList(array $block): ?array {
    $items = [];
    foreach ($this->getInnerBlocks($block) as $item) {
      if (($item['blockName'] ?? '') !== 'core/list-item' || $this->getInnerBlocks($item)) {
        return null;
      }
      $items[] = $this->getContent($this->getHtml($item));
    }
    $flat = ['type' => NewsletterDraftBlocks::TYPE_LIST, 'items' => $items];
    if ($this->getAttribute($block, ['ordered']) === true) {
      $flat['ordered'] = true;
    }
    return array_merge($flat, $this->readColors($block));
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function readQuote(array $block): ?array {
    $inner = $this->getInnerBlocks($block);
    if (count($inner) !== 1 || ($inner[0]['blockName'] ?? '') !== 'core/paragraph') {
      return null;
    }
    return array_merge(
      ['type' => NewsletterDraftBlocks::TYPE_QUOTE, 'text' => $this->getContent($this->getHtml($inner[0]))],
      $this->readColors($block)
    );
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>
   */
  private function readSpacer(array $block): array {
    $height = $this->getAttribute($block, ['height']);
    return ['type' => NewsletterDraftBlocks::TYPE_SPACER, 'height' => is_string($height) ? (int)$height : 0];
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>|null
   */
  private function readSection(array $block): ?array {
    $inner = $this->toFlatBlocks($this->getInnerBlocks($block));
    if ($inner === null) {
      return null;
    }
    return array_merge(['type' => NewsletterDraftBlocks::TYPE_SECTION, 'blocks' => $inner], $this->readColors($block));
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, string>
   */
  private function readAlignment(array $block): array {
    $processor = new \WP_HTML_Tag_Processor($this->getHtml($block));
    $class = $processor->next_tag() ? $processor->get_attribute('class') : null;
    $classes = explode(' ', is_string($class) ? $class : '');
    foreach (NewsletterDraftBlocks::ALIGNMENTS as $alignment) {
      if (in_array("has-text-align-$alignment", $classes, true)) {
        return ['align' => $alignment];
      }
    }
    return [];
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, string>
   */
  private function readColors(array $block): array {
    $colors = [];
    foreach (['text' => 'text_color', 'background' => 'background_color'] as $attribute => $field) {
      $color = $this->getAttribute($block, ['style', 'color', $attribute]);
      if (is_string($color)) {
        $colors[$field] = $color;
      }
    }
    return $colors;
  }

  /**
   * @param array<string, mixed> $block
   * @param string[] $path
   * @return mixed
   */
  private function getAttribute(array $block, array $path) {
    $value = $block['attrs'] ?? [];
    foreach ($path as $key) {
      if (!is_array($value) || !isset($value[$key])) {
        return null;
      }
      $value = $value[$key];
    }
    return $value;
  }

  /** @param array<string, mixed> $block */
  private function getHtml(array $block): string {
    return is_string($block['innerHTML'] ?? null) ? $block['innerHTML'] : '';
  }

  /**
   * The content of the outermost tag. If that isn't all there is in the HTML, building the block
   * again gives something else, and read() returns null.
   */
  private function getContent(string $html): string {
    $html = trim($html);
    $start = strpos($html, '>');
    $end = strrpos($html, '</');
    return $start === false || $end === false || $end <= $start ? '' : substr($html, $start + 1, $end - $start - 1);
  }

  private function decode(string $text): string {
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
}
