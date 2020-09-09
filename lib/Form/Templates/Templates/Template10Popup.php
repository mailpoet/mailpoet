<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template10Popup extends FormTemplate {
  const ID = 'template_10_popup';

  public function getName(): string {
    return 'Template 10 Popup';
  }

  public function getBody(): array {
    return [
      [
        'type' => 'heading',
        'id' => 'heading',
        'params' => [
          'content' => _x('<strong><span style="font-family: Concert One" data-font="Concert One" class="mailpoet-has-font">LET’S KEEP IN TOUCH!</span></strong>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'level' => '2',
          'align' => 'center',
          'font_size' => '50',
          'text_color' => '#ffffff',
          'line_height' => '1',
          'background_color' => '',
          'anchor' => '',
          'class_name' => '',
        ],
      ],
      [
        'type' => 'paragraph',
        'id' => 'paragraph',
        'params' => [
          'content' => _x('<span style="font-family: Concert One" data-font="Concert One" class="mailpoet-has-font">We’d love to keep you updated with our latest news and offers <img draggable="false" role="img" class="emoji" alt="😎" src="https://s.w.org/images/core/emoji/13.0.0/svg/1f60e.svg"></span>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'drop_cap' => '0',
          'align' => 'center',
          'font_size' => '20',
          'line_height' => '1',
          'text_color' => '#ffffff',
          'background_color' => '',
          'class_name' => '',
        ],
      ],
      [
        'type' => 'text',
        'params' => [
          'label' => _x('What’s your name?', 'Form label', 'mailpoet'),
          'class_name' => '',
          'label_within' => '1',
        ],
        'id' => 'first_name',
        'name' => 'First name',
        'styles' => [
          'full_width' => '1',
          'bold' => '0',
          'background_color' => '#ffffff',
          'font_color' => '#5b8ba7',
          'border_size' => '0',
          'border_radius' => '4',
          'border_color' => '#313131',
        ],
      ],
      [
        'type' => 'text',
        'params' => [
          'label' => _x('And your surname?', 'Form label', 'mailpoet'),
          'class_name' => '',
          'label_within' => '1',
        ],
        'id' => 'last_name',
        'name' => 'Last name',
        'styles' => [
          'full_width' => '1',
          'bold' => '0',
          'background_color' => '#ffffff',
          'font_color' => '#5b8ba7',
          'border_size' => '0',
          'border_radius' => '4',
          'border_color' => '#313131',
        ],
      ],
      [
        'type' => 'text',
        'params' => [
          'label' => _x('Pop your email address here', 'Form label', 'mailpoet'),
          'class_name' => '',
          'required' => '1',
          'label_within' => '1',
        ],
        'id' => 'email',
        'name' => 'Email',
        'styles' => [
          'full_width' => '1',
          'bold' => '0',
          'background_color' => '#ffffff',
          'font_color' => '#5b8ba7',
          'border_size' => '0',
          'border_radius' => '4',
          'border_color' => '#313131',
        ],
      ],
      [
        'type' => 'submit',
        'params' => [
          'label' => _x('Keep me posted!', 'Form label', 'mailpoet'),
          'class_name' => '',
        ],
        'id' => 'submit',
        'name' => 'Submit',
        'styles' => [
          'full_width' => '1',
          'bold' => '1',
          'background_color' => '#ff6900',
          'font_size' => '24',
          'font_color' => '#ffffff',
          'border_size' => '0',
          'border_radius' => '40',
          'padding' => '12',
          'font_family' => 'Ubuntu',
        ],
      ],
      [
        'type' => 'paragraph',
        'id' => 'paragraph',
        'params' => [
          'content' => _x('<span style="font-family: Concert One" data-font="Concert One" class="mailpoet-has-font">We don’t spam! Read our <a href="#">Privacy Policy</a> for more details.</span>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'drop_cap' => '0',
          'align' => 'center',
          'font_size' => '14',
          'line_height' => '1.2',
          'text_color' => '#ffffff',
          'background_color' => '',
          'class_name' => '',
        ],
      ],
    ];
  }

  public function getSettings(): array {
    return [
      'success_message' => '',
      'segments' => [],
      'alignment' => 'left',
      'fontSize' => '20',
      'form_placement' => [
        'popup' => [
          'enabled' => '1',
          'delay' => '0',
          'styles' => [
            'width' => [
              'unit' => 'pixel',
              'value' => '440',
            ],
          ],
        ],
        'below_posts' => ['enabled' => ''],
        'fixed_bar' => ['enabled' => ''],
        'slide_in' => ['enabled' => ''],
        'others' => [],
      ],
      'border_radius' => '24',
      'border_size' => '0',
      'form_padding' => '40',
      'input_padding' => '12',
      'background_image_url' => '',
      'background_image_display' => 'scale',
      'close_button' => 'classic_white',
      'segments_selected_by' => 'admin',
      'gradient' => 'linear-gradient(180deg,rgb(70,219,232) 0%,rgb(197,222,213) 100%)',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
      'font_family' => 'Ubuntu',
    ];
  }

  public function getStyles(): string {
    return <<<EOL
/* form */
.mailpoet_form {
}

form {
  margin-bottom: 0;
}

p.mailpoet_form_paragraph.last {
    margin-bottom: 0px;
}

h2.mailpoet-heading {
    margin: -10px 0 10px 0;
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
  }
}
