<?php

namespace MailPoet\Form;

use MailPoet\Form\Block\Checkbox;
use MailPoet\Form\Block\Column;
use MailPoet\Form\Block\Columns;
use MailPoet\Form\Block\Date;
use MailPoet\Form\Block\Divider;
use MailPoet\Form\Block\Heading;
use MailPoet\Form\Block\Html;
use MailPoet\Form\Block\Image;
use MailPoet\Form\Block\Paragraph;
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

  /** @var Image */
  private $image;

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

  /** @var Column */
  private $column;

  /** @var Columns */
  private $columns;

  /** @var Heading */
  private $heading;

  /** @var Paragraph */
  private $paragraph;

  public function __construct(
    Checkbox $checkbox,
    Column $column,
    Columns $columns,
    Date $date,
    Divider $divider,
    Html $html,
    Image $image,
    Heading $heading,
    Paragraph $paragraph,
    Radio $radio,
    Segment $segment,
    Select $select,
    Submit $submit,
    Text $text,
    Textarea $textarea
  ) {
    $this->checkbox = $checkbox;
    $this->column = $column;
    $this->columns = $columns;
    $this->date = $date;
    $this->divider = $divider;
    $this->html = $html;
    $this->image = $image;
    $this->radio = $radio;
    $this->segment = $segment;
    $this->select = $select;
    $this->submit = $submit;
    $this->text = $text;
    $this->textarea = $textarea;
    $this->heading = $heading;
    $this->paragraph = $paragraph;
  }

  public function renderBlock(array $block, array $formSettings): string {
    $html = '';
    switch ($block['type']) {
      case 'html':
        $html .= $this->html->render($block, $formSettings);
        break;

      case 'heading':
        $html .= $this->heading->render($block);
        break;

      case 'image':
        $html .= $this->image->render($block);
        break;

      case 'paragraph':
        $html .= $this->paragraph->render($block);
        break;

      case 'divider':
        $html .= $this->divider->render($block);
        break;

      case 'checkbox':
        $html .= $this->checkbox->render($block, $formSettings);
        break;

      case 'radio':
        $html .= $this->radio->render($block, $formSettings);
        break;

      case 'segment':
        $html .= $this->segment->render($block, $formSettings);
        break;

      case 'date':
        $html .= $this->date->render($block, $formSettings);
        break;

      case 'select':
        $html .= $this->select->render($block, $formSettings);
        break;

      case 'text':
        $html .= $this->text->render($block, $formSettings);
        break;

      case 'textarea':
        $html .= $this->textarea->render($block, $formSettings);
        break;

      case 'submit':
        $html .= $this->submit->render($block, $formSettings);
        break;
    }
    return $html;
  }

  public function renderContainerBlock(array $block, string $content) {
    $html = '';
    switch ($block['type']) {
      case 'columns':
        $html .= $this->columns->render($block, $content);
        break;

      case 'column':
        $html .= $this->column->render($block, $content);
        break;
    }
    return $html;
  }
}
