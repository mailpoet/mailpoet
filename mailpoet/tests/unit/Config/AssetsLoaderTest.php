<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @phpstan-type EnqueuedStyle array{string, string, array<int, string>, mixed, string}
 */
class AssetsLoaderTest extends \MailPoetUnitTest {
  /** @var string|null */
  private $assetsUrl;

  /** @var array<string, mixed> */
  private $get;

  public function _before() {
    parent::_before();
    $this->assetsUrl = Env::$assetsUrl;
    $this->get = $_GET;
    Env::$assetsUrl = 'https://example.test/wp-content/plugins/mailpoet/assets';
  }

  public function testItLoadsWordPressEditorStylesBeforeFormEditorStyles(): void {
    $enqueuedStyles = $this->loadStylesForPage('mailpoet-form-editor');

    $this->assertSame([
      [
        'mailpoet-plugin',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-plugin.css',
        ['forms', 'buttons', 'wp-components'],
        false,
        'all',
      ],
      [
        'mailpoet-form-editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-form-editor.css',
        [
          'mailpoet-plugin',
          'wp-components',
          'wp-block-library',
          'wp-block-library-theme',
          'wp-block-editor',
          'wp-edit-blocks',
          'wp-editor',
          'wp-edit-post',
          'wp-format-library',
        ],
        false,
        'all',
      ],
      [
        'mailpoet-public',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-public.css',
        [],
        false,
        'all',
      ],
    ], $enqueuedStyles);
  }

  public function testItLoadsFormEditorStylesForTemplateSelection(): void {
    $enqueuedStyles = $this->loadStylesForPage('mailpoet-form-editor-template-selection');

    $this->assertSame([
      [
        'mailpoet-plugin',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-plugin.css',
        ['forms', 'buttons', 'wp-components'],
        false,
        'all',
      ],
      [
        'mailpoet-form-editor',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-form-editor.css',
        ['mailpoet-plugin', 'wp-components'],
        false,
        'all',
      ],
    ], $enqueuedStyles);
  }

  public function testItLoadsBundledWordPressComponentsOnRegularPages(): void {
    $enqueuedStyles = $this->loadStylesForPage('mailpoet-newsletters');

    $this->assertSame([
      [
        'mailpoet-wp-components',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-wp-components.css',
        [],
        false,
        'all',
      ],
      [
        'mailpoet-plugin',
        'https://example.test/wp-content/plugins/mailpoet/assets/dist/css/mailpoet-plugin.css',
        ['forms', 'buttons', 'mailpoet-wp-components'],
        false,
        'all',
      ],
    ], $enqueuedStyles);
  }

  public function _after() {
    Env::$assetsUrl = $this->assetsUrl;
    $_GET = $this->get;
    parent::_after();
  }

  /**
   * @return RendererFactory&MockObject
   */
  private function createRendererFactory() {
    $renderer = $this->createMock(Renderer::class);
    $renderer->method('getCssAsset')
      ->willReturnArgument(0);

    $rendererFactory = $this->createMock(RendererFactory::class);
    $rendererFactory->method('getRenderer')
      ->willReturn($renderer);
    return $rendererFactory;
  }

  /**
   * @return EnqueuedStyle[]
   */
  private function loadStylesForPage(string $page): array {
    $_GET['page'] = $page;

    $wp = $this->createMock(WPFunctions::class);
    $enqueuedStyles = [];
    $wp->method('wpEnqueueStyle')
      ->willReturnCallback(
        /**
         * @param array<int, string> $deps
         * @param string|false $ver
         */
        function (string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all') use (&$enqueuedStyles): void {
          $enqueuedStyles[] = [$handle, $src, $deps, $ver, $media];
        }
      );

    $assetsLoader = new AssetsLoader($this->createRendererFactory(), $wp);
    $assetsLoader->loadStyles();

    return $enqueuedStyles;
  }
}
