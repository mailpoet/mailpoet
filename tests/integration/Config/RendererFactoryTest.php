<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class RendererFactoryTest extends \MailPoetTest {
  public function testItCanEnableAndDisableCachingWithAFilter() {
    $rendererFactory = new RendererFactory();
    WPFunctions::get()->addFilter('mailpoet_template_cache_enabled', function () {
      return true;
    });
    $renderer = $rendererFactory->getRenderer();
    $result = $renderer->detectCache();
    expect($result)->notEmpty();

    $rendererFactory = new RendererFactory();
    WPFunctions::get()->addFilter('mailpoet_template_cache_enabled', function () {
      return false;
    });
    $renderer = $rendererFactory->getRenderer();
    $result = $renderer->detectCache();
    expect($result)->equals(false);
  }
}
