<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template1BelowPages extends FormTemplate {
  const ID = 'template_1_below_pages';

  public function getName(): string {
    return 'Template 1 Below Pages';
  }

  public function getBody(): array {
    return [
      [
        'type' => 'heading',
        'id' => 'heading',
        'params' => [
          'content' => $this->wp->wpStaticizeEmoji('🤞') . ' ' .  _x('<span style="font-family: BioRhyme" data-font="BioRhyme" class="mailpoet-has-font">Don’t miss these tips!</span>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'level' => '1',
          'align' => 'center',
          'font_size' => '40',
          'text_color' => '#313131',
          'line_height' => '1.2',
          'background_color' => '',
          'anchor' => '',
          'class_name' => '',
        ],
      ],
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '10',
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '40',
            ],
            'body' => [
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
                  'bold' => '1',
                  'background_color' => '#eeeeee',
                  'font_color' => '#abb8c3',
                  'border_size' => '0',
                  'border_radius' => '8',
                  'border_color' => '#313131',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '40',
            ],
            'body' => [
              [
                'type' => 'submit',
                'params' => [
                  'label' => _x('JOIN THE CLUB', 'Form label', 'mailpoet'),
                  'class_name' => '',
                ],
                'id' => 'submit',
                'name' => 'Submit',
                'styles' => [
                  'full_width' => '1',
                  'bold' => '1',
                  'background_color' => '#000000',
                  'font_size' => '20',
                  'font_color' => '#ffd456',
                  'border_size' => '0',
                  'border_radius' => '8',
                  'padding' => '16',
                  'font_family' => 'Montserrat',
                ],
              ],
            ],
          ],
          [
            'type' => 'column',
            'params' => [
              'class_name' => '',
              'vertical_alignment' => '',
              'width' => '10',
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
      [
        'type' => 'paragraph',
        'id' => 'paragraph',
        'params' => [
          'content' => _x('<em><em><em><span style="font-family: Montserrat" data-font="Montserrat" class="mailpoet-has-font">We don’t spam! Read more in our <a href="#">privacy policy</a>.</span></em></em></em>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'drop_cap' => '0',
          'align' => 'center',
          'font_size' => '13',
          'line_height' => '1.5',
          'text_color' => '',
          'background_color' => '',
          'class_name' => '',
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
      'fontColor' => '#313131',
      'form_placement' => [
        'popup' => ['enabled' => ''],
        'below_posts' => [
          'enabled' => '1',
          'styles' => [
            'width' => [
              'unit' => 'percent',
              'value' => '100',
            ],
          ],
        ],
        'fixed_bar' => ['enabled' => ''],
        'slide_in' => ['enabled' => ''],
        'others' => [],
      ],
      'border_radius' => '15',
      'border_size' => '10',
      'form_padding' => '10',
      'input_padding' => '16',
      'background_image_display' => 'scale',
      'fontSize' => '20',
      'font_family' => 'Montserrat',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
      'backgroundColor' => '#ffffff',
      'background_image_url' => '',
      'close_button' => 'classic',
      'border_color' => '#f7f7f7',
      'form_placement_bellow_posts_enabled' => '1',
      'form_placement_popup_enabled' => '',
      'form_placement_fixed_bar_enabled' => '',
      'form_placement_slide_in_enabled' => '',
    ];
  }

  public function getStyles(): string {
    return <<<EOL
/* form */
form.mailpoet_form {
  margin-bottom: 0;
}

p.mailpoet_form_paragraph.last {
    margin-bottom: 0px;
}

h1.mailpoet-heading {
	margin: 0 0 10px;
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
