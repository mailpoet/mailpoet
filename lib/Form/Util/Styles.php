<?php

namespace MailPoet\Form\Util;

use MailPoet\Entities\FormEntity;
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

  public function renderFormSettingsStyles(array $form, string $selector, string $displayType): string {
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
    $formElementStyles = '';
    if ($formStyles) {
      $formElementStyles = $selector . ' form.mailpoet_form {' . join(';', $formStyles) . ';}';
    }

    // Width styles
    $widthStyles = $this->renderWidthStyles($formSettings, $selector, $displayType);

    $messagesStyles = $this->renderMessagesStyles($formSettings, $selector);

    return $formWrapperStyles
      . $formElementStyles
      . $widthStyles
      . $messagesStyles
      . $media;
  }

  private function renderWidthStyles($formSettings, $selector, $displayType) {
    $styles = [];

    if ($displayType === FormEntity::DISPLAY_TYPE_POPUP) {
      if (isset($formSettings['popup_styles']['width'])) {
        $width = $this->getWidthValue($formSettings['popup_styles']['width']);
        $styles[] = "width: $width";
        $styles[] = "max-width: 100vw";
      } else { // BC compatibilty
        $styles[] = 'width: 560px';
        $styles[] = 'max-width: 560px';
      }
    } elseif ($displayType === FormEntity::DISPLAY_TYPE_SLIDE_IN) {
      if (isset($formSettings['slide_in_styles']['width'])) {
        $width = $this->getWidthValue($formSettings['slide_in_styles']['width']);
        $styles[] = "width: $width";
        $styles[] = "max-width: 100vw";
      } else { // BC compatibilty
        $styles[] = 'max-width: 600px';
        $styles[] = 'min-width: 350px';
      }
    } elseif ($displayType === FormEntity::DISPLAY_TYPE_FIXED_BAR) {
      if (isset($formSettings['fixed_bar_styles']['width'])) {
        $width = $this->getWidthValue($formSettings['fixed_bar_styles']['width']);
        $styles[] = "width: $width";
        $styles[] = "max-width: 100%";
      } else { // BC compatibilty
        $styles[] = 'max-width: 960px';
      }
    } elseif ($displayType === FormEntity::DISPLAY_TYPE_BELOW_POST) {
      if (isset($formSettings['below_post_styles']['width'])) {
        $width = $this->getWidthValue($formSettings['below_post_styles']['width']);
        $styles[] = "width: $width";
      }
    } elseif ($displayType === FormEntity::DISPLAY_TYPE_OTHERS) {
      if (isset($formSettings['other_styles']['width'])) {
        $width = $this->getWidthValue($formSettings['other_styles']['width']);
        $styles[] = "width: $width";
      }
    }

    $widthSelector = $selector;
    $widthSelector .= $displayType === FormEntity::DISPLAY_TYPE_FIXED_BAR ? ' form.mailpoet_form' : '';

    if (!$styles) {
      return '';
    }
    return $widthSelector . '{' . join(';', $styles) . ';}';
  }

  private function getWidthValue(array $width) {
    return $width['value'] . ($width['unit'] === 'percent' ? '%' : 'px');
  }

  private function renderMessagesStyles(array $formSettings, string $selector): string {
    $styles = '';
    if (isset($formSettings['success_validation_color']) && $formSettings['success_validation_color']) {
      $success = $formSettings['success_validation_color'];
      $styles .= "
        $selector .mailpoet_validate_success {color: $success}
        $selector input.parsley-success {color: $success}
        $selector select.parsley-success {color: $success}
        $selector textarea.parsley-success {color: $success}
      ";
    }
    if (isset($formSettings['error_validation_color']) && $formSettings['error_validation_color']) {
      $error = $formSettings['error_validation_color'];
      $styles .= "
        $selector .mailpoet_validate_error {color: $error}
        $selector input.parsley-error {color: $error}
        $selector select.parsley-error {color: $error}
        $selector textarea.textarea.parsley-error {color: $error}
        $selector .parsley-errors-list {color: $error}
        $selector .parsley-required {color: $error}
        $selector .parsley-custom-error-message {color: $error}
      ";
    }
    return $styles;
  }
}
