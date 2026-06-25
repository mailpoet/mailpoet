<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\ProductCollection;

use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\ProductCollectionEmailRendererRegistrar;
use MailPoet\WP\Functions as WPFunctions;

class ProductCollectionEmailRendererRegistrarTest extends \MailPoetUnitTest {
  /** @var ProductCollectionEmailRendererRegistrar */
  private $registrar;

  public function _before() {
    parent::_before();
    $this->registrar = new ProductCollectionEmailRendererRegistrar($this->makeEmpty(WPFunctions::class));
  }

  public function testItRegistersTheRendererOnlyWhenNoCallbackIsWired(): void {
    verify($this->registrar->needsEmailRenderer(null))->true();
  }

  public function testItLeavesAnExistingCallbackUntouched(): void {
    // A callback already set by WooCommerce or another integration must be preserved.
    verify($this->registrar->needsEmailRenderer([$this, 'testItLeavesAnExistingCallbackUntouched']))->false();
    verify($this->registrar->needsEmailRenderer('strlen'))->false();
    verify($this->registrar->needsEmailRenderer(function () {
    }))->false();
  }
}
