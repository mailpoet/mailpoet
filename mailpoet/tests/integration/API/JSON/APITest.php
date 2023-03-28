<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\API as JSONAPI;
use MailPoet\API\JSON\Endpoint;
use MailPoet\API\JSON\ErrorHandler;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\APITestNamespacedEndpointStubV1;
use MailPoet\API\JSON\v2\APITestNamespacedEndpointStubV2;
use MailPoet\Config\AccessControl;
use MailPoet\DI\ContainerConfigurator;
use MailPoet\DI\ContainerFactory;
use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;

// required to be able to use wp_delete_user()
require_once(ABSPATH . 'wp-admin/includes/user.php');
require_once('APITestNamespacedEndpointStubV1.php');
require_once('APITestNamespacedEndpointStubV2.php');

class APITest extends \MailPoetTest {
  /** @var JSONAPI */
  public $api;
  public $wpUserId;
  /** @var Container */
  private $container;

  /** @var ErrorHandler */
  private $errorHandler;

  /** @var SettingsController */
  private $settings;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function _before() {
    parent::_before();
    // create WP user
    $this->wpUserId = null;
    $wpUserId = wp_create_user('WP User', 'pass', 'wp_user@mailpoet.com');
    if (is_wp_error($wpUserId)) {
      // user already exists
      $this->wpUserId = email_exists('wp_user@mailpoet.com');
    } else {
      $this->wpUserId = $wpUserId;
    }
    $containerFactory = new ContainerFactory(new ContainerConfigurator());
    $this->container = $containerFactory->getConfiguredContainer();
    $this->container->autowire(APITestNamespacedEndpointStubV1::class)->setPublic(true);
    $this->container->autowire(APITestNamespacedEndpointStubV2::class)->setPublic(true);
    $this->container->compile();
    $this->errorHandler = $this->container->get(ErrorHandler::class);
    $this->settings = $this->container->get(SettingsController::class);
    $this->loggerFactory = $this->container->get(LoggerFactory::class);
    $this->api = new JSONAPI(
      $this->container,
      $this->container->get(AccessControl::class),
      $this->errorHandler,
      $this->settings,
      $this->loggerFactory,
      new WPFunctions
    );
  }

  public function testItCallsAPISetupAction() {
    $called = false;
    (new WPFunctions)->addAction(
      'mailpoet_api_setup',
      function($api) use (&$called) {
        $called = true;
        expect($api instanceof JSONAPI)->true();
      }
    );
    $wpStub = Stub::make(new WPFunctions, [
      'wpVerifyNonce' => asCallable(function() {
        return true;
      })]);
    $api = Stub::makeEmptyExcept(
      $this->api,
      'setupAjax',
      [
        'wp' => $wpStub,
        'processRoute' => Stub::makeEmpty(new SuccessResponse),
        'settings' => $this->container->get(SettingsController::class),
      ]
    );
    $api->setupAjax();
    expect($called)->true();
  }

  public function testItCanAddEndpointNamespaces() {
    expect($this->api->getEndpointNamespaces())->count(1);

    $namespace = [
      'name' => 'MailPoet\\Dummy\\Name\\Space',
      'version' => 'v2',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $namespaces = $this->api->getEndpointNamespaces();

    expect($namespaces)->count(2);
    expect($namespaces[$namespace['version']][0])->equals($namespace['name']);
  }

  public function testItReturns400ErrorWhenAPIVersionIsNotSpecified() {
    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'test',
    ];

    $response = $this->api->setRequestData($data, Endpoint::TYPE_POST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItAcceptsAndProcessesAPIVersion() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'test',
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);

    expect($this->api->getRequestedAPIVersion())->equals('v2');
    expect($this->api->getRequestedEndpointClass())->equals(
      'MailPoet\API\JSON\v2\APITestNamespacedEndpointStubV2'
    );
  }

  public function testItCallsAddedEndpoints() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'test',
      'api_version' => 'v1',
      'data' => ['test' => 'data'],
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $this->api->processRoute();

