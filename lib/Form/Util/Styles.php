<?php
namespace MailPoet\Form\Util;

class Styles {
  private $_stylesheet = null;
  private $_styles = array();

  static $defaults =<<<EOL
/* form */
.mailpoet_form {

}

/* paragraphs (label + input) */
.mailpoet_paragraph {

}

/* labels */
.mailpoet_input_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_list_label,
.mailpoet_checkbox_label,
.mailpoet_date_label {
  display:block;
}

/* inputs */
.mailpoet_input,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_radio,
.mailpoet_checkbox,
.mailpoet_date {
  display:block;
}

.mailpoet_validate_success {
  color:#468847;
}

.mailpoet_validate_error {
  color:#B94A48;
}
EOL;

  function __construct($stylesheet = null) {
    // store raw styles
    $this->setStylesheet($stylesheet);

    // extract rules/properties
    $this->parseStyles();

    return $this;
  }

  function render($prefix = '') {
    $styles = $this->getStyles();
    if(!empty($styles)) {
      $output = array();

      // add prefix on each selector
      foreach($styles as $style) {
        // check if selector is an array
        if(is_array($style['selector'])) {
          $selector = join(",\n", array_map(function($value) use ($prefix) {
            return $prefix.' '.$value;
          }, $style['selector']));
        } else {
          $selector = $prefix.' '.$style['selector'];
        }

        // format selector
        $output[] = $selector . ' {';

        // format rules
        if(!empty($style['rules'])) {
          $rules = join("\n", array_map(function($rule) {
            return "\t".$rule['property'] . ': ' . $rule['value'].';';
          }, $style['rules']));

          $output[] = $rules;
        }

        $output[] = '}';
      }

      return join("\n", $output);
    }
  }

  private function setStylesheet($stylesheet) {
    $this->_stylesheet = $this->stripComments($stylesheet);
    return $this;
  }

  private function stripComments($stylesheet) {
        // remove comments
    return preg_replace('!/\*.*?\*/!s', '', $stylesheet);
  }

  private function getStylesheet() {
    return $this->_stylesheet;
  }

  private function setStyles($styles) {
    $this->_styles = $styles;

    return $this;
  }

  private function getStyles() {
    return $this->_styles;
  }

  private function parseStyles() {
    if($this->getStylesheet() !== null) {
      // extract selectors and rules
      preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/',
        $this->getStylesheet(),
        $matches
      );
      $selectors = $matches[1];
      $rules = $matches[2];

      // extracted styles
      $styles = array();

      // loop through each selector
      foreach($selectors as $index => $selector) {
        // trim selector
        $selector = trim($selector);
        // get selector rules
        $selector_rules = array_filter(array_map(function($value) {
          if(strlen(trim($value)) > 0) {
            // split property / value
            $pair = explode(':', trim($value));
            if(isset($pair[0]) && isset($pair[1])) {
              return array(
                'property' => $pair[0],
                'value' => $pair[1]
              );
            }
          }
        }, explode(';', trim($rules[$index]))));

        // check if we have multiple selectors
        if(strpos($selector, ',') !== FALSE) {
          $selectors_array = array_filter(array_map(function($value) {
            return trim($value);
          }, explode(',', $selector)));

          // multiple selectors
          $styles[$index] = array(
            'selector' => $selectors_array,
            'rules' => $selector_rules
          );
        } else {
          // it's a single selector
          $styles[$index] = array(
            'selector' => $selector,
            'rules' => $selector_rules
          );
        }
      }

      $this->setStyles($styles);
    }
  }

  function __toString() {
    $this->stripComments();
    return $this->render();
  }
}