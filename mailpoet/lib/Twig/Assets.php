<?php

namespace MailPoet\Twig;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

class Assets extends AbstractExtension {
  private $globals;

  /** @var CdnAssetUrl|null */
  private $cdnAssetsUrl;

  public function __construct(
    array $globals,
    CdnAssetUrl $cdnAssetsUrl = null
  ) {
    $this->globals = $globals;
    $this->cdnAssetsUrl = $cdnAssetsUrl;
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
        $this->globals['assets_url'],
        $this->getAssetFilename($this->globals['assets_manifest_css'], $stylesheet)
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
      $this->globals['assets_url'],
      strpos($script, 'lib/') === 0 ? 'js' : 'dist/js',
      $this->getAssetFileName($this->globals['assets_manifest_js'], $script)
    );
  }

  public function generateImageUrl($path) {
    return $this->appendVersionToUrl(
      $this->globals['assets_url'] . '/img/' . $path
    );
  }

  public function appendVersionToUrl($url) {
    return WPFunctions::get()->addQueryArg('mailpoet_version', $this->globals['version'], $url);
  }

  public function getAssetFileName($manifest, $asset) {
    return (!empty($manifest[$asset])) ? $manifest[$asset] : $asset;
  }

  public function generateCdnUrl($path) {
    if ($this->cdnAssetsUrl === null) {
      $this->cdnAssetsUrl = ContainerWrapper::getInstance()->get(CdnAssetUrl::class);
    }
    return $this->cdnAssetsUrl->generateCdnUrl($path);
  }
}
