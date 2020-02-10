<?php

namespace MailPoet\Form\Block;

class Divider {

  public function render(): string {
    return '<hr class="mailpoet_divider" />';
  }
}
