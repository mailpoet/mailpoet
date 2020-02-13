<?php

namespace MailPoet\Test\Form;

use MailPoet\Form\Widget;
use MailPoet\Models\Form;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

class WidgetTest extends \MailPoetTest {
  public function testItAllowsModifyingRenderedFormWidgetViaHook() {
    $form = Form::createOrUpdate(
      [
        'name' => 'Test Form',
        'body' => [
          [
            'type' => 'text',
            'id' => 'email',
          ],
        ],
        'settings' => [
          'success_message' => 'Hello!',
        ],
      ]
    );
    $formWidget = new Widget();

    // form target is set to _self by default
    $renderedFormWidget = $formWidget->widget(
      [],
      [
        'form' => $form->id,
        'form_type' => 'html',
      ]
    );
    $DOM = pQuery::parseStr($renderedFormWidget);
    expect($DOM->query('form')->attr('target'))->equals('_self');

    // form target is modified to _top via hook
    (new WPFunctions)->addFilter(
      'mailpoet_form_widget_post_process',
      function($form) {
        $form = str_replace('target="_self"', 'target="_top"', $form);
        return $form;
      }
    );
    $renderedFormWidget = $formWidget->widget(
      [],
      [
        'form' => $form->id,
        'form_type' => 'html',
      ]
    );
    $DOM = pQuery::parseStr($renderedFormWidget);
    expect($DOM->query('form')->attr('target'))->equals('_top');
  }
}
