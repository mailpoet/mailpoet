<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template3FixedBar extends FormTemplate {
  public function getName(): string {
    return 'Template 3 Fixed Bar';
  }

  public function getBody(): array {
    return [
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => 'center',
              'width' => '67.5',
            ],
            'body' => [
              [
                'type' => 'heading',
                'id' => 'heading',
                'params' => [
                  'content' => _x('<span style="font-family: Montserrat" data-font="Montserrat" class="mailpoet-has-font"><strong>10% off,</strong> <strong>especially for you 🎁</strong></span>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
                  'level' => '1',
                  'align' => 'left',
                  'font_size' => '30',
                  'text_color' => '#000000',
                  'background_color' => '',
                  'anchor' => 'block-heading_0.27494222669689683-1595510796066',
                  'class_name' => '',
                ],
              ],
              [
                'type' => 'heading',
                'id' => 'heading',
                'params' => [
                  'content' => _x('<span style="font-family: Montserrat" data-font="Montserrat" class="mailpoet-has-font"><strong>Sign up to receive your exclusive discount,</strong></span><br><span style="font-family: Montserrat" data-font="Montserrat" class="mailpoet-has-font"><strong>and keep up to date on our latest products &amp; offers!</strong></span>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
                  'level' => '2',
                  'align' => 'left',
                  'font_size' => '16',
                  'text_color' => '#000000',
                  'line_height' => '1.7',
                  'background_color' => '',
                  'anchor' => '',
                  'class_name' => '',
                ],
              ],
              [
                'type' => 'paragraph',
                'id' => 'paragraph',
                'params' => [
                  'content' => _x('<em>We don\'t spam! Read our <a href="/privacy-notice/">privacy policy</a> for more info.</em>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
                  'drop_cap' => '0',
                  'align' => 'left',
                  'font_size' => '14',
                  'text_color' => '',
                  'background_color' => '',
                  'class_name' => '',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => 'center',
              'width' => '32.5',
            ],
            'body' => [
              [
                'type' => 'divider',
                'params' => [
                  'class_name' => '',
                  'height' => '1',
                  'type' => 'spacer',
                  'style' => 'solid',
                  'divider_height' => '1',
                  'divider_width' => '100',
                  'color' => 'black',
                ],
                'id' => 'divider',
                'name' => 'Divider',
              ],
              [
                'type' => 'text',
                'params' => [
                  'label' => _x('Email', 'Form label', 'mailpoet'),
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
                  'border_size' => '1',
                  'border_radius' => '0',
                  'border_color' => '#313131',
                ],
              ],
              [
                'type' => 'submit',
                'params' => [
                  'label' => _x('Save 10%', 'Form label', 'mailpoet'),
                  'class_name' => '',
                ],
                'id' => 'submit',
                'name' => 'Submit',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '1',
                  'background_color' => '#000000',
                  'font_size' => '16',
                  'font_color' => '#ffffff',
                  'border_size' => '1',
                  'border_radius' => '2',
                  'border_color' => '#313131',
                  'padding' => '12',
                  'font_family' => 'Montserrat',
                ],
              ],
            ],
          ],
        ],
        'params' => [
          'vertical_alignment' => '',
          'class_name' => '',
          'text_color' => '',
          'background_color' => '',
          'gradient' => '',
        ],
      ],
    ];
  }

  public function getSettings(): array {
    return [
      'on_success' => 'message',
      'success_message' => '',
      'segments' => [],
      'segments_selected_by' => 'admin',
      'alignment' => 'left',
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '',
      'place_popup_form_on_all_pages' => '',
      'place_popup_form_on_all_posts' => '',
      'popup_form_delay' => '15',
      'place_fixed_bar_form_on_all_pages' => '1',
      'place_fixed_bar_form_on_all_posts' => '1',
      'fixed_bar_form_delay' => '0',
      'fixed_bar_form_position' => 'top',
      'place_slide_in_form_on_all_pages' => '',
      'place_slide_in_form_on_all_posts' => '',
      'slide_in_form_delay' => '15',
      'slide_in_form_position' => 'right',
      'border_radius' => '2',
      'border_size' => '0',
      'form_padding' => '16',
      'input_padding' => '12',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
      'below_post_styles' => [
        'width' => [
          'unit' => 'percent',
          'value' => '100',
        ],
      ],
      'slide_in_styles' => [
        'width' => [
          'unit' => 'pixel',
          'value' => '420',
        ],
      ],
      'fixed_bar_styles' => [
        'width' => [
          'unit' => 'pixel',
          'value' => '1100',
        ],
      ],
      'popup_styles' => [
        'width' => [
          'unit' => 'pixel',
          'value' => '400',
        ],
      ],
      'other_styles' => [
        'width' => [
          'unit' => 'percent',
          'value' => '100',
        ],
      ],
      'close_button' => 'classic',
      'fontSize' => '16',
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

p.mailpoet_form_paragraph {
    margin-bottom: 10px;
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
