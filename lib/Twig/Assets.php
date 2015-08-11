<?php
namespace MailPoet\Twig;

class Assets extends \Twig_Extension {

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
      )
    );
  }

  public function generateStylesheet() {
    $stylesheets = func_get_args();
    $output = array();

    foreach($stylesheets as $stylesheet) {
      $output[] = '<link rel="stylesheet" type="text/css"'.
                  ' href="'.$this->_globals['assets_url'].'/css/'.$stylesheet.'">';
    }

    return join("\n", $output);
  }

  public function generateJavascript() {
    $scripts = func_get_args();
    $output = array();

    foreach($scripts as $script) {
      $output[] = '<script type="text/javascript"'.
                  ' src="'.$this->_globals['assets_url'].'/js/'.$script.'">'.
                  '</script>';
    }

    return join("\n", $output);
  }
}
