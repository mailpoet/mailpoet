<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlockRenderer;
use WP_Style_Engine;

/**
 * Shared functionality for block renderers.
 */
abstract class AbstractBlockRenderer implements BlockRenderer {
  /**
   * Wrapper for wp_style_engine_get_styles which ensures all values are returned.
   *
   * @param array $block_styles Array of block styles.
   * @param bool $skip_convert_vars If true, --wp_preset--spacing--x type values will be left in the original var:preset:spacing:x format.
   * @return array
   */
  protected function getStylesFromBlock(array $block_styles, $skip_convert_vars = false) {
    $styles = wp_style_engine_get_styles($block_styles, ['convert_vars_to_classnames' => $skip_convert_vars]);
    return wp_parse_args($styles, [
      'css' => '',
      'declarations' => [],
      'classnames' => '',
    ]);
  }

  /**
   * Compile objects containing CSS properties to a string.
   *
   * @param array ...$styles Style arrays to compile.
   * @return string
   */
  protected function compileCss(...$styles): string {
    return WP_Style_Engine::compile_css(array_merge(...$styles), '');
  }
}
