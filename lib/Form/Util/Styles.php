<?php
namespace MailPoet\Form\Util;

class Styles {
  static function getDefaults() {
    return <<<EOL
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
  }
}