<?php

namespace MailPoet\API\JSON;

use MailPoet\API\JSON\Endpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Tracy\ApiPanel\ApiPanel;
use MailPoet\Tracy\DIPanel\DIPanel;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Psr\Container\ContainerInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class API {
  private $requestApiVersion;
  private $requestEndpoint;
  private $requestMethod;
  private $requestToken;
  private $requestType;
  private $requestEndpointClass;
  private $requestData = [];
  private $endpointNamespaces = [];
  private $availableApiVersions = [
      'v1',
  ];
  /** @var ContainerInterface */
  private $container;

  /** @var AccessControl */
  private $accessControl;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  const CURRENT_VERSION = 'v1';

  public function __construct(
    ContainerInterface $container,
    AccessControl $accessControl,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->container = $container;
    $this->accessControl = $accessControl;
    $this->settings = $settings;
    $this->wp = $wp;
    foreach ($this->availableApiVersions as $availableApiVersion) {
      $this->addEndpointNamespace(
        sprintf('%s\%s', __NAMESPACE__, $availableApiVersion),
        $availableApiVersion
      );
    }
  }

  public function init() {
     // admin security token and API version
    WPFunctions::get()->addAction(
      'admin_head',
      [$this, 'setTokenAndAPIVersion']
    );

    // ajax (logged in users)
    WPFunctions::get()->addAction(
      'wp_ajax_mailpoet',
      [$this, 'setupAjax']
    );

    // ajax (logged out users)
    WPFunctions::get()->addAction(
      'wp_ajax_nopriv_mailpoet',
      [$this, 'setupAjax']
    );
  }

  public function setupAjax() {
    $this->wp->doAction('mailpoet_api_setup', [$this]);

    if (isset($_POST['api_version'])) {
      $this->setRequestData($_POST, Endpoint::TYPE_POST);
    } else {
      $this->setRequestData($_GET, Endpoint::TYPE_GET);
    }

    $ignoreToken = (
      $this->settings->get('captcha.type') != Captcha::TYPE_DISABLED &&
      $this->requestEndpoint === 'subscribers' &&
      $this->requestMethod === 'subscribe'
    );

    if (!$ignoreToken && $this->checkToken() === false) {
      $errorMessage = WPFunctions::get()->__("Sorry, but we couldn't connect to the MailPoet server. Please refresh the web page and try again.", 'mailpoet');
      $errorResponse = $this->createErrorResponse(Error::UNAUTHORIZED, $errorMessage, Response::STATUS_UNAUTHORIZED);
      return $errorResponse->send();
    }

    $response = $this->processRoute();
    $response->send();
  }

  public function setRequestData($data, $requestType) {
    $this->requestApiVersion = !empty($data['api_version']) ? $data['api_version'] : false;

    $this->requestEndpoint = isset($data['endpoint'])
      ? Helpers::underscoreToCamelCase(trim($data['endpoint']))
      : null;

    // JS part of /wp-admin/customize.php does not like a 'method' field in a form widget
    $methodParamName = isset($data['mailpoet_method']) ? 'mailpoet_method' : 'method';
    $this->requestMethod = isset($data[$methodParamName])
      ? Helpers::underscoreToCamelCase(trim($data[$methodParamName]))
      : null;
    $this->requestType = $requestType;

    $this->requestToken = isset($data['token'])
      ? trim($data['token'])
      : null;

    if (!$this->requestEndpoint || !$this->requestMethod || !$this->requestApiVersion) {
      $errorMessage = WPFunctions::get()->__('Invalid API request.', 'mailpoet');
      $errorResponse = $this->createErrorResponse(Error::BAD_REQUEST, $errorMessage, Response::STATUS_BAD_REQUEST);
      return $errorResponse;
    } else if (!empty($this->endpointNamespaces[$this->requestApiVersion])) {
      foreach ($this->endpointNamespaces[$this->requestApiVersion] as $namespace) {
        $endpointClass = sprintf(
          '%s\%s',
          $namespace,
          ucfirst($this->requestEndpoint)
        );
        if ($this->container->has($endpointClass)) {
          $this->requestEndpointClass = $endpointClass;
          break;
        }
      }
      $this->requestData = isset($data['data'])
        ? WPFunctions::get()->stripslashesDeep($data['data'])
        : [];

      // remove reserved keywords from data
      if (is_array($this->requestData) && !empty($this->requestData)) {
        // filter out reserved keywords from data
        $reservedKeywords = [
          'token',
          'endpoint',
          'method',
          'api_version',
          'mailpoet_method', // alias of 'method'
          'mailpoet_redirect',
        ];
        $this->requestData = array_diff_key(
          $this->requestData,
          array_flip($reservedKeywords)
        );
      }
    }
  }

  public function processRoute() {
    try {
      if (empty($this->requestEndpointClass) ||
        !$this->container->has($this->requestEndpointClass)
      ) {
        throw new \Exception(__('Invalid API endpoint.', 'mailpoet'));
      }

      $endpoint = $this->container->get($this->requestEndpointClass);
      if (!method_exists($endpoint, $this->requestMethod)) {
        throw new \Exception(__('Invalid API endpoint method.', 'mailpoet'));
      }

      if (!$endpoint->isMethodAllowed($this->requestMethod, $this->requestType)) {
        throw new \Exception(__('HTTP request method not allowed.', 'mailpoet'));
      }

      if (class_exists(Debugger::class)) {
        ApiPanel::init($endpoint, $this->requestMethod, $this->requestData);
        DIPanel::init();
      }

      // check the accessibility of the requested endpoint's action
      // by default, an endpoint's action is considered "private"
      if (!$this->validatePermissions($this->requestMethod, $endpoint->permissions)) {
        $errorMessage = WPFunctions::get()->__('You do not have the required permissions.', 'mailpoet');
        $errorResponse = $this->createErrorResponse(Error::FORBIDDEN, $errorMessage, Response::STATUS_FORBIDDEN);
        return $errorResponse;
      }
      $response = $endpoint->{$this->requestMethod}($this->requestData);
      return $response;
    } catch (\Exception $e) {
      if (class_exists(Debugger::class) && Debugger::$logDirectory) {
        Debugger::log($e, ILogger::EXCEPTION);
      }
      $errorMessage = $e->getMessage();
      $errorResponse = $this->createErrorResponse(Error::BAD_REQUEST, $errorMessage, Response::STATUS_BAD_REQUEST);
      return $errorResponse;
    }
  }

  public function validatePermissions($requestMethod, $permissions) {
    // validate method permission if defined, otherwise validate global permission
    return(!empty($permissions['methods'][$requestMethod])) ?
      $this->accessControl->validatePermission($permissions['methods'][$requestMethod]) :
      $this->accessControl->validatePermission($permissions['global']);
  }

  public function checkToken() {
    return WPFunctions::get()->wpVerifyNonce($this->requestToken, 'mailpoet_token');
  }

  public function setTokenAndAPIVersion() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "%s";';
    $global .= 'var mailpoet_api_version = "%s";';
    $global .= '</script>';
    echo sprintf(
      $global,
      Security::generateToken(),
      self::CURRENT_VERSION
    );
  }

  public function addEndpointNamespace($namespace, $version) {
    if (!empty($this->endpointNamespaces[$version][$namespace])) return;
    $this->endpointNamespaces[$version][] = $namespace;
  }

  public function getEndpointNamespaces() {
    return $this->endpointNamespaces;
  }

  public function getRequestedEndpointClass() {
    return $this->requestEndpointClass;
  }

  public function getRequestedAPIVersion() {
    return $this->requestApiVersion;
  }

  public function createErrorResponse($errorType, $errorMessage, $responseStatus) {
    $errorResponse = new ErrorResponse(
      [
        $errorType => $errorMessage,
      ],
      [],
      $responseStatus
    );
    return $errorResponse;
  }
}
