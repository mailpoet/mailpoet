<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Templates;

use WP_Block_Template;

class Templates {
  private $templateDirectory;

  public function __construct() {
      $this->templateDirectory = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  }

  public function getBlockTemplateFromFile(string $template) {
    $templateObject = $this->createNewBlockTemplateObject($template);

    return $this->buildBlockTemplateFromFile($templateObject);
  }

  private function createNewBlockTemplateObject(string $template) {
    return (object)[
        'slug' => basename($template),
        'id' => 'mailpoet//' . basename($template),
        'path' => $this->templateDirectory . $template,
        'type' => 'wp_template',
        'theme' => 'mailpoet',
        'source' => 'plugin',
        'post_types' => [
          'mailpoet_email',
        ],
    ];
  }

  private function buildBlockTemplateFromFile($templateObject): WP_Block_Template {
      $template = new WP_Block_Template();
      $template->id = $templateObject->id;
      $template->theme = $templateObject->theme;
      $template->content = (string)file_get_contents($templateObject->path);
      $template->source = $templateObject->source;
      $template->slug = $templateObject->slug;
      $template->type = $templateObject->type;
      $template->title = $templateObject->slug;
      $template->description = $templateObject->slug;
      $template->status = 'publish';
      // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $template->has_theme_file = false;
      $template->origin = $templateObject->source;
       // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $template->post_types = $templateObject->post_types;
      // Templates are only custom if they are loaded from the DB.
       // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $template->is_custom = false;
      $template->area = 'uncategorized';
      return $template;
  }
}
