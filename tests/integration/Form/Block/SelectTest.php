<?php
namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Select;
use MailPoet\Models\Subscriber;

class SelectTest extends \MailPoetTest {
  function _before() {
    $this->block = array(
      'id' => 'status',
      'type' => 'select',
      'params' => array(
        'required' => true,
        'label' => 'Status',
        'values' => array(
          array(
            'value' => array(
              Subscriber::STATUS_SUBSCRIBED => Subscriber::STATUS_SUBSCRIBED
            ),
            'is_checked' => false
          ),
          array(
            'value' => array(
              Subscriber::STATUS_UNSUBSCRIBED => Subscriber::STATUS_UNSUBSCRIBED
            ),
            'is_checked' => false
          ),
          array(
            'value' => array(
              Subscriber::STATUS_BOUNCED => Subscriber::STATUS_BOUNCED
            ),
            'is_checked' => false,
            'is_disabled' => false,
            'is_hidden' => false
          )
        )
      )
    );
  }

  function testItRendersSelectBlock() {
    $rendered = Select::render($this->block);
    expect($rendered)->contains(Subscriber::STATUS_SUBSCRIBED);
    expect($rendered)->contains(Subscriber::STATUS_UNSUBSCRIBED);
    expect($rendered)->contains(Subscriber::STATUS_BOUNCED);
  }

  function testItRendersSelectedOption() {
    $this->block['params']['values'][0]['is_checked'] = true;
    $rendered = Select::render($this->block);
    expect($rendered)->contains('selected="selected"');
  }

  function testItRendersDisabledOptions() {
    $this->block['params']['values'][2]['is_disabled'] = true;
    $rendered = Select::render($this->block);
    expect($rendered)->contains('disabled="disabled"');
  }

  function testItDoesNotRenderHiddenOptions() {
    $this->block['params']['values'][2]['is_hidden'] = true;
    $rendered = Select::render($this->block);
    expect($rendered)->notContains(Subscriber::STATUS_BOUNCED);
  }
}