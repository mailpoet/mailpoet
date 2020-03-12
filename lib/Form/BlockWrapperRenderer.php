<?php

namespace MailPoet\Form;

use MailPoet\Models\Form;

class BlockWrapperRenderer {
  public function render(array $block, string $blockContent): string {
    return '<div class="mailpoet_paragraph">' . $blockContent . '</div>';
  }
}
