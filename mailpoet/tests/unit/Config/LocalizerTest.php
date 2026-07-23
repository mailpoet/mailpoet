<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class LocalizerTest extends \MailPoetUnitTest {
  /** @var WPFunctions & MockObject */
  private $wpFunctions;

  public function _before() {
    parent::_before();
    $this->wpFunctions = $this->createMock(WPFunctions::class);
    WPFunctions::set($this->wpFunctions);
  }

  public function _after() {
    WPFunctions::set(new WPFunctions());
    parent::_after();
  }

  public function testItUsesSiteLocaleBeforePluginsLoaded(): void {
    $this->wpFunctions->expects($this->once())
      ->method('didAction')
      ->with('plugins_loaded')
      ->willReturn(0);
    $this->wpFunctions->expects($this->once())
      ->method('getLocale')
      ->willReturn('fr_FR');
    // Reading the user locale before 'plugins_loaded' can fatal when the
    // plugin is network activated on multisite (see Localizer::locale()).
    $this->wpFunctions->expects($this->never())
      ->method('getUserLocale');
    $this->wpFunctions->expects($this->once())
      ->method('applyFilters')
      ->with('plugin_locale', 'fr_FR', $this->anything())
      ->willReturnArgument(1);

    $this->assertSame('fr_FR', (new Localizer())->locale());
  }

  public function testItUsesUserLocaleAfterPluginsLoaded(): void {
    $this->wpFunctions->expects($this->once())
      ->method('didAction')
      ->with('plugins_loaded')
      ->willReturn(1);
    $this->wpFunctions->expects($this->once())
      ->method('getUserLocale')
      ->willReturn('de_DE');
    $this->wpFunctions->expects($this->never())
      ->method('getLocale');
    $this->wpFunctions->expects($this->once())
      ->method('applyFilters')
      ->with('plugin_locale', 'de_DE', $this->anything())
      ->willReturnArgument(1);

    $this->assertSame('de_DE', (new Localizer())->locale());
  }

  public function testItAppliesThePluginLocaleFilter(): void {
    $this->wpFunctions->method('didAction')->willReturn(1);
    $this->wpFunctions->method('getUserLocale')->willReturn('de_DE');
    $this->wpFunctions->expects($this->once())
      ->method('applyFilters')
      ->with('plugin_locale', 'de_DE', $this->anything())
      ->willReturn('ja');

    $this->assertSame('ja', (new Localizer())->locale());
  }
}
