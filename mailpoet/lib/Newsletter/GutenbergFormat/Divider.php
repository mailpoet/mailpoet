<?php

namespace MailPoet\Newsletter\GutenbergFormat;

class Divider {

  /*** @var array $styles */
  private $styles;

  public function __construct(
    array $block
  ) {
    $this->styles = $block['styles']['block'];
  }

  public function getAttributes(): array {
    return [
        'className' => $this->getStyleClassName(),
    ];
  }

  public function getClassNames(): string {
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
