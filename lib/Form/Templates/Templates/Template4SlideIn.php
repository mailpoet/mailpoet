<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template4SlideIn extends FormTemplate {
  const ID = 'template_4_slide_in';

  public function getName(): string {
    return 'Template 4 Slide-in';
  }

  public function getBody(): array {
    return [
      [
        'type' => 'image',
        'id' => 'image',
        'params' => [
          'class_name' => '',
          'align' => 'center',
          'url' => 'http://mailpoet.info/wp-content/uploads/2020/07/mailbox@3x.png',
          'alt' => '',
          'title' => '',
          'caption' => '',
          'link_destination' => 'none',
          'link' => 'http://mailpoet.info/mailbox3x/',
          'href' => '',
          'link_class' => '',
          'rel' => '',
          'link_target' => '',
          'id' => '247',
          'size_slug' => 'large',
          'width' => '95',
          'height' => '90',
        ],
      ],
      [
        'type' => 'divider',
        'params' => [
          'class_name' => '',
          'height' => '1',
          'type' => 'spacer',
          'style' => 'solid',
          'divider_height' => '1',
          'divider_width' => '100',
          'color' => '#ffffff',
        ],
        'id' => 'divider',
        'name' => 'Divider',
      ],
      [
        'type' => 'heading',
        'id' => 'heading',
        'params' => [
          'content' => _x('<strong>Oh hi there 👋</strong><br><strong>It\'s nice to meet you.</strong>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'level' => '2',
          'align' => 'center',
          'font_size' => '20',
          'text_color' => '#0081ff',
          'line_height' => '1.5',
          'background_color' => '',
          'anchor' => '',
          'class_name' => '',
        ],
      ],
      [
        'type' => 'heading',
        'id' => 'heading',
        'params' => [
          'content' => _x('<strong>Sign up to receive awesome content in your inbox, every month.</strong>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
          'level' => '2',
          'align' => 'center',
          'font_size' => '18',
          'text_color' => '',
          'line_height' => '1.5',
          'background_color' => '',
          'anchor' => '',
          'class_name' => '',
        ],
      ],
      [
        'type' => 'divider',
        'params' => [
          'class_name' => '',
          'height' => '1',
          'type' => 'spacer',
          'style' => 'solid',
          'divider_height' => '1',
          'divider_width' => '100',
          'color' => '#ffffff',
        ],
        'id' => 'divider',
        'name' => 'Divider',
      ],
      [
        'type' => 'text',
        'params' => [
          'label' => _x('Enter your e-mail', 'Form label', 'mailpoet'),
          'class_name' => '',
          'required' => '1',
          'label_within' => '1',
        ],
        'id' => 'email',
        'name' => 'Email',
        'styles' => [
          'full_width' => '1',
          'bold' => '0',
          'background_color' => '#f1f1f1',
          'border_size' => '0',
          'border_radius' => '40',
          'border_color' => '#313131',
        ],
      ],
      [
        'type' => 'submit',
        'params' => [
          'label' => _x('Let\'s keep in touch', 'Form label', 'mailpoet'),
          'class_name' => '',
        ],
        'id' => 'submit',
        'name' => 'Submit',
        'styles' => [
          'full_width' => '1',
          'bold' => '1',
          'background_color' => '#0081ff',
          'font_size' => '20',
          'font_color' => '#ffffff',
          'border_size' => '0',
          'border_radius' => '40',
          'border_color' => '#313131',
          'padding' => '15',
          'font_family' => 'Montserrat',
        ],
      ],
      [
        'type' => 'paragraph',
        'id' => 'paragraph',
        'params' => [
          'content' => _x('<em>We don\'t spam! Read our <a href="/privacy-notice/">privacy policy</a> for more info.</em>', 'Text in a web form. Keep HTML tags!', 'mailpoet'),
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
      'alignment' => 'center',
      'fontSize' => '16',
      'form_placement_slide_in_enabled' => '1',
      'form_placement_fixed_bar_enabled' => '',
      'form_placement_popup_enabled' => '',
      'form_placement_bellow_posts_enabled' => '',
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '',
      'place_popup_form_on_all_pages' => '',
      'place_popup_form_on_all_posts' => '',
      'popup_form_delay' => '15',
      'place_fixed_bar_form_on_all_pages' => '',
      'place_fixed_bar_form_on_all_posts' => '',
      'fixed_bar_form_delay' => '15',
      'fixed_bar_form_position' => 'top',
      'place_slide_in_form_on_all_pages' => '',
      'place_slide_in_form_on_all_posts' => '',
      'slide_in_form_delay' => '0',
      'slide_in_form_position' => 'right',
      'border_radius' => '25',
      'border_size' => '0',
      'form_padding' => '30',
      'input_padding' => '15',
      'font_family' => 'Montserrat',
      'below_post_styles' => [
        'width' => [
          'unit' => 'percent',
          'value' => '100',
        ],
      ],
      'slide_in_styles' => [
        'width' => [
          'unit' => 'pixel',
          'value' => '380',
        ],
      ],
      'fixed_bar_styles' => [
        'width' => [
          'unit' => 'percent',
          'value' => '100',
        ],
      ],
      'popup_styles' => [
        'width' => [
          'unit' => 'pixel',
          'value' => '520',
        ],
      ],
      'other_styles' => [
        'width' => [
          'unit' => 'percent',
          'value' => '100',
        ],
      ],
      'close_button' => 'round_black',
      'success_validation_color' => '#00d084',
      'error_validation_color' => '#cf2e2e',
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
