<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\Config\RendererFactory;
use MailPoet\Config\TwigFileSystemCache;
use MailPoetVendor\Twig\Environment as TwigEnvironment;

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  public $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = (new RendererFactory())->getRenderer();
  }

  public function testItUsesCorrectAssetsManifestFilenames() {
    $renderer = Stub::make($this->renderer,
      ['getAssetManifest' => function($manifest) {
        return $manifest;
      }]
    );
    expect($renderer->assetsManifestJs)->equals(Env::$assetsPath . '/dist/js/manifest.json');
    expect($renderer->assetsManifestCss)->equals(Env::$assetsPath . '/dist/css/manifest.json');
  }

  public function testItGetsAssetManifest() {
    $assetsManifestJs = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];
    $assetsManifestCss = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];
    file_put_contents(Env::$tempPath . '/js.json', json_encode($assetsManifestJs));
    file_put_contents(Env::$tempPath . '/css.json', json_encode($assetsManifestCss));

    expect($this->renderer->getAssetManifest(Env::$tempPath . '/js.json'))->equals($assetsManifestJs);
    expect($this->renderer->getAssetManifest(Env::$tempPath . '/css.json'))->equals($assetsManifestCss);
  }

  public function testItReturnsFalseAssetManifestDoesNotExist() {
    expect($this->renderer->getAssetManifest(Env::$tempPath . '/js.json'))->false();
  }

  public function testItCanGetCssAsset() {
    $assetsManifestCss = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];
    $renderer = $this->renderer;
    $renderer->assetsManifestCss = $assetsManifestCss;
    expect($renderer->getCssAsset('style1.css'))->equals('style1.hash.css');
    expect($renderer->getCssAsset('style2.css'))->equals('style2.hash.css');
  }

  public function testItCanGetJsAsset() {
    $assetsManifestJs = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];
    $renderer = $this->renderer;
    $renderer->assetsManifestJs = $assetsManifestJs;
    expect($renderer->getJsAsset('script1.js'))->equals('script1.hash.js');
    expect($renderer->getJsAsset('script2.js'))->equals('script2.hash.js');
  }

  public function testItDelegatesRenderingToTwig() {
    $renderer = Stub::construct(
      $this->renderer,
      [],
      [
        'renderer' => Stub::makeEmpty(TwigEnvironment::class,
          [
            'render' => Expected::atLeastOnce(function() {
              return 'test render';
            }),
          ],
          $this
        ),
      ]
    );

    expect($renderer->render('non-existing-template.html', ['somekey' => 'someval']))->equals('test render');
  }

  public function testItRethrowsTwigCacheExceptions() {
    $exceptionMessage = 'this is a test error';
    $renderer = Stub::construct(
      $this->renderer,
      [true, false],
      [
        'renderer' => Stub::makeEmpty(TwigEnvironment::class,
          [
            'render' => Expected::atLeastOnce(function() use ($exceptionMessage) {
              throw new \RuntimeException($exceptionMessage);
            }),
          ],
          $this
        ),
      ]
    );

    try {
      $renderer->render('non-existing-template.html', ['somekey' => 'someval']);
      self::fail('Twig exception was not rethrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString($exceptionMessage);
      expect($e->getMessage())->notEquals($exceptionMessage);
    }
  }

  public function _after() {
    $this->_removeAssetsManifests();
  }

  public function _removeAssetsManifests() {
    if (is_readable(Env::$tempPath . '/js.json')) unlink(Env::$tempPath . '/js.json');
    if (is_readable(Env::$tempPath . '/css.json')) unlink(Env::$tempPath . '/css.json');
  }
}
