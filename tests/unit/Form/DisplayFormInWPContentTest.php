<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class DisplayFormInWPContentTest extends \MailPoetUnitTest {

  /** @var FormsRepository|MockObject */
  private $repository;

  /** @var WPFunctions|MockObject */
  private $wp;

  /** @var Renderer|MockObject */
  private $renderer;

  /** @var DisplayFormInWPContent */
  private $hook;

  public function _before() {
    parent::_before();
    // settings is needed by renderer
    $settings = $this->createMock(SettingsController::class);
    SettingsController::setInstance($settings);

    $this->repository = $this->createMock(FormsRepository::class);
    $this->wp = $this->createMock(WPFunctions::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->hook = new DisplayFormInWPContent($this->wp, $this->repository, $this->renderer);
  }

  public function testAppendsRenderedFormAfterPostContent() {
    $renderedForm = '<div class="form"></div>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->renderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testDoesNotAppendFormIfDisabled() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsMultipleRenderedFormAfterPostContent() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $form1 = new FormEntity('My Form');
    $form1->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
    ]);
    $form1->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe1'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $form2 = new FormEntity('My Form');
    $form2->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
    ]);
    $form2->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe2'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $renderedForm1 = '<div class="form1"></div>';
    $renderedForm2 = '<div class="form2"></div>';
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form1, $form2]);
    $this->renderer->expects($this->exactly(2))->method('render')->willReturnOnConsecutiveCalls($renderedForm1, $renderedForm2);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->contains($renderedForm1);
    expect($result)->endsWith($renderedForm2);
  }

  public function testDoesNotAppendFormIfNotOnSinglePage() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->repository->expects($this->never())->method('findAll');

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testDoesNotAppendFormIfNotOnPost() {
    $this->wp->expects($this->any())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(false);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsRenderedFormAfterPageContent() {
    $renderedForm = '<div class="form"></div>';
    $this->renderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '1',
      'place_form_bellow_all_posts' => '',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testSetsTransientToImprovePerformance() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp
      ->expects($this->once())
      ->method('setTransient');
    $form1 = new FormEntity('My Form');
    $form2 = new FormEntity('My Form');

    $this->repository->expects($this->once())->method('findAll')->willReturn([$form1, $form2]);

    $this->hook->display('content');
  }

  public function testDoesNotQueryDatabaseIfTransientIsSet() {
    $this->wp->expects($this->any())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp->expects($this->any())->method('getPostType')->willReturn('post');
    $this->wp
      ->expects($this->once())
      ->method('getTransient')
      ->willReturn(['post' => true]);
    $this->repository->expects($this->never())->method('findAll');

    $this->hook->display('content');
  }

}
