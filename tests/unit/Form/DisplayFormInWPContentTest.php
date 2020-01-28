<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class DisplayFormInWPContentTest extends \MailPoetUnitTest {

  /** @var FormsRepository|\PHPUnit_Framework_MockObject_MockObject */
  private $repository;

  /** @var WPFunctions|\PHPUnit_Framework_MockObject_MockObject */
  private $wp;

  /** @var DisplayFormInWPContent */
  private $hook;

  public function _before() {
    parent::_before();
    // settings is needed by renderer
    $settings = $this->createMock(SettingsController::class);
    SettingsController::setInstance($settings);

    $this->repository = $this->createMock(FormsRepository::class);
    $this->wp = $this->createMock(WPFunctions::class);
    $this->hook = new DisplayFormInWPContent($this->wp, $this->repository);
  }

  public function testAppendsRenderedFormAfterPostContent() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'placeFormBellowAllPages' => '',
      'placeFormBellowAllPosts' => '1',
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
    expect($result)->regExp('/content.*input type="submit"/is');
  }

  public function testDoesNotAppendFormIfDisabled() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'placeFormBellowAllPages' => '',
      'placeFormBellowAllPosts' => '',
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
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $form1 = new FormEntity('My Form');
    $form1->setSettings([
      'segments' => ['3'],
      'placeFormBellowAllPages' => '',
      'placeFormBellowAllPosts' => '1',
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
      'placeFormBellowAllPages' => '',
      'placeFormBellowAllPosts' => '1',
    ]);
    $form2->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe2'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findAll')->willReturn([$form1, $form2]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->regExp('/content.*input.*value="Subscribe1".*input.*value="Subscribe2"/is');
  }

  public function testDoesNotAppendFormIfNotOnSinglePage() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->repository->expects($this->never())->method('findAll');

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testDoesNotAppendFormIfNotOnPost() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->once())->method('isPage')->willReturn(true);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'placeFormBellowAllPages' => '',
      'placeFormBellowAllPosts' => '1',
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
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'placeFormBellowAllPages' => '1',
      'placeFormBellowAllPosts' => '',
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
    expect($result)->regExp('/content.*input type="submit"/is');
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
    $this->wp
      ->expects($this->once())
      ->method('getTransient')
      ->willReturn('true');
    $this->repository->expects($this->never())->method('findAll');

    $this->hook->display('content');
  }

}
