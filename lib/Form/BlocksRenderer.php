<?php

namespace MailPoet\Form;

class BlocksRenderer {
  public function renderBlock(array $block = []): string {
    $html = '';
    switch ($block['type']) {
      case 'html':
        $html .= Block\Html::render($block);
        break;

      case 'divider':
        $html .= Block\Divider::render();
        break;

      case 'checkbox':
        $html .= Block\Checkbox::render($block);
        break;

      case 'radio':
        $html .= Block\Radio::render($block);
        break;

      case 'segment':
        $html .= Block\Segment::render($block);
        break;

      case 'date':
        $html .= Block\Date::render($block);
        break;

      case 'select':
        $html .= Block\Select::render($block);
        break;

      case 'text':
        $html .= Block\Text::render($block);
        break;

      case 'textarea':
        $html .= Block\Textarea::render($block);
        break;

      case 'submit':
        $html .= Block\Submit::render($block);
        break;
    }
    return $html;
  }
}
