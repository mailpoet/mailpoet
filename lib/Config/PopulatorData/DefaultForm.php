<?php

namespace MailPoet\Config\PopulatorData;

use MailPoet\Form\Util\Styles;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\WP\Functions as WPFunctions;

class DefaultForm {
  /** @var Styles */
  private $form_styles;

  public function __construct(Styles $form_styles) {
    $this->form_styles = $form_styles;
  }

  public function getName() {
    return WPFunctions::get()->_x('My first form', 'default name of form (GDPR friendly) to capture emails', 'mailpoet');
  }

  public function getBody() {
    return [
      [
        'type' => 'text',
        'name' => WPFunctions::get()->_x('First name', 'Form label', 'mailpoet'),
        'id' => 'first_name',
        'unique' => '1',
        'static' => '0',
        'params' => ['label' => WPFunctions::get()->_x('First name', 'Form label', 'mailpoet')],
        'position' => '1',
      ],
      [
        'type' => 'text',
        'name' => WPFunctions::get()->_x('Email', 'Form label', 'mailpoet'),
        'id' => 'email',
        'unique' => '0',
        'static' => '1',
        'params' => ['label' => WPFunctions::get()->_x('Email', 'Form label', 'mailpoet'), 'required' => 'true'],
        'position' => '2',
      ],
      [
        'type' => 'submit',
        'name' => WPFunctions::get()->_x('Submit', 'Form label', 'mailpoet'),
        'id' => 'submit',
        'unique' => '0',
        'static' => '1',
        'params' => ['label' => WPFunctions::get()->_x('Subscribe!', 'Form label', 'mailpoet')],
        'position' => '3',
      ],
    ];
  }

  public function getSettings(Segment $default_segment) {
    return [
      'segments' => [$default_segment->id()],
      'on_success' => 'message',
      'success_message' => Form::getDefaultSuccessMessage(),
      'success_page' => '5',
      'segments_selected_by' => 'admin',
    ];
  }

  public function getStyles() {
    return $this->form_styles->getDefaultStyles();
  }
}
