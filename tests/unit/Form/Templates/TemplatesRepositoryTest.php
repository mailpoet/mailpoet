<?php

namespace MailPoet\Test\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\TemplateRepository;
use MailPoet\Settings\SettingsController;
use PHPUnit\Framework\MockObject\MockObject;

class TemplatesRepositoryTest extends \MailPoetUnitTest {
  /** @var TemplateRepository */
  private $repository;

  /** @var SettingsController & MockObject */
  private $settingsMock;

  public function _before() {
    parent::_before();
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->repository = new TemplateRepository($this->settingsMock);
  }

  public function testItCanBuildFormEntity() {
    $this->settingsMock->method('get')->willReturn(true);
    $formEntity = $this->repository->getFormEntityForTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(FormEntity::class);
    expect($formEntity->getStyles())->notEmpty();
    expect($formEntity->getBody())->notEmpty();
    expect($formEntity->getSettings())->notEmpty();
  }

  public function testItSetsDefaultMessage() {
    $this->settingsMock->method('get')->willReturnOnConsecutiveCalls([true, false]);
    $formEntity = $this->repository->getFormEntityForTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(FormEntity::class);
    $settings = $formEntity->getSettings() ?? [];
    expect($settings['success_message'])->equals('Check your inbox or spam folder to confirm your subscription.');

    $formEntity = $this->repository->getFormEntityForTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(FormEntity::class);
    $settings = $formEntity->getSettings() ?? [];
    expect($settings['success_message'])->equals('You’ve been successfully subscribed to our newsletter!');
  }
}
