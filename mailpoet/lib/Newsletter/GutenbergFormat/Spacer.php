<?php

namespace MailPoet\Newsletter\GutenbergFormat;

class Spacer extends GutenbergFormatter {
  private function getAttributes(): array {
    return [ // @Todo: old block has background color as well find a way to implement it as well
      'height' => !empty($this->styles['height']) ? esc_attr($this->styles['height']) : false,
    ];
  }

  public function getBlockMarkup(): string {
    $attributes = array_filter($this->getAttributes());
    return strtr(
      '<!-- wp:spacer %attributes --><div %styles aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->', [
        '%attributes' => \json_encode($attributes),
        '%styles' => isset($attributes['height']) ? sprintf('style="height:%s"', $attributes['height']) : "",
      ]
    );
  }
}
