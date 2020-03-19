<?php

namespace MailPoet\Form;

class BlockStylesRenderer {
  public function renderForTextInput(array $styles): string {
    $rules = [];
    if (isset($styles['full_width']) && intval($styles['full_width'])) {
      $rules[] = 'width:100%;';
    }
    if (isset($styles['background_color'])) {
      $rules[] = "background-color:{$styles['background_color']};";
    }
    if (isset($styles['border_size']) || isset($styles['border_radius']) || isset($styles['border_color'])) {
      $rules[] = "border-style:solid;";
    }
    if (isset($styles['border_radius'])) {
      $rules[] = "border-radius:" . intval($styles['border_radius']) . "px;";
    }
    if (isset($styles['border_size'])) {
      $rules[] = "border-width:" . intval($styles['border_size']) . "px;";
    }
    if (isset($styles['border_color'])) {
      $rules[] = "border-color:{$styles['border_color']};";
    }
    return implode('', $rules);
  }

  public function renderForButton(array $styles): string {
    $rules = [];
    if (isset($styles['font_color'])) {
      $rules[] = "color:{$styles['font_color']};";
    }
    if (isset($styles['font_size'])) {
      $rules[] = "font-size:" . intval($styles['font_size']) . "px;";
    }
    if (isset($styles['bold']) && $styles['bold'] === '1') {
      $rules[] = "font-weight:bold;";
    }
    return $this->renderForTextInput($styles) . implode('', $rules);
  }
}
