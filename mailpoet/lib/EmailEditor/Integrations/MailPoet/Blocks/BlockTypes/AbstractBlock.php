<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

abstract class AbstractBlock {
  protected $namespace = 'mailpoet';
  protected $blockName = '';

  public function __construct() {
      $this->registerBlockType();
  }

  protected function getBlockType(): string {
    return $this->namespace . '/' . $this->blockName;
  }

  protected function parseRenderCallbackAttributes($attributes): array {
    return is_a($attributes, 'WP_Block') ? $attributes->attributes : $attributes;
  }

  protected function registerBlockType() {
    if (\WP_Block_Type_Registry::get_instance()->is_registered($this->getBlockType())) {
      return;
    }

    $metadata_path = __DIR__ . '/' . $this->blockName . '/block.json';
    $block_settings = [
        'render_callback' => [$this, 'render'],
        'api_version' => '2',
    ];

    register_block_type_from_metadata(
      $metadata_path,
      $block_settings
    );
  }

  abstract public function render($attributes, $content, $block);
}
