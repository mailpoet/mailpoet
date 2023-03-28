<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\API\JSON\API;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
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
    expect($this->subscribersRepository->findOneBy(['email' => $this->testEmail]))->notEmpty();
    expect($result['mailpoet_success'])->equals($this->form->getId());
    expect($result['mailpoet_error'])->null();
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
    expect($this->subscribersRepository->findOneBy(['email' => $this->testEmail]))->notEmpty();
    expect($result)->regExp('/http.*?sample-post|http.*?\?p=\d+/i');
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
    expect($this->subscribersRepository->findAll())->isEmpty();
    expect($result['mailpoet_error'])->equals($this->form->getId());
    expect($result['mailpoet_success'])->null();
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
    expect($result)->equals($redirectUrl);
  }

  public function _after() {
    wp_delete_post($this->post);
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
