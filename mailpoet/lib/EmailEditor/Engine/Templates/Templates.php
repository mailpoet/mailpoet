<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Templates;

use WP_Block_Template;
use WP_Error;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class Templates {
  private $templateDirectory;
  private $pluginSlug;
  private $postType;

  public function __construct() {
      $this->templateDirectory = dirname(__FILE__) . DIRECTORY_SEPARATOR;
      $this->pluginSlug = 'mailpoet/mailpoet';
      $this->postType = 'mailpoet_email';
  }

  public function initialize(): void {
    // Since we cannot currently disable blocks in the editor for specific templates, disable templates when viewing site editor.
    // @see https://github.com/WordPress/gutenberg/issues/41062
    if (strstr(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), 'site-editor.php') === false) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
      add_filter('pre_get_block_file_template', [$this, 'getBlockFileTemplate'], 10, 3);
      add_filter('get_block_templates', [$this, 'addBlockTemplates'], 10, 3);
      add_filter('get_block_template', [$this, 'addBlockTemplateDetails'], 10, 1);
    }
  }

  public function getBlockFileTemplate($return, $templateId, $template_type) {
    $template_name_parts = explode('//', $templateId);

    if (count($template_name_parts) < 2) {
        return $return;
    }

    list( $templatePrefix, $templateSlug ) = $template_name_parts;

    if ($this->pluginSlug !== $templatePrefix) {
        return $return;
    }

    $templatePath = $templateSlug . '.html';

    if (!is_readable($this->templateDirectory . $templatePath)) {
        return $return;
    }

    return $this->getBlockTemplateFromFile($templatePath);
  }

  public function addBlockTemplates($query_result, $query, $template_type) {
    if ('wp_template' !== $template_type) {
      return $query_result;
    }

    $post_type = isset($query['post_type']) ? $query['post_type'] : '';

    if ($post_type && $post_type !== $this->postType) {
      return $query_result;
    }

    foreach ($this->getBlockTemplates() as $blockTemplate) {
      $fits_slug_query = !isset($query['slug__in']) || in_array($blockTemplate->slug, $query['slug__in'], true);
      $fits_area_query = !isset($query['area']) || ( property_exists($blockTemplate, 'area') && $blockTemplate->area === $query['area'] );
      $should_include = $fits_slug_query && $fits_area_query;

      if ($should_include) {
          $query_result[] = $blockTemplate;
      }
    }

    return $query_result;
  }

  /**
   * Add details to templates in editor.
   *
   * @param WP_Block_Template $block_template Block template object.
   * @return WP_Block_Template
   */
  public function addBlockTemplateDetails($block_template) {
    if (!$block_template) {
      return $block_template;
    }
    if (empty($block_template->title) || $block_template->title === $block_template->slug) {
      $block_template->title = $this->getBlockTemplateTitle($block_template->slug);
    }
    if (empty($block_template->description) || $block_template->description === $block_template->slug) {
      $block_template->title = $this->getBlockTemplateDescription($block_template->slug);
    }
    return $block_template;
  }

  public function getBlockTemplate($templateId) {
    $templates = $this->getBlockTemplates();
    return $templates[$templateId] ?? null;
  }

  /**
   * Gets block templates indexed by ID.
   */
  public function getBlockTemplates() {
    $file_templates = [
      $this->getBlockTemplateFromFile('email-general.html'),
    ];
    $custom_templates = $this->getCustomBlockTemplates();
    $custom_template_ids = wp_list_pluck($custom_templates, 'id');

    return array_column(
      array_merge(
        $custom_templates,
        array_filter(
          $file_templates,
          function($blockTemplate) use ($custom_template_ids) {
              return !in_array($blockTemplate->id, $custom_template_ids, true);
          }
        ),
      ),
      null,
      'id'
    );
  }

  private function getCustomBlockTemplates($slugs = [], $template_type = 'wp_template') {
      $check_query_args = [
          'post_type' => $template_type,
          'posts_per_page' => -1,
          'no_found_rows' => true,
          'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
              [
                  'taxonomy' => 'wp_theme',
                  'field' => 'name',
                  'terms' => [ $this->pluginSlug, get_stylesheet() ],
              ],
          ],
      ];

      if (is_array($slugs) && count($slugs) > 0) {
          $check_query_args['post_name__in'] = $slugs;
      }

      $check_query = new \WP_Query($check_query_args);
      $custom_templates = $check_query->posts;

      return array_map(
        function($custom_template) {
            return $this->buildBlockTemplateFromPost($custom_template);
        },
        $custom_templates
      );
  }

  public function getBlockTemplateFromFile(string $template) {
    $templateObject = $this->createNewBlockTemplateObject($template);

    return $this->buildBlockTemplateFromFile($templateObject);
  }

  private function createNewBlockTemplateObject(string $template) {
    $template_slug = $this->getBlockTemplateSlugFromPath($template);

    return (object)[
      'slug' => $this->getBlockTemplateSlugFromPath($template),
      'id' => $this->pluginSlug . '//' . $template_slug,
      'path' => $this->templateDirectory . $template,
      'type' => 'wp_template',
      'theme' => $this->pluginSlug,
      'source' => 'plugin',
      'post_types' => [
        $this->postType,
      ],
    ];
  }

  private function buildBlockTemplateFromPost($post) {
    $terms = get_the_terms($post, 'wp_theme');

    if (is_wp_error($terms)) {
        return $terms;
    }

    if (!$terms) {
      return new WP_Error('template_missing_theme', 'No theme is defined for this template.');
    }

    $theme = $terms[0]->name;

    $template = new WP_Block_Template();
    $template->wp_id = $post->ID;
    $template->id = $theme . '//' . $post->post_name;
    $template->theme = $theme;
    $template->content = $post->post_content ? $post->post_content : '<p>empty</p>' ;
    $template->slug = $post->post_name;
    $template->source = 'custom';
    $template->type = $post->post_type;
    $template->description = $post->post_excerpt;
    $template->title = $post->post_title;
    $template->status = $post->post_status;
    $template->has_theme_file = true;
    $template->is_custom = true;
    $template->post_types = [];

    if ('wp_template_part' === $post->post_type) {
      $type_terms = get_the_terms($post, 'wp_template_part_area');

      if (!is_wp_error($type_terms) && false !== $type_terms) {
        $template->area = $type_terms[0]->name;
      }
    }

    if ($this->pluginSlug === $theme) {
      $template->origin = 'plugin';
    }

    return $template;
  }

  private function buildBlockTemplateFromFile($templateObject): WP_Block_Template {
    $template = new WP_Block_Template();
    $template->id = $templateObject->id;
    $template->theme = $templateObject->theme;
    $template->content = (string)file_get_contents($templateObject->path);
    $template->source = $templateObject->source;
    $template->slug = $templateObject->slug;
    $template->type = $templateObject->type;
    $template->title = $this->getBlockTemplateTitle($templateObject->slug);
    $template->description = $this->getBlockTemplateDescription($templateObject->slug);
    $template->status = 'publish';
    $template->has_theme_file = true;
    $template->origin = $templateObject->source;
    $template->post_types = $templateObject->post_types;
    $template->is_custom = false; // Templates are only custom if they are loaded from the DB.
    $template->area = 'uncategorized';
    return $template;
  }

  private static function getBlockTemplateSlugFromPath($path) {
    return basename($path, '.html');
  }

  private function getBlockTemplateTitle($template_slug) {
    switch ($template_slug) {
      case 'email-general':
        return 'General Email';
      default:
        // Human friendly title converted from the slug.
        return ucwords(preg_replace('/[\-_]/', ' ', $template_slug));
    }
  }

  private function getBlockTemplateDescription($template_slug) {
    switch ($template_slug) {
      case 'email-general':
        return 'A general template for emails.';
      default:
        return 'A template for emails.';
    }
  }
}
