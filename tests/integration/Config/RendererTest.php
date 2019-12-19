<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoetVendor\Twig_Environment;

class RendererTest extends \MailPoetTest {
  public $renderer;
  public function _before() {
    parent::_before();
    $this->renderer = new Renderer($caching = false, $debugging = false);
  }

  public function testItUsesCorrectAssetsManifestFilenames() {
    $renderer = Stub::make(new Renderer(),
      ['getAssetManifest' => function($manifest) {
        return $manifest;
      }]
    );
    $renderer->__construct();
    expect($renderer->assets_manifest_js)->equals(Env::$assets_path . '/dist/js/manifest.json');
    expect($renderer->assets_manifest_css)->equals(Env::$assets_path . '/dist/css/manifest.json');
  }

  public function testItGetsAssetManifest() {
    $assets_manifest_js = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];
    $assets_manifest_css = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];
    file_put_contents(Env::$temp_path . '/js.json', json_encode($assets_manifest_js));
    file_put_contents(Env::$temp_path . '/css.json', json_encode($assets_manifest_css));

    expect($this->renderer->getAssetManifest(Env::$temp_path . '/js.json'))->equals($assets_manifest_js);
    expect($this->renderer->getAssetManifest(Env::$temp_path . '/css.json'))->equals($assets_manifest_css);
  }

  public function testItReturnsFalseAssetManifestDoesNotExist() {
    expect($this->renderer->getAssetManifest(Env::$temp_path . '/js.json'))->false();
  }

  public function testItCanGetCssAsset() {
    $assets_manifest_css = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];
    $renderer = $this->renderer;
    $renderer->assets_manifest_css = $assets_manifest_css;
    expect($renderer->getCssAsset('style1.css'))->equals('style1.hash.css');
    expect($renderer->getCssAsset('style2.css'))->equals('style2.hash.css');
  }

  public function testItCanGetJsAsset() {
    $assets_manifest_js = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];
    $renderer = $this->renderer;
    $renderer->assets_manifest_js = $assets_manifest_js;
    expect($renderer->getJsAsset('script1.js'))->equals('script1.hash.js');
    expect($renderer->getJsAsset('script2.js'))->equals('script2.hash.js');
  }

  public function testItWillNotEnableCacheWhenWpDebugIsOn() {
    $result = $this->renderer->detectCache();
    expect($result)->equals(false);
  }

  public function testItDelegatesRenderingToTwig() {
    $renderer = Stub::construct(
      $this->renderer,
      [],
      [
        'renderer' => Stub::makeEmpty(Twig_Environment::class,
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
    $exception_message = 'this is a test error';
    $renderer = Stub::construct(
      $this->renderer,
      [true, false],
      [
        'renderer' => Stub::makeEmpty(Twig_Environment::class,
          [
            'render' => Expected::atLeastOnce(function() use ($exception_message) {
              throw new \RuntimeException($exception_message);
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
      expect($e->getMessage())->contains($exception_message);
      expect($e->getMessage())->notEquals($exception_message);
    }
  }

  public function _after() {
    $this->_removeAssetsManifests();
  }

  public function _removeAssetsManifests() {
    if (is_readable(Env::$temp_path . '/js.json')) unlink(Env::$temp_path . '/js.json');
    if (is_readable(Env::$temp_path . '/css.json')) unlink(Env::$temp_path . '/css.json');
  }
}
