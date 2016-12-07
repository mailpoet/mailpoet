<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Assets
  extends \Twig_Extension
  implements \Twig_Extension_GlobalsInterface
{

  private $_globals;

  public function __construct($globals) {
    $this->_globals = $globals;
  }

  public function getName() {
    return 'assets';
  }

  public function getGlobals() {
    return $this->_globals;
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'stylesheet',
        array($this, 'generateStylesheet'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'javascript',
        array($this, 'generateJavascript'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'image_url',
        array($this, 'generateImageUrl'),
        array('is_safe' => array('all'))
      )
    );
  }

  public function generateStylesheet() {
    $stylesheets = func_get_args();
    $output = array();

    foreach($stylesheets as $stylesheet) {
      $url = $this->appendVersionToUrl(
        $this->_globals['assets_url'] . '/css/' . $stylesheet
      );
      $output[] = sprintf(
        '<link rel="stylesheet" type="text/css" href="%s">',
        $url
      );
    }

    return join("\n", $output);
  }

  public function generateJavascript() {
    $scripts = func_get_args();
    $output = array();

    foreach($scripts as $script) {
      $url = $this->appendVersionToUrl(
        $this->_globals['assets_url'] . '/js/' . $script
      );
      $output[] = sprintf(
        '<script type="text/javascript" src="%s"></script>',
        $url
      );
    }

    return join("\n", $output);
  }

  public function generateImageUrl($path) {
    return $this->appendVersionToUrl(
      $this->_globals['assets_url'] . '/img/' . $path
    );
  }

  public function appendVersionToUrl($url) {
    return add_query_arg('mailpoet_version', $this->_globals['version'], $url);
  }
}
