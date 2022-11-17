<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use MailPoet\Config\Env;
use MailPoet\Twig\Assets;
use MailPoet\Util\CdnAssetUrl;

class AssetsTest extends \MailPoetTest {
  public $assetsExtension;
  public $version;
  public $assetsUrl;

  public function _before() {
    parent::_before();
    $this->assetsUrl = 'https://www.testing.com/wp-content/plugins/mailpoet/assets';
    $this->version = '1.2.3';
    $this->assetsExtension = new Assets(
      [
        'assets_url' => $this->assetsUrl,
        'assets_manifest_js' => false,
        'assets_manifest_css' => false,
        'version' => $this->version,
      ],
      new CdnAssetUrl('')
    );
  }

  public function testItGeneratesJavascriptTagsForAssetsUsinManifestFile() {
    $manifest = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];

    $assetsExtension = new Assets(
      [
        'assets_url' => $this->assetsUrl,
        'assets_manifest_js' => $manifest,
        'version' => $this->version,
      ],
      new CdnAssetUrl('')
    );

    expect($assetsExtension->generateJavascript('script1.js', 'script2.js'))->equals(
      sprintf(
        '<script type="text/javascript" src="' . $this->assetsUrl . '/dist/js/script1.hash.js?ver=%s"></script>'
        . "\n"
        . '<script type="text/javascript" src="' . $this->assetsUrl . '/dist/js/script2.hash.js?ver=%s"></script>',
        Env::$version,
        Env::$version
      )
    );
  }

  public function testItGeneratesJavascriptTagsForAssetsWhenManifestFileDoesNotExist() {
    expect($this->assetsExtension->generateJavascript('lib/script1.js', 'script2.js'))->equals(
      sprintf(
        '<script type="text/javascript" src="' . $this->assetsUrl . '/js/lib/script1.js?ver=%s"></script>'
        . "\n"
        . '<script type="text/javascript" src="' . $this->assetsUrl . '/dist/js/script2.js?ver=%s"></script>',
        Env::$version,
        Env::$version
      )
    );
  }

  public function testItGeneratesStylesheetTagsForAssetsUsingManifestFile() {
    $manifest = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];

    $assetsExtension = new Assets(
      [
        'assets_url' => $this->assetsUrl,
        'assets_manifest_css' => $manifest,
        'version' => $this->version,
      ],
      new CdnAssetUrl('')
    );

    expect($assetsExtension->generateStylesheet('style1.css', 'style2.css'))->equals(
      '<link rel="stylesheet" type="text/css" href="' . $this->assetsUrl . '/dist/css/style1.hash.css" />'
      . "\n"
      . '<link rel="stylesheet" type="text/css" href="' . $this->assetsUrl . '/dist/css/style2.hash.css" />'
    );
  }

  public function testItGeneratesStylesheetTagsWhenManifestFileDoesNotExist() {
    expect($this->assetsExtension->generateStylesheet('style1.css', 'style2.css'))->equals(
      '<link rel="stylesheet" type="text/css" href="' . $this->assetsUrl . '/dist/css/style1.css" />'
      . "\n"
      . '<link rel="stylesheet" type="text/css" href="' . $this->assetsUrl . '/dist/css/style2.css" />'
    );
  }

  public function testItGeneratesImageUrls() {
    expect($this->assetsExtension->generateImageUrl('image1.png'))->equals(
      $this->assetsUrl . '/img/image1.png?mailpoet_version=' . $this->version
    );
  }

  public function testItAppendsVersionToUrl() {
    $withoutFile = 'http://url.com/';
    expect($this->assetsExtension->appendVersionToUrl($withoutFile))->equals(
      $withoutFile . '?mailpoet_version=' . $this->version
    );
    $withFile = 'http://url.com/file.php';
    expect($this->assetsExtension->appendVersionToUrl($withFile))->equals(
      $withFile . '?mailpoet_version=' . $this->version
    );
    $withFolder = 'http://url.com/folder/file.php';
    expect($this->assetsExtension->appendVersionToUrl($withFolder))->equals(
      $withFolder . '?mailpoet_version=' . $this->version
    );
    $withQueryString = 'http://url.com/folder/file.php?name=value';
    expect($this->assetsExtension->appendVersionToUrl($withQueryString))->equals(
      $withQueryString . '&mailpoet_version=' . $this->version
    );
  }
}
