<?php

namespace MailPoet\Config;

use MailPoet\Twig;
use MailPoetVendor\Twig\Environment as TwigEnv;
use MailPoetVendor\Twig\Extension\DebugExtension;
use MailPoetVendor\Twig\Lexer as TwigLexer;
use MailPoetVendor\Twig\Loader\FilesystemLoader as TwigFileSystem;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Renderer {
  protected $cache_path;
  protected $caching_enabled;
  protected $debugging_enabled;
  protected $renderer;
  public $assets_manifest_js;
  public $assets_manifest_css;

  function __construct($caching_enabled = false, $debugging_enabled = false) {
    $this->caching_enabled = $caching_enabled;
    $this->debugging_enabled = $debugging_enabled;
    $this->cache_path = Env::$cache_path;

    $file_system = new TwigFileSystem(Env::$views_path);
    $this->renderer = new TwigEnv(
      $file_system,
      [
        'cache' => $this->detectCache(),
        'debug' => $this->debugging_enabled,
        'auto_reload' => true,
      ]
    );

    $this->assets_manifest_js = $this->getAssetManifest(Env::$assets_path . '/dist/js/manifest.json');
    $this->assets_manifest_css = $this->getAssetManifest(Env::$assets_path . '/dist/css/manifest.json');
    $this->setupDebug();
    $this->setupTranslations();
    $this->setupFunctions();
    $this->setupFilters();
    $this->setupHandlebars();
    $this->setupHelpscout();
    $this->setupAnalytics();
    $this->setupPolls();
    $this->setupGlobalVariables();
    $this->setupSyntax();
  }

  function setupTranslations() {
    $this->renderer->addExtension(new Twig\I18n(Env::$plugin_name));
  }

  function setupFunctions() {
    $this->renderer->addExtension(new Twig\Functions());
  }

  function setupFilters() {
    $this->renderer->addExtension(new Twig\Filters());
  }

  function setupHandlebars() {
    $this->renderer->addExtension(new Twig\Handlebars());
  }

  function setupHelpscout() {
    $this->renderer->addExtension(new Twig\Helpscout());
  }

  function setupAnalytics() {
    $this->renderer->addExtension(new Twig\Analytics());
  }

  function setupPolls() {
    $this->renderer->addExtension(new Twig\Polls());
  }

  function setupGlobalVariables() {
    $this->renderer->addExtension(new Twig\Assets([
      'version' => Env::$version,
      'base_url' => Env::$base_url,
      'assets_url' => Env::$assets_url,
      'assets_manifest_js' => $this->assets_manifest_js,
      'assets_manifest_css' => $this->assets_manifest_css,
    ]));
  }

  function setupSyntax() {
    $lexer = new TwigLexer($this->renderer, [
      'tag_comment' => ['<#', '#>'],
      'tag_block' => ['<%', '%>'],
      'tag_variable' => ['<%=', '%>'],
      'interpolation' => ['%{', '}'],
    ]);
    $this->renderer->setLexer($lexer);
  }

  function detectCache() {
    return $this->caching_enabled ? $this->cache_path : false;
  }

  function setupDebug() {
    if ($this->debugging_enabled) {
      $this->renderer->addExtension(new DebugExtension());
    }
  }

  function render($template, $context = []) {
    try {
      return $this->renderer->render($template, $context);
    } catch (\RuntimeException $e) {
      throw new \Exception(sprintf(
        WPFunctions::get()->__('Failed to render template "%s". Please ensure the template cache folder "%s" exists and has write permissions. Terminated with error: "%s"'),
        $template,
        $this->cache_path,
        $e->getMessage()
      ));
    }
  }

  function getAssetManifest($manifest_file) {
    if (is_readable($manifest_file)) {
      $contents = file_get_contents($manifest_file);
      if (is_string($contents)) {
        return json_decode($contents, true);
      }
    }
    return false;
  }

  function getJsAsset($asset) {
    return (!empty($this->assets_manifest_js[$asset])) ?
      $this->assets_manifest_js[$asset] :
      $asset;
  }

  function getCssAsset($asset) {
    return (!empty($this->assets_manifest_css[$asset])) ?
      $this->assets_manifest_css[$asset] :
      $asset;
  }
}
