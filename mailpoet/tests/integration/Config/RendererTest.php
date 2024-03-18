<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\Config\RendererFactory;
use MailPoetVendor\Twig\Environment as TwigEnvironment;
use MailPoetVendor\Twig\Loader\FilesystemLoader as TwigFileSystem;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template as TwigTemplate;
use MailPoetVendor\Twig\TemplateWrapper;

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  public $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = (new RendererFactory())->getRenderer();
  }

  public function testItUsesCorrectAssetsManifestFilenames() {
    $renderer = Stub::make(
      new Renderer(
        false,
        Env::$cachePath,
        new TwigFileSystem(Env::$viewsPath)
      ),
      ['getAssetManifest' => function($manifest) {
        return $manifest;
      }]
    );
    $renderer->__construct(
      false,
      Env::$cachePath,
      new TwigFileSystem(Env::$viewsPath)
    );
    verify($renderer->assetsManifestJs)->equals(Env::$assetsPath . '/dist/js/manifest.json');
    verify($renderer->assetsManifestCss)->equals(Env::$assetsPath . '/dist/css/manifest.json');
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

    verify($this->renderer->getAssetManifest(Env::$tempPath . '/js.json'))->equals($assetsManifestJs);
    verify($this->renderer->getAssetManifest(Env::$tempPath . '/css.json'))->equals($assetsManifestCss);
  }

  public function testItReturnsFalseAssetManifestDoesNotExist() {
    verify($this->renderer->getAssetManifest(Env::$tempPath . '/js.json'))->false();
  }

  public function testItCanGetCssAsset() {
    $assetsManifestCss = [
      'style1.css' => 'style1.hash.css',
      'style2.css' => 'style2.hash.css',
    ];
    $renderer = $this->renderer;
    $renderer->assetsManifestCss = $assetsManifestCss;
    verify($renderer->getCssAsset('style1.css'))->equals('style1.hash.css');
    verify($renderer->getCssAsset('style2.css'))->equals('style2.hash.css');
  }

  public function testItCanGetJsAsset() {
    $assetsManifestJs = [
      'script1.js' => 'script1.hash.js',
      'script2.js' => 'script2.hash.js',
    ];
    $renderer = $this->renderer;
    $renderer->assetsManifestJs = $assetsManifestJs;
    verify($renderer->getJsAsset('script1.js'))->equals('script1.hash.js');
    verify($renderer->getJsAsset('script2.js'))->equals('script2.hash.js');
  }

  public function testItDelegatesRenderingToTwig() {
    $env = Stub::makeEmpty(TwigEnvironment::class);
    $template = new class($env) extends TwigTemplate {
      public function getTemplateName() {
        return 'non-existing-template.html';
      }

      public function render(array $context) {
        return 'test render';
      }

      public function getDebugInfo() {
        // must be implemented (abstract in parent)
        return [];
      }

      public function getSourceContext() {
        // must be implemented (abstract in parent)
        return new Source('', '');
      }

      protected function doDisplay(array $context, array $blocks = []) {
        // must be implemented (abstract in parent)
      }
    };

    $renderer = Stub::construct(
      $this->renderer,
      [
        false,
        Env::$cachePath,
        new TwigFileSystem(Env::$viewsPath),
      ],
      [
        'renderer' => Stub::makeEmpty(
          TwigEnvironment::class,
          [
            'load' => Expected::atLeastOnce(
              new TemplateWrapper($env, $template)
            ),
          ],
          $this
        ),
      ]
    );

    verify($renderer->render(['somekey' => 'someval']))->equals('test render');
  }

  public function _after() {
    parent::_after();
    $this->_removeAssetsManifests();
  }

  public function _removeAssetsManifests() {
    if (is_readable(Env::$tempPath . '/js.json')) unlink(Env::$tempPath . '/js.json');
    if (is_readable(Env::$tempPath . '/css.json')) unlink(Env::$tempPath . '/css.json');
  }
}
