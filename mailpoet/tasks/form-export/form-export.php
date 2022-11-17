<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
  if (isset($_GET['exportId'])) {
    return mailpoetExportForm(absint(wp_unslash($_GET['exportId'])));
  }
  return mailpoetRenderFormList();
}

function mailpoetRenderFormList() {
  $forms = mailpoetGetFormsRepository()->findBy(['deletedAt' => null]);
  echo "<h1>Pick a form to export!</h1>";
  echo "<ul>";
  foreach ($forms as $form) {
    /** @var FormEntity $form */
    $name = $form->getName() ?: '(no name)';
    $exportUrl = menu_page_url('export-forms', false) . '&exportId=' . $form->getId();
    echo '<li><a href="' . esc_url($exportUrl) . '">' .
    sprintf(
      '%s (ID: %d)',
      esc_html($name),
      (int)$form->getId()
      ) .
    '</a></li>';
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
  $settings = mailpoetProcessFormSettings($form->getSettings());
  $template = str_replace('TEMPLATE_ID', strtolower(preg_replace("/[^A-Za-z0-9]/", '_', $form->getName())), $template);
  $template = str_replace('TEMPLATE_ASSETS_DIR', strtolower(preg_replace("/[^A-Za-z0-9]/", '-', $form->getName())), $template);
  $template = str_replace('TEMPLATE_SETTINGS', mailpoetVarExport($settings), $template);
  $template = str_replace('TEMPLATE_STYLES', $form->getStyles(), $template);
  $template = str_replace('TEMPLATE_NAME', $form->getName(), $template);
  $template = str_replace('class Template', 'class ' . preg_replace("/[^A-Za-z0-9]/", '', $form->getName()), $template);

  $template = mailpoetAddStringTranslations($template);
  list($template, $assetUrls) = mailpoetProcessAssets($template);

  echo "<textarea style=\"width:90%;height:80vh;\">" . esc_textarea($template) . "</textarea>";
  if (!$assetUrls) {
    die;
  }
  echo "<h3>Assets to download</h3>";
  echo "<ul style=\"width:90%;height:10vh;\">";
  foreach ($assetUrls as $url) {
    echo "<li><a href='" . esc_url($url) . "' target='_blank'>" . esc_url($url) . "</a></li>";
  }
  echo "</ul>";
  die;
}

function mailpoetGetFormsRepository(): FormsRepository {
  if (!class_exists(ContainerWrapper::class)) {
    die('MailPoet plugin must be active!');
  }
  return ContainerWrapper::getInstance()->get(FormsRepository::class);
}

function mailpoetProcessFormSettings(array $settings): array {
  $settings['success_message'] = '';
  $settings['segments'] = [];
  $placements = [];
  $hasActivePlacement = false;
  foreach ($settings['form_placement'] as $type => $placement) {
    if (!isset($placement['enabled']) || $placement['enabled'] !== '1') {
      $placements[$type] = $type === 'others' ? [] : ['enabled' => ''];
      continue;
    }
    $hasActivePlacement = true;
    $placements[$type] = [
      'enabled' => '1',
    ];
    foreach ($placement as $settingKey => $value) {
      if (in_array($settingKey, ['styles', 'position', 'animation'])) {
        $placements[$type][$settingKey] = $value;
      }
    }
  }
  // It is a widget type
  if (!$hasActivePlacement) {
    $placements['others'] = [
      'styles' => $settings['form_placement']['others']['styles'],
    ];
  }
  $settings['form_placement'] = $placements;
  return $settings;
}

function mailpoetAddStringTranslations(string $template): string {
  // Replace label translations
  $matches = [];
  preg_match_all("/'label' => '(.+)'/u", $template, $matches);
  foreach ($matches[0] as $key => $fullMatch) {
    $stringToTranslate = $matches[1][$key];
    $template = str_replace($fullMatch, "'label' => _x('$stringToTranslate', 'Form label', 'mailpoet')", $template);
  }
  // Add todo comment to paragraphs and headings contents
  $matches = [];
  preg_match_all("/'content' => '(.+)'/u", $template, $matches);
  foreach ($matches[0] as $key => $fullMatch) {
    $content = $matches[1][$key];
    $template = str_replace($fullMatch, "'content' => '$content', // @todo Add translations, links and emoji processing.", $template);
  }
  return $template;
}

function mailpoetProcessAssets(string $template): array {
  $assetUrls = [];
  // background image urls
  $matches = [];
  preg_match_all("/'background_image_url' => '(.+)'/u", $template, $matches);
  foreach ($matches[0] as $key => $fullMatch) {
    list($assetCode, $url) = mailpoetReplaceAssetUrl($matches[1][$key]);
    if (!$assetCode) {
      continue;
    }
    $assetUrls[] = $url;
    $template = str_replace($fullMatch, "'background_image_url' => $assetCode", $template);
  }
  // Urls in url property (e.g. image block url)
  $matches = [];
  preg_match_all("/'url' => '(.+)'/u", $template, $matches);
  foreach ($matches[0] as $key => $fullMatch) {
    list($assetCode, $url) = mailpoetReplaceAssetUrl($matches[1][$key]);
    if (!$assetCode) {
      continue;
    }
    $assetUrls[] = $url;
    $template = str_replace($fullMatch, "'url' => $assetCode", $template);
  }
  return [$template, $assetUrls];
}

function mailpoetReplaceAssetUrl(string $url): array {
  $assetFile = basename(parse_url($url, PHP_URL_PATH));
  $siteUrl = get_site_url();
  // Don't touch urls from different site
  if (strpos($url, $siteUrl) === false) {
    return [];
  }
  // Don't touch url with non-image file extension
  $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
  if (in_array($ext, ['gif', 'jpg', 'jpeg', 'png']) === false) {
    return [];
  }
  return ['$this->getAssetUrl(\'' . $assetFile . '\')', $url];
}

/**
 * @see https://stackoverflow.com/questions/24316347/how-to-format-var-export-to-php5-4-array-syntax
 */
function mailpoetVarExport($var, $indent = "    "): string {
  switch (gettype($var)) {
    case 'string':
      return '\'' . addcslashes($var, "\\\$'\r\n\t\v\f") . '\'';
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
