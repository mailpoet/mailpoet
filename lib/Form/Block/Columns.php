<?php

namespace MailPoet\Form\Block;

class Columns {
  public function render(array $block, string $content): string {
    return "<div class=\"mailpoet_form_columns\">$content</div>";
  }
}
