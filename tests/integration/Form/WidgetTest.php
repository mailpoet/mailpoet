<?php

namespace MailPoet\Test\Form;

use MailPoet\Form\Widget;
use MailPoet\Models\Form;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Hooks;

class WidgetTest extends \MailPoetTest {
  function testItAllowsModifyingRenderedFormWidgetViaHook() {
    $form = Form::createOrUpdate(
      array(
        'name' => 'Test Form',
        'body' => array(
          array(
            'type' => 'text',
            'id' => 'email',
          )
        )
      )
    );
    $form_widget = new Widget();

    // form target is set to _self by default
    $rendered_form_widget = $form_widget->widget(
      array(),
      array(
        'form' => $form->id,
        'form_type' => 'html'
      )
    );
    $DOM = pQuery::parseStr($rendered_form_widget);
    expect($DOM->query('form')->attr('target'))->equals('_self');

    // form target is modified to _top via hook
    Hooks::addFilter(
      'mailpoet_form_widget_post_process',
      function($form) {
        $form = str_replace('target="_self"', 'target="_top"', $form);
        return $form;
      }
    );
    $rendered_form_widget = $form_widget->widget(
      array(),
      array(
        'form' => $form->id,
        'form_type' => 'html'
      )
    );
    $DOM = pQuery::parseStr($rendered_form_widget);
    expect($DOM->query('form')->attr('target'))->equals('_top');
  }
}