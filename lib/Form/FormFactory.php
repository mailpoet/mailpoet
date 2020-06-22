<?php

namespace MailPoet\Form;

use MailPoet\Models\Form;

class FormFactory {
  public function createFormFromTemplate(array $template) {
    if (isset($template['id'])) {
      unset($template['id']);
    }
    return Form::createOrUpdate($template);
  }

  /** @return Form */
  public function createEmptyForm() {
    $data = [
      'name' => '',
      'body' => [
        [
          'id' => 'email',
          'name' => __('Email', 'mailpoet'),
          'type' => 'text',
          'params' => [
            'label' => __('Email', 'mailpoet'),
            'required' => true,
            'label_within' => true,
          ],
          'styles' => [
            'full_width' => true,
          ],
        ],
        [
          'id' => 'submit',
          'name' => __('Submit', 'mailpoet'),
          'type' => 'submit',
          'params' => [
            'label' => __('Subscribe!', 'mailpoet'),
          ],
          'styles' => [
            'full_width' => true,
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
    return $this->createFormFromTemplate($data);
  }
}
