<?php

namespace MailPoet\Twig;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

class Assets extends AbstractExtension {
  const CDN_URL = 'https://ps.w.org/mailpoet/';
  private $_globals;

  public function __construct($globals) {
    $this->_globals = $globals;
  }

  public function getFunctions() {
    return [
      new TwigFunction(
        'stylesheet',
        [$this, 'generateStylesheet'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'javascript',
        [$this, 'generateJavascript'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'getJavascriptScriptUrl',
        [$this, 'getJavascriptScriptUrl'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'image_url',
        [$this, 'generateImageUrl'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'cdn_url',
        [$this, 'generateCdnUrl'],
        ['is_safe' => ['all']]
      ),
    ];
  }

  public function generateStylesheet() {
    $stylesheets = func_get_args();
    $output = [];

    foreach ($stylesheets as $stylesheet) {
      $output[] = sprintf(
        '<link rel="stylesheet" type="text/css" href="%s/dist/css/%s" />',
        $this->_globals['assets_url'],
        $this->getAssetFilename($this->_globals['assets_manifest_css'], $stylesheet)
      );
    }

    return join("\n", $output);
  }

  public function generateJavascript() {
    $scripts = func_get_args();
    $output = [];

    foreach ($scripts as $script) {
      $output[] = sprintf(
        '<script type="text/javascript" src="%s"></script>',
        $this->getJavascriptScriptUrl($script)
      );
    }

    return join("\n", $output);
  }

  public function getJavascriptScriptUrl($script) {
    return sprintf(
      '%s/%s/%s',
      $this->_globals['assets_url'],
      strpos($script, 'lib/') === 0 ? 'js' : 'dist/js',
      $this->getAssetFileName($this->_globals['assets_manifest_js'], $script)
    );
  }

  public function generateImageUrl($path) {
    return $this->appendVersionToUrl(
      $this->_globals['assets_url'] . '/img/' . $path
    );
  }

  public function appendVersionToUrl($url) {
    return WPFunctions::get()->addQueryArg('mailpoet_version', $this->_globals['version'], $url);
  }

  public function getAssetFileName($manifest, $asset) {
    return (!empty($manifest[$asset])) ? $manifest[$asset] : $asset;
  }

  public function generateCdnUrl($path) {
    $useCdn = defined('MAILPOET_USE_CDN') ? MAILPOET_USE_CDN : true;
    return ($useCdn ? self::CDN_URL : $this->_globals['base_url'] . '/plugin_repository/') . "assets/$path";
  }
}
