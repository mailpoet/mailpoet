<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FormsResponseBuilderTest extends \MailPoetTest {
  public function testItBuildsForm() {
    $name = 'Form Builder Test';
    $body = [
      'type' => 'text',
      'name' => 'First name',
      'id' => 'first_name',
      'unique' => '1',
      'static' => '0',
      'params' => [
        'label' => 'First name',
      ],
      'position' => '1',
    ];
    $settings = [
      'on_success' => 'message',
      'success_message' => 'Check your inbox or spam folder to confirm your subscription.',
      'segments' => [0 => '1'],
    ];

    $di = ContainerWrapper::getInstance();
    $em = $di->get(EntityManager::class);
    $em->persist($form = new FormEntity($name));
    $form->setStatus(FormEntity::STATUS_ENABLED);
    $form->setStyles('/* form */.mailpoet_form {}');
    $form->setBody($body);
    $form->setSettings($settings);
    $em->flush();

    $responseBuilder = new FormsResponseBuilder();
    $response = $responseBuilder->build($form);
    expect($response['name'])->equals($name);
    expect($response['status'])->equals(FormEntity::STATUS_ENABLED);
    expect($response['body']['name'])->equals($body['name']);
    expect($response['body']['params']['label'])->equals($body['params']['label']);
    expect($response['settings']['success_message'])->equals($settings['success_message']);
    $em->remove($form);
    $em->flush();
  }
}
