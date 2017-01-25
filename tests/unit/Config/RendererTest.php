<?php
use Codeception\Util\Stub;
use MailPoet\Config\Renderer;

class RendererTest extends MailPoetTest {
  function _before() {
    $this->renderer = new Renderer($caching = false, $debugging = false);
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
  }
}
