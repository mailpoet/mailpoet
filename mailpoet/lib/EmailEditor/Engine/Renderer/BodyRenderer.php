<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class BodyRenderer {
  public function renderBody(string $postContent): string {
    // @todo Parse blocks \WP_Block_Parser
    // @todo We need to wrap top level blocks which are not in columns into a column
    // @todo Add rendering of columns (inspire by/reuse code from mailpoet/lib/Newsletter/Renderer/Columns/Renderer)
    // @todo Add rendering of blocks
    return $postContent ?: '';
  }
}
