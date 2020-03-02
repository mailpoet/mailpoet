<?php

namespace MailPoet\Form;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Entities\FormEntity;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class DisplayFormInWPContentTest extends \MailPoetUnitTest {

  /** @var FormsRepository & MockObject */
  private $repository;

  /** @var WPFunctions & MockObject */
  private $wp;

  /** @var Renderer & MockObject */
  private $renderer;

  /** @var AssetsController & MockObject */
  private $assetsController;

  /** @var TemplateRenderer & MockObject */
  private $templateRenderer;

  /** @var DisplayFormInWPContent */
  private $hook;

  public function _before() {
    parent::_before();
    $this->repository = $this->createMock(FormsRepository::class);
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->expects($this->any())->method('wpCreateNonce')->willReturn('asdfgh');
    WPFunctions::set($this->wp);
    $this->assetsController = $this->createMock(AssetsController::class);
    $this->templateRenderer = $this->createMock(TemplateRenderer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->renderer->expects($this->any())->method('renderStyles')->willReturn('<style></style>');
    $this->renderer->expects($this->any())->method('renderHTML')->willReturn('<form></form>');
    $this->renderer->expects($this->any())->method('renderFormElementStyles')->willReturn('');
    $this->hook = new DisplayFormInWPContent($this->wp, $this->repository, $this->renderer, $this->assetsController, $this->templateRenderer);
  }

  public function testAppendsRenderedFormAfterPostContent() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
      'success_message' => 'Hello',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testItPassThruNonStringPostContent() {
    $this->wp->expects($this->never())->method('isSingle');
    $this->wp->expects($this->never())->method('isSingular');
    $this->repository->expects($this->never())->method('findAll');
    expect($this->hook->display(null))->null();
    expect($this->hook->display([1,2,3]))->equals([1,2,3]);
    expect($this->hook->display(1))->equals(1);
    expect($this->hook->display(1.1))->equals(1.1);
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
      'success_message' => 'Hello',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsMultipleRenderedFormAfterPostContent() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $form1 = new FormEntity('My Form');
    $form1->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '',
      'place_form_bellow_all_posts' => '1',
      'success_message' => 'Hello',
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
      'success_message' => 'Hello',
    ]);
    $form2->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe2'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form1, $form2]);
    $formHtml1 = '<form id="test-form-1"></form>';
    $formHtml2 = '<form id="test-form-2"></form>';
    $this->templateRenderer->expects($this->exactly(2))->method('render')->willReturnOnConsecutiveCalls($formHtml1, $formHtml2);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($formHtml1 . $formHtml2);
  }

  public function testDoesNotAppendFormIfNotOnSinglePage() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->repository->expects($this->never())->method('findBy');
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
      'success_message' => 'Hello',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsRenderedFormAfterPageContent() {
    $formHtml = '<form id="test-form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($formHtml);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'place_form_bellow_all_pages' => '1',
      'place_form_bellow_all_posts' => '',
      'success_message' => 'Hello',
    ]);
    $form->setBody([[
      'type' => 'submit',
      'params' => ['label' => 'Subscribe!'],
      'id' => 'submit',
      'name' => 'Submit',
    ]]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($formHtml);
  }

  public function testSetsTransientToImprovePerformance() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp
      ->expects($this->once())
      ->method('setTransient');
    $form1 = new FormEntity('My Form');
    $form2 = new FormEntity('My Form');

    $this->repository->expects($this->once())->method('findBy')->willReturn([$form1, $form2]);

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
    $this->repository->expects($this->never())->method('findBy');

    $this->hook->display('content');
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions());
  }
}
