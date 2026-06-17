<?php declare(strict_types = 1);

namespace MailPoet\AdminPages;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class AssetsControllerTest extends \MailPoetUnitTest {
  /** @var string|null */
  private $assetsPath;

  /** @var string|null */
  private $assetsUrl;

  /** @var string|null */
  private $version;

  /** @var string */
  private $testAssetsPath;

  public function _before() {
    parent::_before();
    $this->assetsPath = Env::$assetsPath;
    $this->assetsUrl = Env::$assetsUrl;
    $this->version = Env::$version;
    $this->testAssetsPath = sys_get_temp_dir() . '/mailpoet-assets-controller-test-' . uniqid('', true);
    mkdir($this->testAssetsPath . '/dist/js', 0777, true);
    Env::$assetsPath = $this->testAssetsPath;
    Env::$assetsUrl = 'https://example.test/wp-content/plugins/mailpoet/assets';
    Env::$version = 'mailpoet-version';
  }

  public function testItUsesGeneratedDependenciesWhenEnqueuingFormEditor(): void {
    file_put_contents(
      $this->testAssetsPath . '/dist/js/form_editor.asset.json',
      json_encode(['dependencies' => ['react', 'wp-api-fetch', 'wp-data'], 'version' => 'asset-version'], JSON_THROW_ON_ERROR)
    );

    $renderer = $this->createRenderer();
    $wp = $this->createMock(WPFunctions::class);
    $registeredScripts = [];
    $enqueuedScripts = [];
    $inlineScripts = [];
    $wp->method('wpRegisterScript')
      ->willReturnCallback(function (...$args) use (&$registeredScripts) {
        $registeredScripts[] = $args;
        return true;
      });
    $wp->expects($this->exactly(2))
      ->method('wpEnqueueScript')
      ->willReturnCallback(function (...$args) use (&$enqueuedScripts): void {
        $enqueuedScripts[] = $args;
      });
    $wp->expects($this->once())
      ->method('wpSetScriptTranslations')
      ->with('mailpoet_form_editor', 'mailpoet');
    $wp->expects($this->exactly(2))
      ->method('wpAddInlineScript')
      ->willReturnCallback(function (...$args) use (&$inlineScripts): void {
        $inlineScripts[] = $args;
      });
    $wp->expects($this->once())
      ->method('wpEnqueueStyle')
      ->with(
        'mailpoet_form_editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-form-editor.css'
      );

    $controller = new AssetsController($renderer, $wp);
    $controller->setupFormEditorDependencies();
    $controller->setupAdminPagesDependencies();

    $this->assertSame([
      ['mailpoet_mailpoet', false, [], 'mailpoet-version', true],
    ], $registeredScripts);
    $this->assertStringContainsString('window.MailPoet.I18n', $inlineScripts[0][1]);
    $this->assertSame('before', $inlineScripts[0][2]);
    $this->assertSame([
      ['mailpoet_mailpoet', '', [], false, false],
      [
        'mailpoet_form_editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/js/form_editor.js',
        ['mailpoet_mailpoet', 'underscore', 'react', 'wp-api-fetch', 'wp-data'],
        'asset-version',
        true,
      ],
    ], $enqueuedScripts);
    $this->assertSame('mailpoet_form_editor', $inlineScripts[1][0]);
  }

  public function _after() {
    Env::$assetsPath = $this->assetsPath;
    Env::$assetsUrl = $this->assetsUrl;
    Env::$version = $this->version;
    $this->removeDirectory($this->testAssetsPath);
    parent::_after();
  }

  /**
   * @return Renderer&MockObject
   */
  private function createRenderer() {
    $renderer = $this->createMock(Renderer::class);
    $renderer->method('getJsAsset')
      ->with('form_editor.js')
      ->willReturn('form_editor.js');
    $renderer->method('getCssAsset')
      ->with('mailpoet-form-editor.css')
      ->willReturn('mailpoet-form-editor.css');
    return $renderer;
  }

  private function removeDirectory(string $path): void {
    if (!is_dir($path)) {
      return;
    }
    $files = scandir($path);
    if ($files === false) {
      return;
    }
    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }
      $childPath = $path . '/' . $file;
      if (is_dir($childPath)) {
        $this->removeDirectory($childPath);
      } else {
        unlink($childPath);
      }
    }
    rmdir($path);
  }
}
