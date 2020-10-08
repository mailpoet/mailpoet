<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Select;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions;
use PHPUnit\Framework\MockObject\MockObject;

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

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(Functions::class);
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->rendererHelperMock->method('getFieldName')->will($this->returnValue('select'));
    $this->rendererHelperMock->method('renderLabel')->will($this->returnValue('<label></label>'));
    $this->rendererHelperMock->method('getFieldLabel')->will($this->returnValue('Field label'));
    $this->rendererHelperMock->method('getFieldValue')->will($this->returnValue('1'));
    $this->blockStylesRenderer = $this->createMock(BlockStylesRenderer::class);
    $this->blockStylesRenderer->method('renderForSelect')->willReturn('');
    $this->selectBlock = new Select($this->rendererHelperMock, $this->wrapperMock, $this->blockStylesRenderer, $this->wpMock);
    $this->block = [
      'id' => 'status',
      'type' => 'select',
      'params' => [
        'required' => true,
        'label' => 'Status',
        'values' => [
          [
            'value' => [
              Subscriber::STATUS_SUBSCRIBED => Subscriber::STATUS_SUBSCRIBED,
            ],
            'is_checked' => false,
          ],
          [
            'value' => [
              Subscriber::STATUS_UNSUBSCRIBED => Subscriber::STATUS_UNSUBSCRIBED,
            ],
            'is_checked' => false,
          ],
          [
            'value' => [
              Subscriber::STATUS_BOUNCED => Subscriber::STATUS_BOUNCED,
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
    expect($rendered)->stringContainsString(Subscriber::STATUS_SUBSCRIBED);
    expect($rendered)->stringContainsString(Subscriber::STATUS_UNSUBSCRIBED);
    expect($rendered)->stringContainsString(Subscriber::STATUS_BOUNCED);
  }

  public function testItRendersSelectedOption() {
    $this->block['params']['values'][0]['is_checked'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    expect($rendered)->stringContainsString('selected="selected"');
  }

  public function testItRendersDisabledOptions() {
    $this->block['params']['values'][2]['is_disabled'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    expect($rendered)->stringContainsString('disabled="disabled"');
  }

  public function testItDoesNotRenderHiddenOptions() {
    $this->block['params']['values'][2]['is_hidden'] = true;
    $rendered = $this->selectBlock->render($this->block, []);
    expect($rendered)->stringNotContainsString(Subscriber::STATUS_BOUNCED);
  }
}
