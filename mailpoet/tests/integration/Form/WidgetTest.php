<?php declare(strict_types = 1);

namespace MailPoet\Test\Form;

use MailPoet\Captcha\FormRenderStamp;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Widget;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

class WidgetTest extends \MailPoetTest {
  public function testItAllowsModifyingRenderedFormWidgetViaHook() {
    $form = new FormEntity('Test Form');
    $form->setBody([
      [
        'type' => 'text',
        'id' => 'email',
      ],
    ]);
    $form->setSettings([
      'success_message' => 'Hello!',
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();

    $formWidget = new Widget();

    // form target is set to _self by default
    $renderedFormWidget = $formWidget->widget(
      [],
      [
        'form' => $form->getId(),
        'form_type' => 'html',
      ]
    );
    $DOM = pQuery::parseStr($renderedFormWidget);
    verify($DOM->query('form')->attr('target'))->equals('_self');

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
        'form' => $form->getId(),
        'form_type' => 'html',
      ]
    );
    $DOM = pQuery::parseStr($renderedFormWidget);
    verify($DOM->query('form')->attr('target'))->equals('_top');
  }

  public function testItRendersFormWithValidRenderStamp() {
    $form = new FormEntity('Test Form');
    $form->setBody([['type' => 'text', 'id' => 'email']]);
    $form->setSettings(['success_message' => 'Hello!']);
    $this->entityManager->persist($form);
    $this->entityManager->flush();

    $rendered = (new Widget())->widget([], ['form' => $form->getId(), 'form_type' => 'html']);

    $stampInput = pQuery::parseStr($rendered)->query('input[name="data[form_stamp]"]');
    verify($stampInput->attr('type'))->equals('hidden');
    $stamp = ContainerWrapper::getInstance()->get(FormRenderStamp::class);
    verify($stamp->elapsedSeconds($stampInput->attr('value')))->notNull();
  }
}
