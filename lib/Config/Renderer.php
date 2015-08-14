<?php
namespace MailPoet\Config;
use \Twig_Loader_Filesystem as TwigFileSystem;
use \Twig_Environment as TwigEnv;
use \Twig_Lexer as TwigLexer;
use \MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Renderer {
  function __construct() {
    $file_system = new TwigFileSystem(Env::$views_path);
    $this->renderer = new TwigEnv(
      $file_system,
      array('cache' => $this->detectCache())
    );
  }

  function init() {
    $this->setupTranslations();
    $this->setupHandlebars();
    $this->setupWordPress();
    $this->setupGlobalVariables();
    $this->setupSyntax();
    return $this->renderer;
  }

  function setupTranslations() {
    $this->renderer->addExtension(new Twig\i18n(Env::$plugin_name));
  }

  function setupWordPress() {
    $this->renderer->addExtension(new Twig\WordPress());
  }

  function setupHandlebars() {
    $this->renderer->addExtension(new Twig\Handlebars());
  }

  function setupGlobalVariables() {
    $this->renderer->addExtension(new Twig\Assets(array(
      'assets_url' => Env::$assets_url,
      'assets_path' => Env::$assets_path
    )));
  }

  function setupSyntax() {
    $lexer = new TwigLexer($this->renderer, array(
      'tag_comment' => array('<%#', '%>'),
      'tag_block' => array('<%', '%>'),
      'tag_variable' => array('<%=', '%>'),
      'interpolation' => array('%{', '}')
    ));
    $this->renderer->setLexer($lexer);
  }

  function detectCache() {
    $cache_path = Env::$views_path . '/cache';
    if (WP_DEBUG === false) return $cache_path;
    return false;
  }
}
