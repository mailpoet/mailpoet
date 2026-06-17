<?php declare(strict_types = 1);

namespace MailPoet\AdminPages;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @phpstan-type RegisteredScript array{string, mixed, array<int|string, mixed>, mixed, bool}
 * @phpstan-type EnqueuedScript array{string, string, array<int|string, mixed>, mixed, bool}
 * @phpstan-type InlineScript array{string, string, string}
 * @phpstan-type EnqueuedStyle array{string, string, array<int|string, mixed>, mixed, string}
 * @phpstan-type ScriptTranslation array{string, string, string}
 */
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
    $this->writeFormEditorAssetData(['dependencies' => ['react', 'wp-api-fetch', 'wp-data'], 'version' => 'asset-version']);

    $result = $this->enqueueFormEditor();

    $this->assertSame([
      ['mailpoet_mailpoet', false, [], 'mailpoet-version', true],
    ], $result['registeredScripts']);
    $this->assertStringContainsString('window.MailPoet.I18n', $result['inlineScripts'][0][1]);
    $this->assertSame('before', $result['inlineScripts'][0][2]);
    $this->assertSame([
      ['mailpoet_mailpoet', '', [], false, false],
      [
        'mailpoet_form_editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/js/form_editor.js',
        ['mailpoet_mailpoet', 'underscore', 'code-editor', 'react', 'wp-api-fetch', 'wp-data'],
        'asset-version',
        true,
      ],
    ], $result['enqueuedScripts']);
    $this->assertSame('mailpoet_form_editor', $result['inlineScripts'][1][0]);
    $this->assertSame([
      [
        'mailpoet_form_editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-form-editor.css',
        [],
        false,
        'all',
      ],
    ], $result['enqueuedStyles']);
    $this->assertSame([
      ['mailpoet_form_editor', 'mailpoet', ''],
    ], $result['scriptTranslations']);
  }

  public function testItDoesNotRequireCodeEditorWhenCodeEditorIsUnavailable(): void {
    $this->writeFormEditorAssetData(['dependencies' => ['wp-data'], 'version' => 'asset-version']);

    $result = $this->enqueueFormEditor(false);

    $this->assertSame(
      ['mailpoet_mailpoet', 'underscore', 'wp-data'],
      $result['enqueuedScripts'][1][2]
    );
  }

  public function testItFallsBackWhenGeneratedAssetDataIsMissing(): void {
    $result = $this->enqueueFormEditor();

    $this->assertSame(
      ['mailpoet_mailpoet', 'underscore', 'code-editor'],
      $result['enqueuedScripts'][1][2]
    );
    $this->assertSame('mailpoet-version', $result['enqueuedScripts'][1][3]);
  }

  /**
   * @dataProvider invalidGeneratedAssetData
   */
  public function testItFallsBackWhenGeneratedAssetDataIsInvalid(string $contents): void {
    file_put_contents($this->getFormEditorAssetDataPath(), $contents);

    $result = $this->enqueueFormEditor();

    $this->assertSame(
      ['mailpoet_mailpoet', 'underscore', 'code-editor'],
      $result['enqueuedScripts'][1][2]
    );
    $this->assertSame('mailpoet-version', $result['enqueuedScripts'][1][3]);
  }

  /**
   * @return array<string, array{string}>
   */
  public function invalidGeneratedAssetData(): array {
    return [
      'malformed json' => ['{"dependencies":'],
      'not an object' => ['"not an object"'],
    ];
  }

  public function testItFiltersInvalidGeneratedAssetDataValues(): void {
    $this->writeFormEditorAssetData(['dependencies' => ['wp-data', false, 1, 'react'], 'version' => 1]);

    $result = $this->enqueueFormEditor();

    $this->assertSame(
      ['mailpoet_mailpoet', 'underscore', 'code-editor', 'wp-data', 'react'],
      $result['enqueuedScripts'][1][2]
    );
    $this->assertSame('mailpoet-version', $result['enqueuedScripts'][1][3]);
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

  /**
   * @param mixed[] $data
   */
  private function writeFormEditorAssetData(array $data): void {
    file_put_contents(
      $this->getFormEditorAssetDataPath(),
      json_encode($data, JSON_THROW_ON_ERROR)
    );
  }

  private function getFormEditorAssetDataPath(): string {
    return $this->testAssetsPath . '/dist/js/form_editor.asset.json';
  }

  /**
   * @param array<mixed>|false $codeEditorSettings
   * @return array{
   *   registeredScripts: array<int, RegisteredScript>,
   *   enqueuedScripts: array<int, EnqueuedScript>,
   *   inlineScripts: array<int, InlineScript>,
   *   enqueuedStyles: array<int, EnqueuedStyle>,
   *   scriptTranslations: array<int, ScriptTranslation>
   * }
   */
  private function enqueueFormEditor($codeEditorSettings = ['codemirror' => ['mode' => 'css']]): array {
    $renderer = $this->createRenderer();
    $wp = $this->createMock(WPFunctions::class);
    $registeredScripts = [];
    $enqueuedScripts = [];
    $inlineScripts = [];
    $enqueuedStyles = [];
    $scriptTranslations = [];
    $wp->expects($this->once())
      ->method('wpEnqueueCodeEditor')
      ->with(['type' => 'text/css'])
      ->willReturn($codeEditorSettings);
    $registerScriptCallback =
      /**
       * @param string|false $src
       * @param array<int, string> $deps
       * @param string|false $ver
       */
      function (string $handle, $src = '', array $deps = [], $ver = false, bool $inFooter = false) use (&$registeredScripts): bool {
        $registeredScripts[] = [$handle, $src, $deps, $ver, $inFooter];
        return true;
      };
    $wp->method('wpRegisterScript')
      ->willReturnCallback($registerScriptCallback);

    $enqueueScriptCallback =
      /**
       * @param array<int, string> $deps
       * @param string|false $ver
       */
      function (string $handle, string $src = '', array $deps = [], $ver = false, bool $inFooter = false) use (&$enqueuedScripts): void {
        $enqueuedScripts[] = [$handle, $src, $deps, $ver, $inFooter];
      };
    $wp->method('wpEnqueueScript')
      ->willReturnCallback($enqueueScriptCallback);

    $wp->method('wpSetScriptTranslations')
      ->willReturnCallback(function (string $handle, string $domain = 'default', string $path = '') use (&$scriptTranslations): bool {
        $scriptTranslations[] = [$handle, $domain, $path];
        return true;
      });
    $wp->method('wpAddInlineScript')
      ->willReturnCallback(function (string $handle, string $data, string $position = 'after') use (&$inlineScripts): void {
        $inlineScripts[] = [$handle, $data, $position];
      });

    $enqueueStyleCallback =
      /**
       * @param array<int, string> $deps
       * @param string|false $ver
       */
      function (string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all') use (&$enqueuedStyles): void {
        $enqueuedStyles[] = [$handle, $src, $deps, $ver, $media];
      };
    $wp->method('wpEnqueueStyle')
      ->willReturnCallback($enqueueStyleCallback);

    $controller = new AssetsController($renderer, $wp);
    $controller->setupFormEditorDependencies();
    $controller->setupAdminPagesDependencies();

    return [
      'registeredScripts' => $registeredScripts,
      'enqueuedScripts' => $enqueuedScripts,
      'inlineScripts' => $inlineScripts,
      'enqueuedStyles' => $enqueuedStyles,
      'scriptTranslations' => $scriptTranslations,
    ];
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
