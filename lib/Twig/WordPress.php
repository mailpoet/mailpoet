<?php
namespace MailPoet\Twig;

class WordPress extends \Twig_Extension {

  public function __construct() {
  }

  public function getName() {
    return 'wordpress';
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'nonce',
        array($this, 'generateNonce'),
        array(
          'needs_environment' => true,
          'needs_context'     => true,
          'is_safe'           => array('all'))
      )
    );
  }

  public function generateNonce($env, $context, $action = 'mailpoet') {
    return '<input type="hidden" '.
      'id="mailpoet_nonce" '.
      'name="mailpoet_nonce" '.
      'value="'.wp_create_nonce($action).'" '.
      '/>';
  }
}