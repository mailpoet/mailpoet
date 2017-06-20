<?php
use Codeception\Util\Stub;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer;

class RendererTest extends MailPoetTest {
  function _before() {
    $this->renderer = new Renderer($caching = false, $debugging = false);
  }

  function testItUsesCorrectAssetsManifestFilenames() {
    $renderer = Stub::make(new Renderer(),
      array('getAssetManifest' => function($manifest) { return $manifest; })
    );
    $renderer->__construct();
    expect($renderer->assets_manifest_js)->equals(Env::$assets_path . '/js/manifest.json');
    expect($renderer->assets_manifest_css)->equals(Env::$assets_path . '/css/manifest.json');
  }

  function testItGetsAssetManifest() {
    $assets_manifest_js = array(
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js'
    );
    $assets_manifest_css = array(
      'style1.js' => 'style1.hash.js',
      'style2.js' => 'style2.hash.js'
    );
    file_put_contents(Env::$temp_path . '/js.json', json_encode($assets_manifest_js));
    file_put_contents(Env::$temp_path . '/css.json', json_encode($assets_manifest_css));

    expect($this->renderer->getAssetManifest(Env::$temp_path . '/js.json'))->equals($assets_manifest_js);
    expect($this->renderer->getAssetManifest(Env::$temp_path . '/css.json'))->equals($assets_manifest_css);
  }

  function testItReturnsFalseAssetManifestDoesNotExist() {
    expect($this->renderer->getAssetManifest(Env::$temp_path . '/js.json'))->false();
  }

  function testItWillNotEnableCacheWhenWpDebugIsOn() {
    $result = $this->renderer->detectCache();
    expect($result)->equals(false);
  }

  function testItDelegatesRenderingToTwig() {
    $renderer = Stub::construct(
      $this->renderer,
      array(),
      array(
        'renderer' => Stub::makeEmpty('Twig_Environment',
          array(
            'render' => Stub::atLeastOnce(function() { return 'test render'; }),
          )
        ),
      )
    );

    expect($renderer->render('non-existing-template.html', array('somekey' => 'someval')))->equals('test render');
  }

  function testItRethrowsTwigCacheExceptions() {
    $exception_message = 'this is a test error';
    $renderer = Stub::construct(
      $this->renderer,
      array(true, false),
      array(
        'renderer' => Stub::makeEmpty('Twig_Environment',
          array(
            'render' => Stub::atLeastOnce(function() use ($exception_message) {
              throw new \RuntimeException($exception_message);
            }),
          )
        ),
      )
    );

    try {
      $renderer->render('non-existing-template.html', array('somekey' => 'someval'));
      self::fail('Twig exception was not rethrown');
    } catch(\Exception $e) {
      expect($e->getMessage())->contains($exception_message);
      expect($e->getMessage())->notEquals($exception_message);
    }
  }

  function _after() {
    $this->_removeAssetsManifests();
  }

  function _removeAssetsManifests() {
    if(is_file(Env::$temp_path . '/js.json')) unlink(Env::$temp_path . '/js.json');
    if(is_file(Env::$temp_path . '/css.json')) unlink(Env::$temp_path . '/css.json');
  }
}