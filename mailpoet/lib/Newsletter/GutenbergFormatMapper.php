<?php declare(strict_types=1);

namespace MailPoet\Newsletter;

class GutenbergFormatMapper {
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
        default:
          $result .= '<!-- wp:mailpoet/todo {"originalBlock":"' . $block['type'] . '"} /-->';
      }
    }
    return $result;
  }
}
