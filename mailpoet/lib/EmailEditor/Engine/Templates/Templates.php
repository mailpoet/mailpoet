<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Templates;

use MailPoet\DI\ContainerWrapper;
use MailPoet\EmailEditor\Engine\EmailStylesSchema;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\Validator\Builder;
use WP_Block_Template;
use WP_Error;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class Templates {
  const MAILPOET_EMAIL_META_THEME_TYPE = 'mailpoet_email_theme';

  private string $templateDirectory;
  private string $pluginSlug;
  private string $postType;
  private array $themeJson = [];

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
      add_filter('theme_templates', [$this, 'addThemeTemplates'], 10, 4); // Needed when saving post – template association
      add_filter('get_block_template', [$this, 'addBlockTemplateDetails'], 10, 1);
      add_filter('rest_pre_insert_wp_template', [$this, 'trimPostContent'], 10, 2);
      // Register custom post meta for mailpoet_email_theme
      $this->registerTemplateThemeFields();
      // Rest field for compiled CSS used in template preview
      register_rest_field(
        'wp_template',
        'email_theme_css',
        [
          'get_callback' => [$this, 'getEmailThemeCss'],
          'update_callback' => null,
          'schema' => Builder::string()->toArray(),
        ]
      );
    }
  }

  /**
   * Trims post content when saving a template.
   * We add empty space on client to force saving the content.
   */
  public function trimPostContent($changes, \WP_REST_Request $request) {
    if (strpos($request->get_route(), '/wp/v2/templates/mailpoet/mailpoet') === 0 && !empty($changes->post_content)) {
      $changes->post_content = trim($changes->post_content);
    }
    return $changes;
  }

  public function getBlockTemplate($templateId) {
    $templates = $this->getBlockTemplates();
    return $templates[$templateId] ?? null;
  }

  public function getBlockTheme($templateId, $templateWpId = null) {
    // First check if there is a user updated theme saved
    if ($templateWpId) {
      $theme = get_post_meta($templateWpId, self::MAILPOET_EMAIL_META_THEME_TYPE, true);
      if (is_array($theme) && isset($theme['styles'])) {
        return $theme;
      }
    }

    // If there is no user edited theme, look for default template themes in files
    $template_name_parts = explode('//', $templateId);

    if (count($template_name_parts) < 2) {
        return false;
    }

    list( $templatePrefix, $templateSlug ) = $template_name_parts;

    if ($this->pluginSlug !== $templatePrefix) {
      return false;
    }

    if (!isset($this->themeJson[$templateSlug])) {
      $templatePath = $templateSlug . '.json';
      $jsonFile = $this->templateDirectory . $templatePath;

      if (file_exists($jsonFile)) {
        $this->themeJson[$templateSlug] = json_decode((string)file_get_contents($jsonFile), true);
      }
    }

    return $this->themeJson[$templateSlug] ?? [];
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

  public function addThemeTemplates($templates, $theme, $post, $post_type) {
    if ($post_type && $post_type !== $this->postType) {
      return $templates;
    }
    foreach ($this->getBlockTemplates() as $blockTemplate) {
      $templates[$blockTemplate->slug] = $blockTemplate;
    }
    return $templates;
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

  /**
   * Gets block templates indexed by ID.
   */
  public function getBlockTemplates() {
    $file_templates = [
      $this->getBlockTemplateFromFile('email-general.html'),
      $this->getBlockTemplateFromFile('awesome-one.html'),
      $this->getBlockTemplateFromFile('awesome-two.html'),
      $this->getBlockTemplateFromFile('email-computing-mag.html'),
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

  /**
   * Generates CSS for
   */
  public function getEmailThemeCss($template): string {
    $themeController = ContainerWrapper::getInstance()->get(ThemeController::class);
    $editorTheme = clone $themeController->getTheme();
    $templateTheme = $this->getBlockTheme($template['id'], $template['wp_id']);
    if (is_array($templateTheme)) {
      $editorTheme->merge(new \WP_Theme_JSON($templateTheme, 'custom'));
    }
    return $editorTheme->get_stylesheet();
  }

  private function registerTemplateThemeFields(): void {
    register_post_meta(
      'wp_template',
      self::MAILPOET_EMAIL_META_THEME_TYPE,
      [
        'show_in_rest' => [
          'schema' => (new EmailStylesSchema())->getSchema(),
        ],
        'single' => true,
        'type' => 'object',
        'default' => ['version' => 2], // The version 2 is important to merge themes correctly
      ]
    );

    register_rest_field('wp_template', self::MAILPOET_EMAIL_META_THEME_TYPE, [
      'get_callback' => function($object) {
         return $this->getBlockTheme($object['id'], $object['wp_id']);
      },

      'update_callback' => function($value, $template) {
        return update_post_meta($template->wp_id, self::MAILPOET_EMAIL_META_THEME_TYPE, $value);
      },
      'schema' => (new EmailStylesSchema())->getSchema(),
    ]);
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
