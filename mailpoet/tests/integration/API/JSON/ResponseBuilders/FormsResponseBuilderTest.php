<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FormsResponseBuilderTest extends \MailPoetTest {
  /** @var ContainerWrapper */
  protected $container;

  /** @var string */
  protected $formName;

  /** @var array */
  protected $formBody;

  /** @var array */
  protected $formSettings;

  /** @var FormsResponseBuilder */
  private $responseBuilder;

  public function _before() {
    parent::_before();

    $this->container = ContainerWrapper::getInstance();
    $this->entityManager = $this->container->get(EntityManager::class);
    $this->responseBuilder = $this->container->get(FormsResponseBuilder::class);
  }

  public function testItBuildsForm() {
    $form = $this->createForm('Form 1');

    $response = $this->responseBuilder->build($form);

    expect($response['name'])->equals($this->formName);
    expect($response['status'])->equals(FormEntity::STATUS_ENABLED);
    expect($response['body']['name'])->equals($this->formBody['name']);
    expect($response['body']['params']['label'])->equals($this->formBody['params']['label']);
    expect($response['settings']['success_message'])->equals($this->formSettings['success_message']);
  }

  public function testItBuildsFormsForListing() {
    $form1 = $this->createForm('Form 1');
    $form2 = $this->createForm('Form 2');

    $response = $this->responseBuilder->buildForListing([$form1, $form2]);

    expect($response)->count(2);
    expect($response[0]['signups'])->equals(0);
    expect($response[0]['segments'])->equals($this->formSettings['segments']);
  }

  private function createForm($name) {
    $this->formName = $name;
    $this->formBody = [
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
    $this->formSettings = [
      'on_success' => 'message',
      'success_message' => 'Check your inbox or spam folder to confirm your subscription.',
      'segments' => [0 => '1'],
    ];

    $this->entityManager->persist($form = new FormEntity($this->formName));
    $form->setStatus(FormEntity::STATUS_ENABLED);
    $form->setStyles('/* form */.mailpoet_form {}');
    $form->setBody($this->formBody);
    $form->setSettings($this->formSettings);
    $this->entityManager->flush();

    return $form;
  }
}
