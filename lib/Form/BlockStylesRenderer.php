<?php

namespace MailPoet\Form;

class BlockStylesRenderer {
  public function renderForTextInput(array $styles, array $formSettings = []): string {
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
    if (isset($formSettings['input_padding'])) {
      $rules[] = "padding:{$formSettings['input_padding']}px;";
    }
    if (isset($formSettings['alignment'])) {
      $rules[] = $this->convertAlignmentToMargin($formSettings['alignment']);
    }
    if (isset($styles['font_size'])) {
      $rules[] = "font-size:" . intval($styles['font_size']) . "px;";
    }
    if (isset($formSettings['fontSize']) && !isset($styles['font_size'])) {
      $rules[] = "font-size:" . intval($formSettings['fontSize']) . "px;";
    }
    if (isset($formSettings['fontSize']) || isset($styles['font_size'])) {
      $rules[] = "line-height:1.5;";
    }
    if (isset($styles['font_color'])) {
      $rules[] = "color:{$styles['font_color']};";
    }
    return implode('', $rules);
  }

  public function renderForButton(array $styles, array $formSettings = []): string {
    $rules = [];
    if (!isset($styles['border_color'])) {
      $rules[] = "border-color:transparent;";
    }
    if (isset($styles['bold']) && $styles['bold'] === '1') {
      $rules[] = "font-weight:bold;";
    }
    return $this->renderForTextInput($styles, $formSettings) . implode('', $rules);
  }

  public function renderForSelect(array $styles, array $formSettings = []): string {
    $rules = [];
    if (isset($formSettings['input_padding'])) {
      $rules[] = "padding:{$formSettings['input_padding']}px;";
    }
    if (isset($formSettings['alignment'])) {
      $rules[] = $this->convertAlignmentToMargin($formSettings['alignment']);
    }
    return implode('', $rules);
  }

  private function convertAlignmentToMargin(string $alignment): string {
    if ($alignment === 'right') {
      return 'margin: 0 0 0 auto;';
    }
    if ($alignment === 'center') {
      return 'margin: 0 auto;';
    }
    return 'margin: 0 auto;';
  }
}
