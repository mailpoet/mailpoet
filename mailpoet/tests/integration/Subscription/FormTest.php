<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\API\JSON\API;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Form;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Util\Url as UrlHelper;
use MailPoet\WP\Functions as WPFunctions;

class FormTest extends \MailPoetTest {
  public $post;
  public $requestData;
  public $form;
  public $testEmail;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
    $this->testEmail = 'test@example.com';
    $segmentFactory = new SegmentFactory();
    $segment = $segmentFactory->withName('Test segment')->create();
    $this->form = new FormEntity('Test form');
    $this->form->setBody([
      [
        'type' => 'text',
        'id' => 'email',
      ],
    ]);
    $this->form->setSettings([
      'segments' => [$segment->getId()],
    ]);
    $this->entityManager->persist($this->form);
    $this->entityManager->flush();
    $obfuscator = new FieldNameObfuscator(WPFunctions::get());
    $obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->requestData = [
      'action' => 'mailpoet_subscription_form',
      'data' => [
        'form_id' => $this->form->getId(),
        $obfuscatedEmail => $this->testEmail,
        // Human-like signals so the disabled-CAPTCHA baseline doesn't
        // escalate this submission to an inline CAPTCHA challenge.
        'behavioral_signals' => [
          'time_ms' => 5000,
          'mm_count' => 10,
          'kd_count' => 10,
          'scroll_count' => 0,
          'focus_count' => 1,
          'touch' => false,
        ],
      ],
      'token' => WPFunctions::get()->wpCreateNonce('mailpoet_token'),
      'api_version' => 'v1',
      'endpoint' => 'subscribers',
      'mailpoet_method' => 'subscribe',
    ];
    $this->post = wp_insert_post(
      [
        'post_title' => 'Sample Post',
        'post_content' => 'contents',
        'post_status' => 'publish',
      ]
    );
    $this->settings->set('signup_confirmation.enabled', false);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItSubscribesAndRedirectsBackWithSuccessResponse() {
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $formController = new Form(ContainerWrapper::getInstance()->get(API::class), $urlHelper);
    $result = $formController->onSubmit($this->requestData);
    verify($this->subscribersRepository->findOneBy(['email' => $this->testEmail]))->notEmpty();
    verify($result['mailpoet_success'])->equals($this->form->getId());
    verify($result['mailpoet_error'])->null();
  }

  public function testItSubscribesAndRedirectsToCustomUrlWithSuccessResponse() {
    // update form with a redirect setting
    $form = $this->form;
    $formSettings = $form->getSettings();
    $formSettings['on_success'] = 'page';
    $formSettings['success_page'] = $this->post;
    $form->setSettings($formSettings);
    $this->entityManager->flush();
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectTo' => function($params) {
        return $params;
      },
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $formController = new Form(ContainerWrapper::getInstance()->get(API::class), $urlHelper);
    $result = $formController->onSubmit($this->requestData);
    verify($this->subscribersRepository->findOneBy(['email' => $this->testEmail]))->notEmpty();
    verify($result)->stringMatchesRegExp('/http.*?sample-post|http.*?\?p=\d+/i');
  }

  public function testItDoesNotSubscribeAndRedirectsBackWithErrorResponse() {
    // clear subscriber email so that subscription fails
    $requestData = $this->requestData;
    $requestData['data']['email'] = false;
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $formController = new Form(ContainerWrapper::getInstance()->get(API::class), $urlHelper);
    $result = $formController->onSubmit($requestData);
    verify($this->subscribersRepository->findAll())->empty();
    verify($result['mailpoet_error'])->equals($this->form->getId());
    verify($result['mailpoet_success'])->null();
  }

  public function testItDoesNotSubscribeAndRedirectsToRedirectUrlIfPresent() {
    $redirectUrl = 'http://test/';
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectTo' => function($params) {
        return $params;
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'processRoute' => function() use ($redirectUrl) {
        return new ErrorResponse([], ['redirect_url' => $redirectUrl], Response::STATUS_BAD_REQUEST);
      },
    ], $this);
    $formController = new Form($api, $urlHelper);
    $result = $formController->onSubmit($this->requestData);
    verify($result)->equals($redirectUrl);
  }

  public function testItRedirectsBackWhenRequestHasNoFormData() {
    // A crawler hitting admin-post.php?action=mailpoet_subscription_form directly
    // sends no 'data' payload. The guard must redirect back without invoking the
    // JSON API, otherwise processRoute() throws "Invalid API endpoint." and the
    // exception ends up in the WordPress debug log.
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params = []) {
        return 'redirected-back';
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'setRequestData' => Stub\Expected::never(),
      'processRoute' => Stub\Expected::never(),
    ], $this);
    $formController = new Form($api, $urlHelper);
    $result = $formController->onSubmit(['action' => 'mailpoet_subscription_form']);
    verify($result)->equals('redirected-back');
  }

  public function testItRedirectsBackWhenActionDoesNotMatch() {
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params = []) {
        return 'redirected-back';
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'setRequestData' => Stub\Expected::never(),
      'processRoute' => Stub\Expected::never(),
    ], $this);
    $formController = new Form($api, $urlHelper);
    $result = $formController->onSubmit(['action' => 'unrelated_action', 'data' => ['form_id' => 1]]);
    verify($result)->equals('redirected-back');
  }

  public function testItRedirectsBackWhenFormDataIsMalformed() {
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params = []) {
        return 'redirected-back';
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'setRequestData' => Stub\Expected::never(),
      'processRoute' => Stub\Expected::never(),
    ], $this);
    $formController = new Form($api, $urlHelper);
    $requestData = $this->requestData;
    $requestData['data'] = 'not-an-array';

    $result = $formController->onSubmit($requestData);

    verify($result)->equals('redirected-back');
  }

  public function testItRedirectsBackWhenApiRouteFieldsAreMissing() {
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params = []) {
        return 'redirected-back';
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'setRequestData' => Stub\Expected::never(),
      'processRoute' => Stub\Expected::never(),
    ], $this);
    $formController = new Form($api, $urlHelper);
    $requestData = $this->requestData;
    unset($requestData['endpoint']);

    $result = $formController->onSubmit($requestData);

    verify($result)->equals('redirected-back');
  }

  public function testItAcceptsMethodAliasForApiRoute() {
    $urlHelper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params = []) {
        return $params;
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'setRequestData' => Stub\Expected::once(),
      'processRoute' => function() {
        return new SuccessResponse();
      },
    ], $this);
    $formController = new Form($api, $urlHelper);
    $requestData = $this->requestData;
    $requestData['method'] = 'subscribe';
    unset($requestData['mailpoet_method']);

    $result = $formController->onSubmit($requestData);

    verify($result['mailpoet_success'])->equals($this->form->getId());
    verify($result['mailpoet_error'])->null();
  }

  public function _after() {
    parent::_after();
    wp_delete_post($this->post);
  }
}
