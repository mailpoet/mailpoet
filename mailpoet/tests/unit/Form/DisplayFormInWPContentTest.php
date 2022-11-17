<?php declare(strict_types = 1);

namespace MailPoet\Form;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\SubscriberSubscribeController;
use MailPoet\WooCommerce\Helper as WCHelper;
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

  /** @var SubscribersRepository & MockObject */
  private $subscribersRepository;

  /** @var SubscriberSubscribeController & MockObject */
  private $subscriberSubscribeController;

  /** @var WCHelper & MockObject */
  private $woocommerceHelper;

  // fix for method return override
  private $applyFiltersValue = false;

  public function _before() {
    parent::_before();
    if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
    $this->repository = $this->createMock(FormsRepository::class);
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->expects($this->any())->method('inTheLoop')->willReturn(true);
    $this->wp->expects($this->any())->method('isMainQuery')->willReturn(true);
    $this->wp->expects($this->any())->method('wpCreateNonce')->willReturn('asdfgh');
    $this->wp->expects($this->any())->method('applyFilters')->will( $this->returnCallback(function () { return $this->applyFiltersValue;

    } ) );
    WPFunctions::set($this->wp);
    $this->assetsController = $this->createMock(AssetsController::class);
    $this->templateRenderer = $this->createMock(TemplateRenderer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->renderer->expects($this->any())->method('renderStyles')->willReturn('<style></style>');
    $this->renderer->expects($this->any())->method('renderHTML')->willReturn('<form></form>');
    $this->subscribersRepository = $this->createMock( SubscribersRepository::class);
    $this->subscriberSubscribeController = $this->createMock(SubscriberSubscribeController::class);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->hook = new DisplayFormInWPContent(
      $this->wp,
      $this->repository,
      $this->renderer,
      $this->assetsController,
      $this->templateRenderer,
      $this->subscriberSubscribeController,
      $this->subscribersRepository,
      $this->woocommerceHelper
    );
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
      'form_placement' => ['below_posts' => ['enabled' => '1', 'pages' => ['all' => ''], 'posts' => ['all' => '1']],],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificPost() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '1', 'pages' => ['all' => ''], 'posts' => ['all' => '', 'selected' => ['1']]],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificCategory() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->wp->expects($this->any())->method('hasCategory')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => ''],
          'posts' => ['all' => '', 'selected' => ['2']],
          'categories' => ['2'],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificWoocommerceCategory() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->wp->expects($this->any())->method('hasCategory')->willReturn(false);
    $this->wp->expects($this->any())->method('hasTerm')->with(['2'], 'product_cat')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => ''],
          'posts' => ['all' => '', 'selected' => ['2']],
          'categories' => ['2'],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificTag() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->wp->expects($this->any())->method('hasCategory')->willReturn(false);
    $this->wp->expects($this->any())->method('hasTag')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => ''],
          'posts' => ['all' => '', 'selected' => ['2']],
          'categories' => ['2'],
          'tags' => ['3'],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificWooCommerceTag() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->wp->expects($this->any())->method('hasCategory')->willReturn(false);
    $this->wp->expects($this->any())->method('hasTag')->willReturn(false);
    $this->wp->expects($this->any())->method('hasTerm')->with(['3'], 'product_tag')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => ''],
          'posts' => ['all' => '', 'selected' => ['2']],
          'tags' => ['3'],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormAfterOnASpecificPage() {
    $renderedForm = '<form class="form"></form>';
    $this->wp->expects($this->any())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(false);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '1', 'pages' => ['all' => '', 'selected' => ['1']], 'posts' => ['all' => '']],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
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
      'form_placement' => [
        'below_posts' => ['enabled' => '', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
      ],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testDoesNotAppendFormIfEnabledAndPlacementIsDisabled() {
    $this->wp->expects($this->once())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(true);
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => ['below_posts' => ['enabled' => '1', 'pages' => ['all' => ''], 'posts' => ['all' => '']]],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->equals('content');
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
      'form_placement' => ['below_posts' => ['enabled' => '1', 'pages' => ['all' => ''], 'posts' => ['all' => '1']],],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsRenderedFormOnWoocommerceShopListingPage() {
    $renderedForm = '<form class="form"></form>';

    $this->applyFiltersValue = true;
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(false);
    $this->wp->expects($this->any())->method('isArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('isPostTypeArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->woocommerceHelper->expects($this->any())->method('wcGetPageId')->willReturn(1);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);

    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => '', 'selected' => ['1']],
          'posts' => ['all' => ''],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testAppendsRenderedFormOnWoocommerceShopListingPageWhenAllPagesIsSelected() {
    $renderedForm = '<form class="form"></form>';

    $this->applyFiltersValue = true;
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(false);
    $this->wp->expects($this->any())->method('isArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('isPostTypeArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->woocommerceHelper->expects($this->any())->method('wcGetPageId')->willReturn(1);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($renderedForm);

    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => '1'],
          'posts' => ['all' => ''],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);
    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($renderedForm);
  }

  public function testItDoesNotAppendsFormOnWoocommerceShopListingPageWhenPageIsNotSelected() {
    $renderedForm = '<form class="form"></form>';

    $this->applyFiltersValue = true;
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isSingular')->willReturn(false);
    $this->wp->expects($this->any())->method('isArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('isPostTypeArchive')->willReturn(true);
    $this->wp->expects($this->any())->method('getPost')->willReturn(['ID' => 1]);
    $this->woocommerceHelper->expects($this->any())->method('wcGetPageId')->willReturn(1);
    $this->assetsController->expects($this->never())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->never())->method('render')->willReturn($renderedForm);

    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => [
          'enabled' => '1',
          'pages' => ['all' => '', 'selected' => ['5']],
          'posts' => ['all' => ''],
        ],
      ],
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
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
      'form_placement' => [
        'below_posts' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '']],
      ],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
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

    $this->repository->expects($this->once())->method('findBy')->willReturn([]);

    $this->hook->display('content');
  }

  public function testDoesNotQueryDatabaseIfTransientIsSet() {
    $this->wp->expects($this->any())->method('isSingle')->willReturn(true);
    $this->wp->expects($this->any())->method('isPage')->willReturn(false);
    $this->wp->expects($this->any())->method('getPostType')->willReturn('post');
    $this->wp
      ->expects($this->once())
      ->method('getTransient')
      ->willReturn('1');
    $this->repository->expects($this->never())->method('findBy');

    $this->hook->display('content');
  }

  public function testAppendsRenderedPopupForm() {
    $formHtml = '<form id="test-form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($formHtml);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
        'popup' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '']],
      ],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($formHtml);
  }

  public function testDoesNotAppendPopupFormIfLoggedInAndSubscribed() {
    $formHtml = '<form id="test-form"></form>';
    $subscriber = new SubscriberEntity();
    $this->subscribersRepository->expects($this->once())->method('getCurrentWPUser')->willReturn($subscriber);
    $this->subscriberSubscribeController->expects($this->once())->method('isSubscribedToAnyFormSegments')->willReturn(true);
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->assetsController->expects($this->never())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->never())->method('render')->willReturn($formHtml);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
        'popup' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '']],
      ],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->equals('content');
  }

  public function testAppendsPopupFormIfLoggedInAndNotSubscribed() {
    $formHtml = '<form id="test-form"></form>';
    $subscriber = new SubscriberEntity();
    $this->subscribersRepository->expects($this->any())->method('getCurrentWPUser')->willReturn($subscriber);
    $this->subscriberSubscribeController->expects($this->any())->method('isSubscribedToAnyFormSegments')->willReturn(false);
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($formHtml);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
        'popup' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '']],
      ],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->endsWith($formHtml);
  }

  public function testAppendsRenderedFixedBarForm() {
    $formHtml = '<form id="test-form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->assetsController->expects($this->once())->method('setupFrontEndDependencies');
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($formHtml);
    $this->wp
      ->expects($this->never())
      ->method('setTransient');
    $form = new FormEntity('My Form');
    $form->setSettings([
      'segments' => ['3'],
      'form_placement' => [
        'below_posts' => ['enabled' => '', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
        'popup' => ['enabled' => '1', 'pages' => ['all' => ''], 'posts' => ['all' => '']],
        'fixed_bar' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '1']]],
      'success_message' => 'Hello',
    ]);
    $form->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($formHtml);
  }

  public function testOnlyOneFormInEachCategory() {
    $formHtml = '<form id="test-form"></form>';
    $this->wp->expects($this->once())->method('isSingle')->willReturn(false);
    $this->wp->expects($this->any())->method('isPage')->willReturn(true);
    $this->templateRenderer->expects($this->once())->method('render')->willReturn($formHtml);
    $form1 = new FormEntity('My Form');
    $form1->setSettings([
      'segments' => ['3'],
      'form_placement' => ['fixed_bar' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '1']]],
      'success_message' => 'Hello',
    ]);
    $form1->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $form2 = new FormEntity('My Form');
    $form2->setSettings([
      'segments' => ['3'],
      'form_placement' => ['fixed_bar' => ['enabled' => '1', 'pages' => ['all' => '1'], 'posts' => ['all' => '1']]],
      'success_message' => 'Hello',
    ]);
    $form2->setBody([['type' => 'submit', 'params' => ['label' => 'Subscribe!'], 'id' => 'submit', 'name' => 'Submit']]);
    $this->repository->expects($this->once())->method('findBy')->willReturn([$form1, $form2]);

    $result = $this->hook->display('content');
    expect($result)->notEquals('content');
    expect($result)->endsWith($formHtml);
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions());
  }
}
