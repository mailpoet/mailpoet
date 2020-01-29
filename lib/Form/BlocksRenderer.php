<?php

namespace MailPoet\Form;

use MailPoet\Form\Block\Checkbox;
use MailPoet\Form\Block\Date;
use MailPoet\Form\Block\Divider;
use MailPoet\Form\Block\Html;
use MailPoet\Form\Block\Radio;
use MailPoet\Form\Block\Segment;
use MailPoet\Form\Block\Select;
use MailPoet\Form\Block\Submit;
use MailPoet\Form\Block\Text;
use MailPoet\Form\Block\Textarea;

class BlocksRenderer {
  /** @var Checkbox */
  private $checkbox;

  /** @var Date */
  private $date;

  /** @var Divider */
  private $divider;

  /** @var Html */
  private $html;

  /** @var Radio */
  private $radio;

  /** @var Segment */
  private $segment;

  /** @var Select */
  private $select;

  /** @var Submit */
  private $submit;

  /** @var Text */
  private $text;

  /** @var Textarea */
  private $textarea;

  public function __construct(
    Checkbox $checkbox,
    Date $date,
    Divider $divider,
    Html $html,
    Radio $radio,
    Segment $segment,
    Select $select,
    Submit $submit,
    Text $text,
    Textarea $textarea
  ) {
    $this->checkbox = $checkbox;
    $this->date = $date;
    $this->divider = $divider;
    $this->html = $html;
    $this->radio = $radio;
    $this->segment = $segment;
    $this->select = $select;
    $this->submit = $submit;
    $this->text = $text;
    $this->textarea = $textarea;
  }

  public function renderBlock(array $block = []): string {
    $html = '';
    switch ($block['type']) {
      case 'html':
        $html .= $this->html->render($block);
        break;

      case 'divider':
        $html .= $this->divider->render();
        break;

      case 'checkbox':
        $html .= $this->checkbox->render($block);
        break;

      case 'radio':
        $html .= $this->radio->render($block);
        break;

      case 'segment':
        $html .= $this->segment->render($block);
        break;

      case 'date':
        $html .= $this->date->render($block);
        break;

      case 'select':
        $html .= $this->select->render($block);
        break;

      case 'text':
        $html .= $this->text->render($block);
        break;

      case 'textarea':
        $html .= $this->textarea->render($block);
        break;

      case 'submit':
        $html .= $this->submit->render($block);
        break;
    }
    return $html;
  }
}
