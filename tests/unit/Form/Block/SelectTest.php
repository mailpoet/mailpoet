<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Select;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions;
use PHPUnit\Framework\MockObject\MockObject;

class SelectTest extends \MailPoetUnitTest {
  /** @var array */
  private $block;

  /** @var Select */
  private $selectBlock;

  /** @var MockObject | Functions */
  private $wpMock;

  /** @var MockObject | FieldNameObfuscator */
  private $fieldNameObfuscatorMock;

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(Functions::class);
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->fieldNameObfuscatorMock = $this->createMock(FieldNameObfuscator::class);
    $this->fieldNameObfuscatorMock->method('obfuscate')->will($this->returnArgument(0));
    $this->selectBlock = new Select($this->fieldNameObfuscatorMock, $this->wpMock);
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
            'is_hidden' => false,
          ],
        ],
      ],
    ];
  }

  public function testItRendersSelectBlock() {
    $rendered = $this->selectBlock->render($this->block);
    expect($rendered)->contains(Subscriber::STATUS_SUBSCRIBED);
    expect($rendered)->contains(Subscriber::STATUS_UNSUBSCRIBED);
    expect($rendered)->contains(Subscriber::STATUS_BOUNCED);
  }

  public function testItRendersSelectedOption() {
    $this->block['params']['values'][0]['is_checked'] = true;
    $rendered = $this->selectBlock->render($this->block);
    expect($rendered)->contains('selected="selected"');
  }

  public function testItRendersDisabledOptions() {
    $this->block['params']['values'][2]['is_disabled'] = true;
    $rendered = $this->selectBlock->render($this->block);
    expect($rendered)->contains('disabled="disabled"');
  }

  public function testItDoesNotRenderHiddenOptions() {
    $this->block['params']['values'][2]['is_hidden'] = true;
    $rendered = $this->selectBlock->render($this->block);
    expect($rendered)->notContains(Subscriber::STATUS_BOUNCED);
  }
}
