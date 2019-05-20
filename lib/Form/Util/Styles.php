<?php
namespace MailPoet\Form\Util;

use MailPoetVendor\Sabberworm\CSS\Parser as CSSParser;

class Styles {
  public $styles;
  static $default_styles = <<<EOL
/* form */
.mailpoet_form {

}

/* paragraphs (label + input) */
.mailpoet_paragraph {
  line-height:20px;
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
  font-weight:bold;
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
  width:200px;
}

.mailpoet_checkbox {
}

.mailpoet_submit input {
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
  private $stylesheet;

  function __construct($stylesheet = null) {
    $this->stylesheet = $stylesheet;
  }

  function render($prefix = '') {
    if (!$this->stylesheet) return;
    $styles = new CSSParser($this->stylesheet);
    $styles = $styles->parse();
    $formatted_styles = [];
    foreach ($styles->getAllDeclarationBlocks() as $style_declaration) {
      $selectors = array_map(function($selector) use ($prefix) {
        return sprintf('%s %s', $prefix, $selector->__toString());
      }, $style_declaration->getSelectors());
      $selectors = implode(', ', $selectors);
      $rules = array_map(function($rule) {
        return $rule->__toString();
      }, $style_declaration->getRules());
      $rules = sprintf('{ %s }', implode(' ', $rules));
      $formatted_styles[] = sprintf('%s %s', $selectors, $rules);
    }
    return implode(PHP_EOL, $formatted_styles);
  }
}
