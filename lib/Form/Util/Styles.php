<?php

namespace MailPoet\Form\Util;

use MailPoetVendor\Sabberworm\CSS\Parser as CSSParser;

class Styles {
  private $defaultCustomStyles = <<<EOL
/* form */
.mailpoet_form {
}

/* columns */
.mailpoet_column_with_background {
  padding: 10px;
}
/* space between columns */
.mailpoet_form_column:not(:first-child) {
  margin-left: 20px;
}

/* input wrapper (label + input) */
.mailpoet_paragraph {
  line-height:20px;
  margin-bottom: 20px;
}

/* labels */
.mailpoet_segment_label,
.mailpoet_text_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_checkbox_label,
.mailpoet_list_label,
.mailpoet_date_label {
  display:block;
  font-weight: normal;
}

/* inputs */
.mailpoet_text,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_date_month,
.mailpoet_date_day,
.mailpoet_date_year,
.mailpoet_date {
  display:block;
}

.mailpoet_text,
.mailpoet_textarea {
  width: 200px;
}

.mailpoet_checkbox {
}

.mailpoet_submit {
}

.mailpoet_divider {
}

.mailpoet_message {
}

.mailpoet_validate_success {
  font-weight: 600;
  color:#468847;
}

.mailpoet_validate_error {
  color:#B94A48;
}

.mailpoet_form_loading {
  width: 30px;
  text-align: center;
  line-height: normal;
}

.mailpoet_form_loading > span {
  width: 5px;
  height: 5px;
  background-color: #5b5b5b;
}
EOL;

  public function getDefaultCustomStyles() {
    return $this->defaultCustomStyles;
  }

  public function prefixStyles($stylesheet, $prefix = '') {
    if (!$stylesheet) return;
    $styles = new CSSParser($stylesheet);
    $styles = $styles->parse();
    $formattedStyles = [];
    foreach ($styles->getAllDeclarationBlocks() as $styleDeclaration) {
      $selectors = array_map(function($selector) use ($prefix) {
        return sprintf('%s %s', $prefix, $selector->__toString());
      }, $styleDeclaration->getSelectors());
      $selectors = implode(', ', $selectors);
      $rules = array_map(function($rule) {
        return $rule->__toString();
      }, $styleDeclaration->getRules());
      $rules = sprintf('{ %s }', implode(' ', $rules));
      $formattedStyles[] = sprintf('%s %s', $selectors, $rules);
    }
    return implode(PHP_EOL, $formattedStyles);
  }

  public function renderFormSettingsStyles(array $form, string $selector = null): string {
    if (is_null($selector)) return '';
    if (!isset($form['settings'])) return '';
    $formSettings = $form['settings'];
    // Wrapper styles
    $styles = [];

    if (isset($formSettings['backgroundColor'])) {
      $styles[] = 'background-color: ' . trim($formSettings['backgroundColor']);
    }

    if (isset($formSettings['border_size']) && isset($formSettings['border_color'])) {
      $styles[] = 'border: ' . $formSettings['border_size'] . 'px solid ' . $formSettings['border_color'];
    }

    if (isset($formSettings['border_radius'])) {
      $styles[] = 'border-radius: ' . $formSettings['border_radius'] . 'px';
    }

    if (isset($formSettings['background_image_url'])) {
      $styles[] = 'background-image: url(' . trim($formSettings['background_image_url']) . ')';
      $backgroundPosition = 'center';
      $backgroundRepeat = 'no-repeat';
      $backgroundSize = 'cover';
      if (isset($formSettings['background_image_display']) && $formSettings['background_image_display'] === 'fit') {
        $backgroundPosition = 'center top';
        $backgroundSize = 'contain';
      }
      if (isset($formSettings['background_image_display']) && $formSettings['background_image_display'] === 'tile') {
        $backgroundRepeat = 'repeat';
        $backgroundSize = 'auto';
      }
      $styles[] = 'background-position: ' . $backgroundPosition;
      $styles[] = 'background-repeat: ' . $backgroundRepeat;
      $styles[] = 'background-size: ' . $backgroundSize;
    }

    if (isset($formSettings['fontColor'])) {
      $styles[] = 'color: ' . trim($formSettings['fontColor']);
    }

    if (isset($formSettings['alignment'])) {
      $styles[] = 'text-align: ' . $formSettings['alignment'];
    }
    $formWrapperStyles = $selector . '{' . join(';', $styles) . ';}';

    // Media styles
    $media = "@media (max-width: 500px) {{$selector} {background-image: none;}}";

    // Form element styles
    $formStyles = [];
    if (isset($formSettings['form_padding'])) {
      $formStyles[] = 'padding: ' . $formSettings['form_padding'] . 'px';
    }
    $formElementStyles = $selector . ' form.mailpoet_form {' . join(';', $formStyles) . ';}';

    return $formWrapperStyles . $formElementStyles . $media;
  }
}
