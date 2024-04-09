<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

use WP_Block;

abstract class AbstractBlock {
  protected $namespace = 'mailpoet';
  protected $blockName = '';

  public function __construct() {
      $this->registerBlockType();
  }

  /**
   * Get the block type.
   *
   * @return string
   */
  protected function getBlockType() {
    return $this->namespace . '/' . $this->blockName;
  }

  protected function parseRenderCallbackAttributes($attributes): array {
    return is_a($attributes, 'WP_Block') ? $attributes->attributes : $attributes;
  }

  /**
   * The default render_callback for all blocks.
   */
  public function renderCallback($attributes = [], $content = '', $block = null): string {
      $render_callback_attributes = $this->parseRenderCallbackAttributes($attributes);
      return $this->render($render_callback_attributes, $content, $block);
  }

    /**
     * Registers the block type with WordPress.
     */
  protected function registerBlockType() {
    $metadata_path = __DIR__ . '/' . $this->blockName . '/block.json';
    $block_settings = [
        'render_callback' => [$this, 'renderCallback'],
        'api_version' => '2',
    ];

    register_block_type_from_metadata(
      $metadata_path,
      $block_settings
    );
  }

  /**
   * Render the block. Extended by children.
   *
   * @param array    $attributes Block attributes.
   * @param string   $content    Block content.
   * @param WP_Block $block      Block instance.
   * @return string Rendered block type output.
   */
  protected function render($attributes, $content, $block) {
      return $content;
  }
}
