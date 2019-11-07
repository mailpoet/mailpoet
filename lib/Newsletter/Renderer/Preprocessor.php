<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Newsletter\Renderer\Blocks\Renderer as BlocksRenderer;

class Preprocessor {

  /** @var BlocksRenderer */
  private $blocks_renderer;

  public function __construct(BlocksRenderer $blocks_renderer) {
    $this->blocks_renderer = $blocks_renderer;
  }

  /**
   * @param array $content
   * @return array
   */
  public function process($content) {
    if (!array_key_exists('blocks', $content)) {
      return $content;
    }
    $blocks = [];
    foreach ($content['blocks'] as $block) {
      if ($block['type'] === 'automatedLatestContentLayout') {
        $blocks = array_merge(
          $blocks,
          $this->blocks_renderer->automatedLatestContentTransformedPosts($block)
        );
      } else {
        $blocks[] = $block;
      }
    }
    $content['blocks'] = $blocks;
    return $content;
  }
}