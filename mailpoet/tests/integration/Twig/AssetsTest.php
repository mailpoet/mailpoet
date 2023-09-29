<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use MailPoet\Twig\Assets;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

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
        'assets_manifest_css' => false,
        'version' => $this->version,
      ],
      WPFunctions::get(),
      new CdnAssetUrl('')
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
      WPFunctions::get(),
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
    expect($this->assetsExtension->generateImageUrl('image1.png'))->equals($this->assetsUrl . '/img/image1.png');
  }
}