    expect($response->getData()['data'])->equals($data['data']);
  }

  public function testItConvertsExceptionToErrorResponse() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'testBadRequest',
      'api_version' => 'v1',
      'data' => ['test' => 'data'],
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $this->api->processRoute();

    expect($response->errors)->equals([['error' => 'key', 'message' => 'value']]);
  }

  public function testItCallsAddedEndpointsForSpecificAPIVersion() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'testVersion',
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $this->api->processRoute();
    expect($response->getData()['data'])->equals($data['api_version']);
  }

  public function testItValidatesPermissionBeforeProcessingEndpointMethod() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1',
    ];
    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'restricted',
      'api_version' => 'v1',
      'data' => ['test' => 'data'],
    ];
    $api = Stub::make(
      JSONAPI::class,
      [
        'container' => $this->container,
        'validatePermissions' => function($method, $permissions) use ($data) {
          expect($method)->equals($data['method']);
          expect($permissions)->equals(
            [
              'global' => AccessControl::NO_ACCESS_RESTRICTION,
              'methods' => [
                'test' => AccessControl::NO_ACCESS_RESTRICTION,
                'restricted' => AccessControl::PERMISSION_MANAGE_SETTINGS,
              ],
            ]
          );
          return true;
        },
      ]
    );
    $api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $api->processRoute();
    expect($response->getData()['data'])->equals($data['data']);
  }

  public function testItReturnsForbiddenResponseWhenPermissionFailsValidation() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1',
    ];
    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'restricted',
      'api_version' => 'v1',
      'data' => ['test' => 'data'],
    ];
    $accessControl = Stub::make(
      new AccessControl(),
      ['validatePermission' => false]
    );

    $api = new JSONAPI($this->container, $accessControl, $this->errorHandler, $this->settings, $this->loggerFactory, new WPFunctions);
    $api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $api->processRoute();
    expect($response->status)->equals(Response::STATUS_FORBIDDEN);
  }

  public function testItValidatesGlobalPermission() {
    $permissions = [
      'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
    ];

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        }),
      ]
    );

    $api = new JSONAPI($this->container, $accessControl, $this->errorHandler, $this->settings, $this->loggerFactory, new WPFunctions);
    expect($api->validatePermissions(null, $permissions))->false();

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        }),
      ]
    );
    $api = new JSONAPI($this->container, $accessControl, $this->errorHandler, $this->settings, $this->loggerFactory, new WPFunctions);
    expect($api->validatePermissions(null, $permissions))->true();
  }

  public function testItValidatesEndpointMethodPermission() {
    $permissions = [
      'global' => null,
      'methods' => [
        'test' => AccessControl::PERMISSION_MANAGE_SETTINGS,
      ],
    ];

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        }),
      ]
    );

    $api = new JSONAPI($this->container, $accessControl, $this->errorHandler, $this->settings, $this->loggerFactory, new WPFunctions);
    expect($api->validatePermissions('test', $permissions))->false();

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        }),
      ]
    );

    $api = new JSONAPI($this->container, $accessControl, $this->errorHandler, $this->settings, $this->loggerFactory, new WPFunctions);
    expect($api->validatePermissions('test', $permissions))->true();
  }

  public function testItThrowsExceptionWhenInvalidEndpointMethodIsCalled() {
    $namespace = [
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'fakeMethod',
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $this->api->processRoute();

    expect($response->status)->equals(Response::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Invalid API endpoint method.');
  }

  public function testItLogsExceptionToLogTable() {
    $this->settings->set('logging', 'everything');
    $namespace = [
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1',
    ];
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = [
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'testError',
      'api_version' => 'v1',
      'data' => ['test' => 'data'],
    ];
    $this->api->setRequestData($data, Endpoint::TYPE_POST);
    $response = $this->api->processRoute();

    /** @var LogRepository $logRepository */
    $logRepository = $this->container->get(LogRepository::class);
    $logs = $logRepository->findAll();
    expect($logs)->count(1);
    $log = reset($logs);
    $this->assertInstanceOf(LogEntity::class, $log);
    expect($log->getMessage())->stringContainsString('Some Error');
    expect($log->getName())->equals(LoggerFactory::TOPIC_API);
    expect($response->errors)->equals([['error' => 'bad_request', 'message' => 'Some Error']]);
    $this->diContainer->get(SettingsController::class)->set('logging', 'errors');
  }

  public function _after() {
    wp_delete_user($this->wpUserId);
  }
}
