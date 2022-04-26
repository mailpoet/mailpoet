<?php

namespace MailPoet\Newsletter\GutenbergFormat;

abstract class GutenbergFormatter {

  /*** @var array $block */
  protected $block;

  /*** @var array $styles */
  protected $styles;

  public function __construct(
    array $block
  ) {
    $this->block = $block;
    $this->styles = $block['styles']['block'];
  }

  public abstract function getBlockMarkup(): string;
}
