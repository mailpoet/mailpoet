<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\Template;

/**
 * Temporary form template. Remove after we get real data from designer
 */
class DemoForm implements Template {
  public function getName(): string {
    return 'My Fancy Form';
  }

  public function getBody(): array {
    return [
      [
        'type' => 'columns',
        'body' =>
          [
            [
              'type' => 'column',
              'params' => ['class_name' => '', 'vertical_alignment' => '', 'width' => '50'],
              'body' => [
                [
                  'type' => 'text',
                  'params' => ['label' => _x('Email', 'Form label', 'mailpoet'), 'class_name' => '', 'required' => '1'],
                  'id' => 'email',
                  'name' => _x('Email', 'Form label', 'mailpoet'),
                  'styles' => ['full_width' => '1'],
                ],
                [
                  'type' => 'text',
                  'params' => ['label' => _x('First name', 'Form label', 'mailpoet'), 'class_name' => ''],
                  'id' => 'first_name',
                  'name' => _x('First name', 'Form label', 'mailpoet'),
                  'styles' => ['full_width' => '1'],
                ],
              ],
            ],
            [
              'type' => 'column',
              'params' => ['class_name' => '', 'vertical_alignment' => '', 'width' => '50'],
              'body' => [
                [
                  'type' => 'paragraph',
                  'id' => 'paragraph',
                  'params' => [
                    'content' => 'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.',
                    'drop_cap' => '0',
                    'align' => 'left',
                    'font_size' => '',
                    'text_color' => '',
                    'background_color' => '',
                    'class_name' => '',
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
        ],
      ],
      [
        'type' => 'submit',
        'params' => ['label' => _x('Subscribe!', 'Form label', 'mailpoet'), 'class_name' => ''],
        'id' => 'submit',
        'name' => 'Submit',
        'styles' => [
          'full_width' => '0',
          'bold' => '0',
          'background_color' => '#ff6900',
          'font_size' => '36',
          'font_color' => '#313131',
          'border_size' => '1',
          'border_radius' => '8',
          'border_color' => '#f78da7',
          'padding' => '5',
        ],
      ],
    ];
  }

  public function getSettings(): array {
    return [
      'segments' => [],
      'on_success' => 'message',
      'success_message' => '',
      'success_page' => '',
      'segments_selected_by' => 'admin',
      'alignment' => 'left',
      'border_radius' => '0',
      'border_size' => '0',
      'form_padding' => '20',
      'input_padding' => '5',
      'below_post_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
      'slide_in_styles' => ['width' => ['unit' => 'pixel', 'value' => '560']],
      'fixed_bar_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
      'popup_styles' => ['width' => ['unit' => 'pixel', 'value' => '560']],
      'other_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
    ];
  }

  public function getStyles(): string {
    return '
        /* form */.mailpoet_form {}
        /* columns */.mailpoet_column_with_background {  padding: 10px;}/* space between columns */.mailpoet_form_column:not(:first-child) {  margin-left: 20px;}
        /* input wrapper (label + input) */.mailpoet_paragraph {  line-height:20px;  margin-bottom: 20px;}
        /* labels */.mailpoet_segment_label,.mailpoet_text_label,.mailpoet_textarea_label,.mailpoet_select_label,.mailpoet_radio_label,.mailpoet_checkbox_label,.mailpoet_list_label,.mailpoet_date_label {  display:block;  font-weight: normal;}
        /* inputs */.mailpoet_text,.mailpoet_textarea,.mailpoet_select,.mailpoet_date_month,.mailpoet_date_day,.mailpoet_date_year,.mailpoet_date {  display:block;}
        .mailpoet_text,.mailpoet_textarea {  width: 200px;}
        .mailpoet_checkbox {}
        .mailpoet_submit {}
        .mailpoet_divider {}
        .mailpoet_message {}
        .mailpoet_form_loading {  width: 30px;  text-align: center;  line-height: normal;}
        .mailpoet_form_loading > span {  width: 5px;  height: 5px;  background-color: #5b5b5b;}
    ';
  }
}
