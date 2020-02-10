<?php

namespace MailPoet\Form;

use MailPoet\Models\Form;

class FormFactory {

  /** @return Form */
  public function createEmptyForm() {
    $data = [
      'name' => '',
      'body' => [
        [
          'id' => 'email',
          'name' => __('Email', 'mailpoet'),
          'type' => 'text',
          'static' => true,
          'params' => [
            'label' => __('Email', 'mailpoet'),
            'required' => true,
            'label_within' => true,
          ],
        ],
        [
          'id' => 'submit',
          'name' => __('Submit', 'mailpoet'),
          'type' => 'submit',
          'static' => true,
          'params' => [
            'label' => __('Subscribe!', 'mailpoet'),
          ],
        ],
      ],
      'settings' => [
        'on_success' => 'message',
        'success_message' => Form::getDefaultSuccessMessage(),
        'segments' => null,
        'segments_selected_by' => 'admin',
      ],
    ];
    return Form::createOrUpdate($data);
  }

}
