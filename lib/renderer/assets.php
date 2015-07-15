<?php
namespace MailPoet\Renderer;

class Assets extends \Twig_Extension {

  public $assets_url;

  public function __construct($assets_url) {
    $this->assets_url = $assets_url;
  }

  public function getName() {
    return 'assets';
  }

  public function getGlobals() {
    return array(
      'assets_url' => $this->assets_url
    );
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'stylesheet',
        array($this, 'generate_stylesheet'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'javascript',
        array($this, 'generate_javascript'),
        array('is_safe' => array('all'))
      )
    );
  }

  public function generate_stylesheet() {
    $stylesheets = func_get_args();
    $output = array();

    foreach($stylesheets as $stylesheet) {
      $output[] = '<link
        rel="stylesheet"
        type="text/css"
        href="'.$this->assets_url.'/css/'.$stylesheet.'"
      >';
    }

    return join("\n", $output);
  }

  public function generate_javascript() {
    $scripts = func_get_args();
    $output = array();

    foreach($scripts as $script) {
      $output[] = '<script
        type="text/javascript"
        src="'.$this->assets_url.'/js/'.$script.'"
      ></script>';
    }

    return join("\n", $output);
  }
}