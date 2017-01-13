<?php
namespace MailPoet\Form\Util;

use Sabberworm\CSS\Parser as CSSParser;

class Styles {
  public $styles;
  static $default_styles = <<<EOL
/* form */
.mailpoet_form {

}

/* paragraphs (label + input) */
.mailpoet_paragraph {

}

/* labels */
.mailpoet_text_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_checkbox_label,
.mailpoet_list_label,
.mailpoet_date_label {
  display:block;
}

/* inputs */
.mailpoet_text,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_date {
  display:block;
}

.mailpoet_checkbox {

}

.mailpoet_validate_success {
  color:#468847;
}

.mailpoet_validate_error {
  color:#B94A48;
}
EOL;

  function __construct($stylesheet = null) {
    $this->stylesheet = $stylesheet;
  }

  function render($prefix = '') {
    if(!$this->stylesheet) return;
    $styles = new CSSParser($this->stylesheet);
    $styles = $styles->parse();
    $formatted_styles = array();
    foreach($styles->getAllDeclarationBlocks() as $style_declaration) {
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