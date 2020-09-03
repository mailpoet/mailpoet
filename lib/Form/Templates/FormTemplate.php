<?php

namespace MailPoet\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\Util\CdnAssetUrl;

abstract class FormTemplate {
  const DEFAULT_STYLES = <<<EOL
/* form */
.mailpoet_form {
}

/* columns */
.mailpoet_column_with_background {
  padding: 10px;
}
/* space between columns */
.mailpoet_form_column:not(:first-child) {
  margin-left: 20px;
}

/* input wrapper (label + input) */
.mailpoet_paragraph {
  line-height:20px;
  margin-bottom: 20px;
}

/* labels */
.mailpoet_segment_label,
.mailpoet_text_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_checkbox_label,
.mailpoet_list_label,
.mailpoet_date_label {
  display:block;
  font-weight: normal;
}

/* inputs */
.mailpoet_text,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_date_month,
.mailpoet_date_day,
.mailpoet_date_year,
.mailpoet_date {
  display:block;
}

.mailpoet_text,
.mailpoet_textarea {
  width: 200px;
}

.mailpoet_checkbox {
}

.mailpoet_submit {
}

.mailpoet_divider {
}

.mailpoet_message {
}

.mailpoet_form_loading {
  width: 30px;
  text-align: center;
  line-height: normal;
}

.mailpoet_form_loading > span {
  width: 5px;
  height: 5px;
  background-color: #5b5b5b;
}
EOL;

  /** @var CdnAssetUrl */
  protected $cdnAssetUrl;

  /** @var string */
  protected $assetsDirectory = '';

  public function __construct(CdnAssetUrl $cdnAssetUrl) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  abstract public function getName(): string;

  abstract public function getBody(): array;

  public function getSettings(): array {
    return [
      'on_success' => 'message',
      'success_message' => '',
      'segments' => null,
      'segments_selected_by' => 'admin',
    ];
  }

  public function getStyles(): string {
    return self::DEFAULT_STYLES;
  }

  public function toFormEntity(): FormEntity {
    $formEntity = new FormEntity($this->getName());
    $formEntity->setBody($this->getBody());
    $formEntity->setSettings($this->getSettings());
    $formEntity->setStyles($this->getStyles());
    return $formEntity;
  }

  protected function getAssetUrl(string $filename): string {
    return $this->cdnAssetUrl->generateCdnUrl("form-templates/{$this->assetsDirectory}/$filename");
  }
}
