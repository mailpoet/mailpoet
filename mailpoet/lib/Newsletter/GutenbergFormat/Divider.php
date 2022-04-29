<?php

namespace MailPoet\Newsletter\GutenbergFormat;

class Divider extends GutenbergFormatter {
  public function getBlockMarkup(): string {
    return strtr('<!-- wp:separator %attributes --><hr class="wp-block-separator %classNames"/><!-- /wp:separator -->', [
        '%attributes' => \json_encode($this->getAttributes()),
        '%classNames' => $this->getClassNames(),
      ]
    );
  }

  private function getAttributes(): array {
    return [
        'className' => $this->getStyleClassName(),
    ];
  }

  private function getClassNames(): string {
    // @Todo: extract the following classNames from $this->styles
    return implode(" ", [
      'has-text-color',
      'has-background',
      'has-vivid-green-cyan-background-color',
      'has-vivid-green-cyan-color',
      $this->getStyleClassName(),
    ]);
  }

  private function getStyleClassName(): string {
    if ($this->styles['borderStyle'] === "dotted") {
      return 'is-style-dots';
    }

    return "";
  }
}
