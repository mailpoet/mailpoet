<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Select;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class SelectTest extends \MailPoetUnitTest {
  /** @var array */
  private $block;

  /** @var Select */
  private $selectBlock;

  /** @var MockObject & Functions */
  private $wpMock;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $blockStylesRenderer;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var HtmlParser */
  private $htmlParser;

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(Functions::class);
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->wpMock->method('escHtml')->will($this->returnArgument(0));
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->rendererHelperMock->method('getFieldName')->will($this->returnValue('select'));
    $this->rendererHelperMock->method('renderLabel')->willReturnCallback(function(array $block) {
      $for = empty($block['params']['input_id']) ? '' : ' for="' . $block['params']['input_id'] . '"';
      return '<label' . $for . '></label>';
    });
    $this->rendererHelperMock->method('getFieldLabel')->will($this->returnValue('Field label'));
    $this->rendererHelperMock->method('getFieldValue')->will($this->returnValue(''));
    $this->blockStylesRenderer = $this->createMock(BlockStylesRenderer::class);
    $this->blockStylesRenderer->method('renderForSelect')->willReturn('');
    $this->selectBlock = new Select($this->rendererHelperMock, $this->wrapperMock, $this->blockStylesRenderer, $this->wpMock);
    $this->htmlParser = new HtmlParser();
    $this->block = [
      'id' => 'status',
      'type' => 'select',
      'params' => [
        'required' => true,
        'label' => 'Status',
        'values' => [
          [
            'value' => [
              SubscriberEntity::STATUS_SUBSCRIBED => SubscriberEntity::STATUS_SUBSCRIBED,
            ],
            'is_checked' => false,
          ],
          [
            'value' => [
              SubscriberEntity::STATUS_UNSUBSCRIBED => SubscriberEntity::STATUS_UNSUBSCRIBED,
            ],
            'is_checked' => false,
          ],
          [
            'value' => [
              SubscriberEntity::STATUS_BOUNCED => SubscriberEntity::STATUS_BOUNCED,
            ],
            'is_checked' => false,
            'is_disabled' => false,

          ],
        ],
      ],
    ];
  }

  public function testItRendersSelectBlock() {
    $rendered = $this->selectBlock->render($this->block, []);
    verify($rendered)->stringContainsString(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($rendered)->stringContainsString(SubscriberEntity::STATUS_UNSUBSCRIBED);
    verify($rendered)->stringContainsString(SubscriberEntity::STATUS_BOUNCED);
  }

  public function testItRendersSelectedOption() {
    $this->block['params']['values'][0]['is_checked'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    verify($rendered)->stringContainsString('selected="selected"');
  }

  public function testItRendersDisabledOptions() {
    $this->block['params']['values'][2]['is_disabled'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    verify($rendered)->stringContainsString('disabled="disabled"');
  }

  public function testItDoesNotRenderHiddenOptions() {
    $this->block['params']['values'][2]['is_hidden'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    verify($rendered)->stringNotContainsString(SubscriberEntity::STATUS_BOUNCED);
  }

  public function testItAssociatesLabelAndDescriptionWhenInputIdIsConfigured(): void {
    $this->block['params']['input_id'] = 'status_select';
    $this->block['params']['description'] = 'Status helper';

    $rendered = $this->selectBlock->render($this->block, []);

    $label = $this->htmlParser->getElementByXpath($rendered, '//label');
    $description = $this->htmlParser->getElementByXpath($rendered, "//p[@class='mailpoet_field_description']");
    $select = $this->htmlParser->getElementByXpath($rendered, '//select');

    verify($this->htmlParser->getAttribute($label, 'for')->value)->equals('status_select');
    verify($this->htmlParser->getAttribute($select, 'id')->value)->equals('status_select');
    verify($this->htmlParser->getAttribute($description, 'id')->value)->equals('status_select_description');
    verify($this->htmlParser->getAttribute($select, 'aria-describedby')->value)->equals('status_select_description');
  }
}
