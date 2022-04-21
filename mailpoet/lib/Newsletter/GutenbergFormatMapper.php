<?php declare(strict_types=1);

namespace MailPoet\Newsletter;

use MailPoet\Util\pQuery\pQuery;

class GutenbergFormatMapper {
  const NEWSLETTER_WIDTH = 600;

  public function map(array $body): string {
    $blocks = $body['content']['blocks'] ?? [];
    $xxx = $this->mapBlocks($blocks);
    return $xxx;
  }

  private function mapBlocks(array $blocks): string {
    $result = '';
    foreach ($blocks as $block) {
      switch ($block['type']) {
        case 'container':
          if ($block['orientation'] === 'horizontal') {
            $result .= '<!-- wp:columns --><div class="wp-block-columns">' . $this->mapBlocks($block['blocks']) . '</div><!-- /wp:columns -->';
            break;
          }
          $result .= '<!-- wp:column --><div class="wp-block-column">' . $this->mapBlocks($block['blocks']) . '</div><!-- /wp:column -->';
          break;
        case 'footer':
          $result .= '<!-- wp:mailpoet/footer --><p class="wp-block-mailpoet-footer">' . str_replace(["</p>\n<p>", "\n", '<p>', '</p>'], ['<br/>', '<br/>', '', ''], $block['text']) . '</p><!-- /wp:mailpoet/footer -->';
          break;
        case 'header':
          $result .= '<!-- wp:mailpoet/header --><p class="wp-block-mailpoet-header">' . str_replace(["</p>\n<p>", "\n", '<p>', '</p>'], ['<br/>', '<br/>', '', ''], $block['text']) . '</p><!-- /wp:mailpoet/header -->';
          break;
        case 'button':
          $result .= $this->mapButton($block);
          break;
        case 'text':
          $result .= $this->mapText($block);
          break;
        default:
          $result .= '<!-- wp:mailpoet/todo {"originalBlock":"' . $block['type'] . '"} /-->';
      }
    }
    return $result;
  }

  private function mapText(array $block): string {
    $parsed = pQuery::parseStr($block['text']);
    $result = '';
    $childCount = $parsed->childCount(true);
    for ($i = 0; $i < $childCount; $i++) {
      $child = $parsed->getChild($i, true);
      switch ($child->getTag()) {
        case 'p':
          $result .= '<!-- wp:paragraph -->' . $child->toString() . '<!-- /wp:paragraph -->';
          break;
        case 'h1':
        case 'h2':
        case 'h3':
          $result .= '<!-- wp:heading -->' . $child->toString() . '<!-- /wp:heading -->';
          break;
        case 'ol':
          $result .= '<!-- wp:list {"ordered": true} -->' . $child->toString() . '<!-- /wp:list -->';
          break;
        case 'ul':
          $result .= '<!-- wp:list -->' . $child->toString() . '<!-- /wp:list -->';
          break;
        case 'blockquote':
          $result .= '<!-- wp:quote --><blockquote class="wp-block-quote">' . $child->getInnerText() . '</blockquote><!-- /wp:quote -->';
          break;
      }
    }
    return $result;
  }

  /**
  "type": "text",
  "text": "<h2>Heading+1</h2>\n<p>Paragraph+with+text.+<strong>Bold</strong>.+<em>Itallic</em>.+<span+style=\"color:+#eb1c1c\">Custom+colored</span>.+<span+style=\"color:+#2dc26b\">Colored+from+selection</span>.+<a+href=\"https://example.com\"+title=\"Title+link\"+target=\"_blank\">Link</a></p>\n<p+style=\"text-align:+left\">Text+align+left.</p>\n<p+style=\"text-align:+right\">Text+align+right.</p>\n<p+style=\"text-align:+center\">Text+align+middle.</p>\n<p+style=\"text-align:+justify\">Text+aligned+justify.</p>\n<blockquote>\n<p+style=\"text-align:+justify\">Quote+text</p>\n</blockquote>\n<ol>\n<li+style=\"text-align:+justify\">Numbered+list</li>\n<li+style=\"text-align:+justify\">Numbered+list</li>\n</ol>\n<ul>\n<li>bullet+list\n<ul>\n<li>nested+bullet+list</li>\n</ul>\n</li>\n</ul>\n<p></p>\n<p+style=\"text-align:+center\"><a+href=\"[link:subscription_unsubscribe_url]\"+target=\"_blank\">Unsubscribe</a></p>"
  },
   */

