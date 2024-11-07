<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Settings_Controller;

class Image_Test extends \MailPoetTest {
  /** @var Image */
  private $imageRenderer;

  private $imageContent = '
    <figure class="wp-block-image alignleft size-full is-style-default">
        <img src="https://test.com/wp-content/uploads/2023/05/image.jpg" alt="" style="" srcset="https://test.com/wp-content/uploads/2023/05/image.jpg 1000w"/>
    </figure>
  ';

  /** @var array */
  private $parsedImage = [
    'blockName' => 'core/image',
    'attrs' => [
      'align' => 'left',
      'id' => 1,
      'scale' => 'cover',
      'sizeSlug' => 'full',
      'linkDestination' => 'none',
      'className' => 'is-style-default',
      'width' => '640px',
    ],
    'innerBlocks' => [],
    'innerHTML' => '',
    'innerContent' => [],
  ];

  /** @var Settings_Controller */
  private $settingsController;

  public function _before() {
    $this->diContainer->get(Email_Editor::class)->initialize();
    $this->imageRenderer = new Image();
    $this->settingsController = $this->diContainer->get(Settings_Controller::class);
  }

  public function testItRendersMandatoryImageStyles(): void {
    $parsedImage = $this->parsedImage;
    $parsedImage['innerHTML'] = $this->imageContent; // To avoid repetition of the image content in the test we need to add it to the parsed block

    $rendered = $this->imageRenderer->render($this->imageContent, $parsedImage, $this->settingsController);
    $this->assertStringNotContainsString('<figure', $rendered);
    $this->assertStringNotContainsString('<figcaption', $rendered);
    $this->assertStringNotContainsString('</figure>', $rendered);
    $this->assertStringNotContainsString('</figcaption>', $rendered);
    $this->assertStringNotContainsString('srcset', $rendered);
    $this->assertStringContainsString('width="640"', $rendered);
    $this->assertStringContainsString('width:640px;', $rendered);
    $this->assertStringContainsString('<img ', $rendered);
  }

  public function testItRendersBorderRadiusStyle(): void {
    $parsedImage = $this->parsedImage;
    $parsedImage['attrs']['className'] = 'is-style-rounded';
    $parsedImage['innerHTML'] = $this->imageContent; // To avoid repetition of the image content in the test we need to add it to the parsed block

    $rendered = $this->imageRenderer->render($this->imageContent, $parsedImage, $this->settingsController);
    $this->assertStringNotContainsString('<figure', $rendered);
    $this->assertStringNotContainsString('<figcaption', $rendered);
    $this->assertStringNotContainsString('</figure>', $rendered);
    $this->assertStringNotContainsString('</figcaption>', $rendered);
    $this->assertStringContainsString('width="640"', $rendered);
    $this->assertStringContainsString('width:640px;', $rendered);
    $this->assertStringContainsString('<img ', $rendered);
    $this->assertStringContainsString('border-radius: 9999px;', $rendered);
  }

  public function testItRendersCaption(): void {
    $imageContent = str_replace('</figure>', '<figcaption class="wp-element-caption">Caption</figcaption></figure>', $this->imageContent);
    $parsedImage = $this->parsedImage;
    $parsedImage['innerHTML'] = $imageContent; // To avoid repetition of the image content in the test we need to add it to the parsed block

    $rendered = $this->imageRenderer->render($imageContent, $parsedImage, $this->settingsController);
    $this->assertStringContainsString('>Caption</span>', $rendered);
    $this->assertStringContainsString('text-align:center;', $rendered);
  }

  public function testItRendersImageAlignment(): void {
    $imageContent = str_replace('style=""', 'style="width:400px;height:300px;"', $this->imageContent);
    $parsedImage = $this->parsedImage;
    $parsedImage['attrs']['align'] = 'center';
    $parsedImage['attrs']['width'] = '400px';
    $parsedImage['innerHTML'] = $imageContent; // To avoid repetition of the image content in the test we need to add it to the parsed block

    $rendered = $this->imageRenderer->render($imageContent, $parsedImage, $this->settingsController);
    $this->assertStringContainsString('align="center"', $rendered);
    $this->assertStringContainsString('width="400"', $rendered);
    $this->assertStringContainsString('height="300"', $rendered);
    $this->assertStringContainsString('height:300px;', $rendered);
    $this->assertStringContainsString('width:400px;', $rendered);
  }

  public function testItRendersBorders(): void {
    $imageContent = $this->imageContent;
    $parsedImage = $this->parsedImage;
    $parsedImage['attrs']['style']['border'] = [
      'width' => '10px',
      'color' => '#000001',
      'radius' => '20px',
    ];

    $rendered = $this->imageRenderer->render($imageContent, $parsedImage, $this->settingsController);
    $html = new \WP_HTML_Tag_Processor($rendered);
    // Border is rendered on the wrapping table cell
    $html->next_tag(['tag_name' => 'td', 'class_name' => 'email-image-cell']);
    $tableCellStyle = $html->get_attribute('style');
    $this->assertStringContainsString('border-color:#000001', $tableCellStyle);
    $this->assertStringContainsString('border-radius:20px', $tableCellStyle);
    $this->assertStringContainsString('border-style:solid;', $tableCellStyle);
    $html->next_tag(['tag_name' => 'img']);
    $imgStyle = $html->get_attribute('style');
    $this->assertStringNotContainsString('border', $imgStyle);
  }

  public function testItMovesBorderRelatedClasses(): void {
    $imageContent = str_replace('<img', '<img class="custom-class has-border-color has-border-red-color"',$this->imageContent);
    $parsedImage = $this->parsedImage;
    $parsedImage['attrs']['style']['border'] = [
      'width' => '10px',
      'color' => '#000001',
      'radius' => '20px',
    ];

    $rendered = $this->imageRenderer->render($imageContent, $parsedImage, $this->settingsController);
    $html = new \WP_HTML_Tag_Processor($rendered);
    // Border is rendered on the wrapping table cell and the border classes are moved to the wrapping table cell
    $html->next_tag(['tag_name' => 'td', 'class_name' => 'email-image-cell']);
    $tableCellClass = $html->get_attribute('class');
    $this->assertStringContainsString('has-border-red-color', $tableCellClass);
    $this->assertStringContainsString('has-border-color', $tableCellClass);
    $this->assertStringNotContainsString('custom-class', $tableCellClass);
  }
}
