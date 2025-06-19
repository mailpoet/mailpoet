<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use PHPUnit\Framework\MockObject\MockObject;

class RendererTest extends \MailPoetUnitTest {
  /** @var Renderer */
  private $renderer;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var AutomatedLatestContentBlock & MockObject */
  private $ALC;

  /** @var Button & MockObject */
  private $button;

  /** @var Divider & MockObject */
  private $divider;

  /** @var Footer & MockObject */
  private $footer;

  /** @var Header & MockObject */
  private $header;

  /** @var Image & MockObject */
  private $image;

  /** @var Social & MockObject */
  private $social;

  /** @var Spacer & MockObject */
  private $spacer;

  /** @var Text & MockObject */
  private $text;

  /** @var Placeholder & MockObject */
  private $placeholder;

  /** @var Coupon & MockObject */
  private $coupon;

  /** @var DynamicProductsBlock & MockObject */
  private $dynamicProducts;

  public function _before() {
    parent::_before();
    $this->newsletter = new NewsletterEntity();

    // Create mocks for all dependencies
    $this->ALC = $this->createMock(AutomatedLatestContentBlock::class);
    $this->button = $this->createMock(Button::class);
    $this->divider = $this->createMock(Divider::class);
    $this->footer = $this->createMock(Footer::class);
    $this->header = $this->createMock(Header::class);
    $this->image = $this->createMock(Image::class);
    $this->social = $this->createMock(Social::class);
    $this->spacer = $this->createMock(Spacer::class);
    $this->text = $this->createMock(Text::class);
    $this->placeholder = $this->createMock(Placeholder::class);
    $this->coupon = $this->createMock(Coupon::class);
    $this->dynamicProducts = $this->createMock(DynamicProductsBlock::class);

    $this->renderer = new Renderer(
      $this->ALC,
      $this->button,
      $this->divider,
      $this->footer,
      $this->header,
      $this->image,
      $this->social,
      $this->spacer,
      $this->text,
      $this->placeholder,
      $this->coupon,
      $this->dynamicProducts
    );
  }

  public function testItReturnsNullWhenDataHasTypeButNoCountableBlocks() {
    // Test case 1: data has type but no blocks property
    $dataWithTypeNoBlocks = [
      'type' => 'container',
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($this->newsletter, $dataWithTypeNoBlocks);
    verify($result)->null();

    // Test case 2: data has type and blocks property but blocks is not countable (null)
    $dataWithTypeNullBlocks = [
      'type' => 'container',
      'blocks' => null,
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($this->newsletter, $dataWithTypeNullBlocks);
    verify($result)->null();

    // Test case 3: data has type and blocks property but blocks is not countable (string)
    $dataWithTypeStringBlocks = [
      'type' => 'container',
      'blocks' => 'not countable',
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($this->newsletter, $dataWithTypeStringBlocks);
    verify($result)->null();

    // Test case 4: data has type and blocks property but blocks is not countable (object)
    $dataWithTypeObjectBlocks = [
      'type' => 'container',
      'blocks' => new \stdClass(),
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($this->newsletter, $dataWithTypeObjectBlocks);
    verify($result)->null();
  }

  public function testItRendersNormallyWhenDataHasTypeAndCountableBlocks() {
    // Test case: data has type and countable blocks (array)
    $dataWithTypeAndCountableBlocks = [
      'type' => 'container',
      'blocks' => [
        [
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<p>Test content</p>',
            ],
          ],
        ],
      ],
      'styles' => ['block' => []],
    ];

    $this->text->method('render')->willReturn('<div>Test content</div>');

    $result = $this->renderer->render($this->newsletter, $dataWithTypeAndCountableBlocks);
    verify($result)->notNull();
    verify(is_array($result))->true();
    verify(count($result))->equals(1);
  }

  public function testItRendersNormallyWhenDataHasNoType() {
    // Test case: data has no type property but has countable blocks
    $dataWithNoType = [
      'blocks' => [
        [
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<p>Test content</p>',
            ],
          ],
        ],
      ],
      'styles' => ['block' => []],
    ];

    $this->text->method('render')->willReturn('<div>Test content</div>');

    $result = $this->renderer->render($this->newsletter, $dataWithNoType);
    verify($result)->notNull();
    verify(is_array($result))->true();
    verify(count($result))->equals(1);
  }

  public function testItRendersMultipleBlocks() {
    $dataWithMultipleBlocks = [
      'type' => 'container',
      'blocks' => [
        [
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<p>First block</p>',
            ],
            [
              'type' => 'image',
              'src' => 'test.jpg',
            ],
          ],
        ],
      ],
      'styles' => ['block' => []],
    ];

    $this->text->method('render')->willReturn('<div>First block</div>');
    $this->image->method('render')->willReturn('<img src="test.jpg">');

    $result = $this->renderer->render($this->newsletter, $dataWithMultipleBlocks);
    verify($result)->notNull();
    verify(is_array($result))->true();
    verify(count($result))->equals(1);
  }
}
