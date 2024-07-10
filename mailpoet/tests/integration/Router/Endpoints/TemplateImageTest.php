<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\NewsletterTemplates\TemplateImageLoader;
use MailPoet\Router\Endpoints\TemplateImage;
use MailPoet\WP\Functions as WPFunctions;

class TemplateImageTest extends \MailPoetTest {
  public function testItDisplaysExternalImage() {
    // Make a copy of the test image because it's going to be unlinked
    $tempFile = (string)tempnam(dirname(__DIR__) . '/../../../tests/_output/', 'testimg');
    copy(dirname(__DIR__) . '/../../../tests/_data/test-image.jpg', $tempFile);

    $loader = Stub::make(TemplateImageLoader::class, [
      'wp' => new WPFunctions,
      'downloadUrl' => Expected::once($tempFile),
    ], $this);
    $templateImage = new TemplateImage($loader);

    $url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/yoga_studio/yoga-1.png';
    [$output, $result] = $this->captureOutputAndReturnValue($templateImage, $url);

    verify($result)->equals(true);
    verify($output)->stringContainsString('JFIF'); // JPEG file header
    verify(file_exists($tempFile))->equals(false); // Image is unlinked after display
  }

  public function testItDoesNotLoadIfUrlNotAllowed() {
    $loader = Stub::make(TemplateImageLoader::class, [
      'wp' => new WPFunctions,
      'downloadUrl' => Expected::never(),
    ], $this);
    $templateImage = new TemplateImage($loader);

    $url = 'https://example.com/some/image.jpg';
    [$output, $result] = $this->captureOutputAndReturnValue($templateImage, $url);

    verify($result)->false();
    verify($output)->empty();
  }

  public function testItDoesNotDisplayWrongFileType() {
    $tempFile = (string)tempnam(dirname(__DIR__) . '/../../../tests/_output/', 'testimg');
    copy(dirname(__DIR__) . '/../../../tests/_data/newsletterWithALC.json', $tempFile);

    $loader = Stub::make(TemplateImageLoader::class, [
      'wp' => new WPFunctions,
      'downloadUrl' => Expected::once($tempFile),
    ], $this);
    $templateImage = new TemplateImage($loader);

    $url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/yoga_studio/yoga-1.png';
    [$output, $result] = $this->captureOutputAndReturnValue($templateImage, $url);

    verify($result)->false();
    verify($output)->empty();
    verify(file_exists($tempFile))->equals(false);
  }

  private function captureOutputAndReturnValue(TemplateImage $templateImage, string $url): array {
    $_GET['url'] = $url;
    ob_start();
    $result = $templateImage->getExternalImage([], true);
    $output = ob_get_clean();
    return [$output, $result];
  }
}
