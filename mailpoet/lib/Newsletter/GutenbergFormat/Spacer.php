<?php

namespace MailPoet\Newsletter\GutenbergFormat;

class Spacer extends GutenbergFormatter {
  private function getAttributes(): array {
    return [ // @Todo: old block has background color as well find a way to implement it as well
      'height' => !empty($this->styles['height']) ? esc_attr($this->styles['height']) : false,
      'backgroundColor' => !empty($this->styles['backgroundColor']) ? esc_attr($this->styles['backgroundColor']) : false,
    ];
  }

  public function getBlockMarkup(): string {
    $attributes = array_filter($this->getAttributes());
    $rules = [];
    $rules[] = isset($attributes['height']) ? sprintf('height:%s', $attributes['height']) : '';
    $rules[] = isset($attributes['backgroundColor']) ? sprintf('background-color:%s', $attributes['backgroundColor']) : '';
    $styles = sprintf('style="%s"', implode("; ", $rules));
    return strtr(
      '<!-- wp:spacer %attributes --><div %styles aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->', [
        '%attributes' => \json_encode($attributes),
        '%styles' => $styles,
      ]
    );
  }
}
