<?php

/*
Plugin Name: MailPoet Form Template Export
Description: Simple plugin for exporting form templates
Author: MailPoet
Version: 1.0
*/

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;

add_action('admin_menu', 'formExportMenu');

function formExportMenu() {
  add_menu_page(
    'MailPoet Form Export',
    'MailPoet Form Export',
    'manage_options',
    'export-forms',
    'mailpoetExportForms'
  );
}

function mailpoetExportForms() {
  if ($_GET['exportId']) {
    return mailpoetExportForm((int)$_GET['exportId']);
  } else {
    return mailpoetRenderFormList();
  }
}

function mailpoetRenderFormList() {
  $forms = mailpoetGetFormsRepository()->findAll();
  echo "<h1>Pick a form to export!</h1>";
  echo "<ul>";
  foreach ($forms as $form) {
    /** @var FormEntity $form */
    $name = $form->getName() ?: '(no name)';
    $exportUrl = menu_page_url('export-forms', false) . '&exportId=' . $form->getId();
    echo "<li><a href=\"$exportUrl\">$name (ID: {$form->getId()})</a></li>";
  }
  echo "</ul>";
}

function mailpoetExportForm(int $id) {
  /** @var FormEntity $form */
  $form = mailpoetGetFormsRepository()->findOneById($id);
  if (!$form) {
    die('Meh! Wrong id!');
  }
  $template = file_get_contents(__DIR__ . '/Template.php', false);
  $template = str_replace('TEMPLATE_BODY', mailpoetVarExport($form->getBody()), $template);
  $settings = $form->getSettings();
  $settings['success_message'] = '';
  $settings['segments'] = [];
  $template = str_replace('TEMPLATE_SETTINGS', mailpoetVarExport($settings), $template);
  $template = str_replace('TEMPLATE_STYLES', $form->getStyles(), $template);
  $template = str_replace('TEMPLATE_NAME', $form->getName(), $template);
  $template = str_replace('class Template', 'class ' . preg_replace("/[^A-Za-z0-9]/", '', $form->getName()), $template);

  $template = htmlspecialchars($template);
  echo "<textarea style=\"width:90%;height:90vh;\">$template</textarea>";
  die;
}

function mailpoetGetFormsRepository(): FormsRepository {
  if (!class_exists(ContainerWrapper::class)) {
    die('MailPoet plugin must be active!');
  }
  return ContainerWrapper::getInstance()->get(FormsRepository::class);
}

/**
 * @see https://stackoverflow.com/questions/24316347/how-to-format-var-export-to-php5-4-array-syntax
 */
function mailpoetVarExport($var, $indent="    "): string {
  switch (gettype($var)) {
    case 'string':
      return '\'' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '\'';
    case 'array':
      $indexed = array_keys($var) === range(0, count($var) - 1);
      $r = [];
      foreach ($var as $key => $value) {
        $r[] = "$indent  "
          . ($indexed ? "" : mailpoetVarExport($key) . " => ")
          . mailpoetVarExport($value, "$indent  ");
      }
      if (count($r) === 0) {
        return '[]';
      }
      return "[\n" . implode(",\n", $r) . ",\n" . $indent . "]";
    case 'boolean':
      return $var ? 'true' : 'false';
    default:
      return var_export($var, true);
  }
}
