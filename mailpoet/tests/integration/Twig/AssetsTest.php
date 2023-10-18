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
        'version' => $this->version,
      ],
      WPFunctions::get(),
      new CdnAssetUrl('')
    );
  }

  public function testItGeneratesImageUrls() {
    verify($this->assetsExtension->generateImageUrl('image1.png'))->equals($this->assetsUrl . '/img/image1.png');
  }
}
