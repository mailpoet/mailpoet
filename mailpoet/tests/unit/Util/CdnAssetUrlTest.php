<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\CdnAssetUrl;

class CdnAssetUrlTest extends \MailPoetUnitTest {
  public function testItGeneratesCdnUrlForPlainPath() {
    $cdnAssetUrl = new CdnAssetUrl('http://example.com');
    verify($cdnAssetUrl->generateCdnUrl('form-templates/template-4/popup.png'))
      ->equals('https://ps.w.org/mailpoet/assets/form-templates/template-4/popup.png');
  }

  public function testItEncodesAtSignSoTheCdnDoesNotReadItAsSvnRevision() {
    $cdnAssetUrl = new CdnAssetUrl('http://example.com');
    verify($cdnAssetUrl->generateCdnUrl('form-templates/template-4/mailbox@3x.png'))
      ->equals('https://ps.w.org/mailpoet/assets/form-templates/template-4/mailbox%403x.png');
  }

  public function testItReturnsAssetsBaseUrlForEmptyPath() {
    $cdnAssetUrl = new CdnAssetUrl('http://example.com');
    verify($cdnAssetUrl->generateCdnUrl(''))->equals('https://ps.w.org/mailpoet/assets/');
  }
}
