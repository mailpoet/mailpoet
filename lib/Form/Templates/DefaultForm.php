<?php

namespace MailPoet\Form\Templates;

/**
 * Template for default form created on plugin activation
 */
class DefaultForm extends InitialForm implements Template {
  public function getName(): string {
    return _x('My First Form', 'default name of form (GDPR friendly) to capture emails', 'mailpoet');
  }

  public function getBody(): array {
    return [
      [
        'type' => 'text',
        'name' => _x('First name', 'Form label', 'mailpoet'),
        'id' => 'first_name',
        'unique' => '1',
        'static' => '0',
        'params' => ['label' => _x('First name', 'Form label', 'mailpoet')],
        'position' => '1',
      ],
      [
        'type' => 'text',
        'name' => _x('Email', 'Form label', 'mailpoet'),
        'id' => 'email',
        'unique' => '0',
        'static' => '1',
        'params' => ['label' => _x('Email', 'Form label', 'mailpoet'), 'required' => 'true'],
        'position' => '2',
      ],
      [
        'type' => 'submit',
        'name' => _x('Submit', 'Form label', 'mailpoet'),
        'id' => 'submit',
        'unique' => '0',
        'static' => '1',
        'params' => ['label' => _x('Subscribe!', 'Form label', 'mailpoet')],
        'position' => '3',
      ],
    ];
  }
}
