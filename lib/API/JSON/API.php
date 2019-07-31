<?php
namespace MailPoet\API\JSON;

use MailPoet\Config\AccessControl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Tracy\ApiPanel\ApiPanel;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use Tracy\Debugger;

if (!defined('ABSPATH')) exit;

class API {
  private $_request_api_version;
  private $_request_endpoint;
  private $_request_method;
  private $_request_token;
  private $_request_endpoint_class;
  private $_request_data = [];
  private $_endpoint_namespaces = [];
  private $_available_api_versions = [
      'v1',
  ];
  /** @var ContainerInterface */
  private $container;

  /** @var AccessControl */
  private $access_control;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  const CURRENT_VERSION = 'v1';


  function __construct(
    ContainerInterface $container,
    AccessControl $access_control,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->container = $container;
    $this->access_control = $access_control;
    $this->settings = $settings;
    $this->wp = $wp;
    foreach ($this->_available_api_versions as $available_api_version) {
      $this->addEndpointNamespace(
        sprintf('%s\%s', __NAMESPACE__, $available_api_version),
        $available_api_version
      );
    }
  }

  function init() {
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

  function setupAjax() {
    $this->wp->doAction('mailpoet_api_setup', [$this]);
    $this->setRequestData($_POST);

    $ignoreToken = (
      $this->settings->get('captcha.type') != Captcha::TYPE_DISABLED &&
      $this->_request_endpoint === 'subscribers' &&
      $this->_request_method === 'subscribe'
    );

    if (!$ignoreToken && $this->checkToken() === false) {
      $error_message = WPFunctions::get()->__("Sorry, but we couldn't connect to the MailPoet server. Please refresh the web page and try again.", 'mailpoet');
      $error_response = $this->createErrorResponse(Error::UNAUTHORIZED, $error_message, Response::STATUS_UNAUTHORIZED);
      return $error_response->send();
    }

    $response = $this->processRoute();
    $response->send();
  }

  function setRequestData($data) {
    $this->_request_api_version = !empty($data['api_version']) ? $data['api_version'] : false;

    $this->_request_endpoint = isset($data['endpoint'])
      ? Helpers::underscoreToCamelCase(trim($data['endpoint']))
      : null;

    // JS part of /wp-admin/customize.php does not like a 'method' field in a form widget
    $method_param_name = isset($data['mailpoet_method']) ? 'mailpoet_method' : 'method';
    $this->_request_method = isset($data[$method_param_name])
      ? Helpers::underscoreToCamelCase(trim($data[$method_param_name]))
      : null;

    $this->_request_token = isset($data['token'])
      ? trim($data['token'])
      : null;

    if (!$this->_request_endpoint || !$this->_request_method || !$this->_request_api_version) {
      $error_message = WPFunctions::get()->__('Invalid API request.', 'mailpoet');
      $error_response = $this->createErrorResponse(Error::BAD_REQUEST, $error_message, Response::STATUS_BAD_REQUEST);
      return $error_response;
    } else if (!empty($this->_endpoint_namespaces[$this->_request_api_version])) {
      foreach ($this->_endpoint_namespaces[$this->_request_api_version] as $namespace) {
        $endpoint_class = sprintf(
          '%s\%s',
          $namespace,
          ucfirst($this->_request_endpoint)
        );
        if ($this->container->has($endpoint_class)) {
          $this->_request_endpoint_class = $endpoint_class;
          break;
        }
      }
      $this->_request_data = isset($data['data'])
        ? WPFunctions::get()->stripslashesDeep($data['data'])
        : [];

      // remove reserved keywords from data
      if (is_array($this->_request_data) && !empty($this->_request_data)) {
        // filter out reserved keywords from data
        $reserved_keywords = [
          'token',
          'endpoint',
          'method',
          'api_version',
          'mailpoet_method', // alias of 'method'
          'mailpoet_redirect',
        ];
        $this->_request_data = array_diff_key(
          $this->_request_data,
          array_flip($reserved_keywords)
        );
      }
    }
  }

  function processRoute() {
    try {
      if (empty($this->_request_endpoint_class) ||
        !$this->container->has($this->_request_endpoint_class)
      ) {
        throw new \Exception(__('Invalid API endpoint.', 'mailpoet'));
      }

      $endpoint = $this->container->get($this->_request_endpoint_class);
      if (!method_exists($endpoint, $this->_request_method)) {
        throw new \Exception(__('Invalid API endpoint method.', 'mailpoet'));
      }

      if (class_exists(Debugger::class)) {
        ApiPanel::init($endpoint, $this->_request_method, $this->_request_data);
      }

      // check the accessibility of the requested endpoint's action
      // by default, an endpoint's action is considered "private"
      if (!$this->validatePermissions($this->_request_method, $endpoint->permissions)) {
        $error_message = WPFunctions::get()->__('You do not have the required permissions.', 'mailpoet');
        $error_response = $this->createErrorResponse(Error::FORBIDDEN, $error_message, Response::STATUS_FORBIDDEN);
        return $error_response;
      }
      $response = $endpoint->{$this->_request_method}($this->_request_data);
      return $response;
    } catch (\Exception $e) {
      $error_message = $e->getMessage();
      $error_response = $this->createErrorResponse(Error::BAD_REQUEST, $error_message, Response::STATUS_BAD_REQUEST);
      return $error_response;
    }
  }

  function validatePermissions($request_method, $permissions) {
    // validate method permission if defined, otherwise validate global permission
    return(!empty($permissions['methods'][$request_method])) ?
      $this->access_control->validatePermission($permissions['methods'][$request_method]) :
      $this->access_control->validatePermission($permissions['global']);
  }

  function checkToken() {
    return WPFunctions::get()->wpVerifyNonce($this->_request_token, 'mailpoet_token');
  }

  function setTokenAndAPIVersion() {
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

  function addEndpointNamespace($namespace, $version) {
    if (!empty($this->_endpoint_namespaces[$version][$namespace])) return;
    $this->_endpoint_namespaces[$version][] = $namespace;
  }

  function getEndpointNamespaces() {
    return $this->_endpoint_namespaces;
  }

  function getRequestedEndpointClass() {
    return $this->_request_endpoint_class;
  }

  function getRequestedAPIVersion() {
    return $this->_request_api_version;
  }

  function createErrorResponse($error_type, $error_message, $response_status) {
    $error_response = new ErrorResponse(
      [
        $error_type => $error_message,
      ],
      [],
      $response_status
    );
    return $error_response;
  }
}