  private function mapButton(array $block): string {
    $blockStyles = $block['styles']['block'];
    $attributes = [];
    $width = intval(str_replace('px', '', $blockStyles['width'] ?? ''));
    $fontSize = intval(str_replace('px', '', $blockStyles['fontSize'] ?? ''));
    $lineHeight = intval(str_replace('px', '', $blockStyles['lineHeight'] ?? ''));
    // Approx pixel width to container width. So far only container considered is full width.
    // Todo: use width of nested container
    $attributes['width'] = ceil(($width / self::NEWSLETTER_WIDTH) * 4) * 25;
    $renderWidth = self::NEWSLETTER_WIDTH * ($attributes['width'] / 100);
    $styles = [];
    $styles['border'] = [
      'radius' => $blockStyles['borderRadius'],
      'style' => $blockStyles['borderStyle'],
      'width' => $blockStyles['borderWidth'],
      'color' => $blockStyles['borderColor'],
    ];
    $styles['spacing']['padding'] = [
      'left' => strval(ceil(($width - $renderWidth) / 2)) . 'px',
      'right' => strval(ceil(($width - $renderWidth) / 2)) . 'px',
      'top' => strval(ceil(($lineHeight - ($fontSize * 1.8)) / 2)) . 'px',
      'bottom' => strval(ceil(($lineHeight - ($fontSize * 1.8)) / 2)) . 'px',
    ];
    $styles['typography']['fontSize'] = $blockStyles['fontSize'];
    $styles['color'] = [
      'background' => $blockStyles['backgroundColor'],
      'text' => $blockStyles['fontColor'],
    ];
    $attributes['style'] = $styles;
    $linkStyles = [
      "border-radius:{$blockStyles['borderRadius']}",
      "border-color:{$blockStyles['borderColor']}",
      "border-style:{$blockStyles['borderStyle']}",
      "border-width:{$blockStyles['borderWidth']}",
      "background-color:{$blockStyles['backgroundColor']}",
      "color:{$blockStyles['fontColor']}",
      "padding-top:{$styles['spacing']['padding']['top']}",
      "padding-right:{$styles['spacing']['padding']['right']}",
      "padding-bottom:{$styles['spacing']['padding']['bottom']}",
      "padding-left:{$styles['spacing']['padding']['left']}",
    ];
    $buttonStyles = [
      "font-size:{$blockStyles['fontSize']}",
    ];
    $linkClasses = ['wp-block-button__link'];
    if (isset($blockStyles['fontColor'])) {
      $linkClasses[] = 'has-text-color';
    }
    if (isset($blockStyles['backgroundColor'])) {
      $linkClasses[] = 'has-background';
    }
    if (isset($blockStyles['borderColor'])) {
      $linkClasses[] = 'has-border-color';
    }
    $buttonClasses = ['wp-block-button'];
    $buttonClasses[] = 'has-custom-width';
    $buttonClasses[] = 'wp-block-button__width-' . $attributes['width'];
    if (isset($blockStyles['fontSize'])) {
      $buttonClasses[] = 'has-custom-font-size';
    }
    $result = '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button ' . json_encode($attributes) . ' --><div class="' . esc_attr(join(' ', $buttonClasses)) . '" style="' . esc_attr(join(';', $buttonStyles)) . '"><a class="' . esc_attr(join(' ', $linkClasses)) . '" style="' . esc_attr(join(';', $linkStyles)) . '">' . $block['text'] . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
    return $result;
  }
}
