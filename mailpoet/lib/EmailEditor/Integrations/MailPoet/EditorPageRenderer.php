<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\API\JSON\API;
use MailPoet\Config\Env;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use MailPoet\EmailEditor\Engine\User_Theme;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor as EditorInitController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController as MailPoetSettings;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class EditorPageRenderer {
  private WPFunctions $wp;

  private Settings_Controller $settingsController;

  private Theme_Controller $themeController;

  private User_Theme $userTheme;

  private CdnAssetUrl $cdnAssetUrl;

  private ServicesChecker $servicesChecker;

  private SubscribersFeature $subscribersFeature;

  private MailPoetSettings $mailpoetSettings;

  private NewslettersRepository $newslettersRepository;

  public function __construct(
    WPFunctions $wp,
    Settings_Controller $settingsController,
    CdnAssetUrl $cdnAssetUrl,
    ServicesChecker $servicesChecker,
    SubscribersFeature $subscribersFeature,
    Theme_Controller $themeController,
    User_Theme $userTheme,
    MailPoetSettings $mailpoetSettings,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->settingsController = $settingsController;
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->servicesChecker = $servicesChecker;
    $this->subscribersFeature = $subscribersFeature;
    $this->themeController = $themeController;
    $this->userTheme = $userTheme;
    $this->mailpoetSettings = $mailpoetSettings;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function render() {
    $postId = isset($_GET['post']) ? intval($_GET['post']) : 0;
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== EditorInitController::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }

    // load mailpoet email editor JS integrations
    $editorIntegrationAssetsParams = require Env::$assetsPath . '/dist/js/email_editor_integration/email_editor_integration.asset.php';
    $this->wp->wpEnqueueScript(
      'email_editor_integration',
      Env::$assetsUrl . '/dist/js/email_editor_integration/email_editor_integration.js',
      $editorIntegrationAssetsParams['dependencies'],
      $editorIntegrationAssetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'email_editor_integration',
      Env::$assetsUrl . '/dist/js/email_editor_integration/email_editor_integration.css',
      [],
      $editorIntegrationAssetsParams['version']
    );

    $assetsParams = require Env::$assetsPath . '/dist/js/email-editor/email_editor.asset.php';

    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor/email_editor.css',
      [],
      $assetsParams['version']
    );

    $jsonAPIRoot = rtrim($this->wp->escUrlRaw(admin_url('admin-ajax.php')), '/');
    $token = $this->wp->wpCreateNonce('mailpoet_token');
    $apiVersion = API::CURRENT_VERSION;
    $currentUserEmail = $this->wp->wpGetCurrentUser()->user_email;
    $this->wp->wpLocalizeScript(
      'mailpoet_email_editor',
      'MailPoetEmailEditor',
      [
        'json_api_root' => esc_js($jsonAPIRoot),
        'api_token' => esc_js($token),
        'api_version' => esc_js($apiVersion),
        'cdn_url' => esc_js($this->cdnAssetUrl->generateCdnUrl("")),
        'is_premium_plugin_active' => (bool)$this->servicesChecker->isPremiumPluginActive(),
        'current_wp_user_email' => esc_js($currentUserEmail),
        'editor_settings' => $this->settingsController->get_settings(),
        'editor_theme' => $this->themeController->get_base_theme()->get_raw_data(),
        'user_theme_post_id' => $this->userTheme->get_user_theme_post()->ID,
        'urls' => [
          'listings' => admin_url('admin.php?page=mailpoet-newsletters'),
        ],
      ]
    );

    $installedAtDiff = (new \DateTime($this->mailpoetSettings->get('installed_at')))->diff(new \DateTime());

    // Renders additional script data that some components require e.g. PremiumModal. This is done here instead of using
    // PageRenderer since that introduces other dependencies we want to avoid. Used by getUpgradeInfo.
    // some of these values are used by the powered by mailpoet block: mailpoet/assets/js/src/mailpoet-custom-email-editor-blocks/powered-by-mailpoet/
    $installer = new Installer(Installer::PREMIUM_PLUGIN_SLUG);
    $inline_script_data = [
      'mailpoet_premium_plugin_installed' => Installer::isPluginInstalled(Installer::PREMIUM_PLUGIN_SLUG),
      'mailpoet_premium_plugin_active' => $this->servicesChecker->isPremiumPluginActive(),
      'mailpoet_premium_plugin_download_url' => $this->subscribersFeature->hasValidPremiumKey() ? $installer->generatePluginDownloadUrl() : null,
      'mailpoet_premium_plugin_activation_url' => $installer->generatePluginActivationUrl(Installer::PREMIUM_PLUGIN_PATH),
      'mailpoet_has_valid_api_key' => $this->subscribersFeature->hasValidApiKey(),
      'mailpoet_has_valid_premium_key' => $this->subscribersFeature->hasValidPremiumKey(),
      'mailpoet_has_premium_support' => $this->subscribersFeature->hasPremiumSupport(),
      'mailpoet_plugin_partial_key' => $this->servicesChecker->generatePartialApiKey(),
      'mailpoet_subscribers_count' => $this->subscribersFeature->getSubscribersCount(),
      'mailpoet_subscribers_limit' => $this->subscribersFeature->getSubscribersLimit(),
      'mailpoet_subscribers_limit_reached' => $this->subscribersFeature->check(),
      // settings needed for Satismeter tracking
      'mailpoet_3rd_party_libs_enabled' => $this->mailpoetSettings->get('3rd_party_libs.enabled') === '1',
      'mailpoet_display_nps_email_editor' => $this->newslettersRepository->getCountOfEmailsWithWPPost() > 1, // Poll should be displayed only if there are 2 and more emails
      'mailpoet_display_nps_poll' => true,
      'mailpoet_current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'mailpoet_current_wp_user_firstname' => $this->wp->wpGetCurrentUser()->user_firstname,
      'mailpoet_cdn_url' => $this->cdnAssetUrl->generateCdnUrl(""),
      'mailpoet_site_url' => $this->wp->siteUrl(),
      'mailpoet_review_request_illustration_url' => $this->cdnAssetUrl->generateCdnUrl('review-request/review-request-illustration.20190815-1427.svg'),
      'mailpoet_installed_days_ago' => (int)$installedAtDiff->format('%a'),
    ];
    $this->wp->wpAddInlineScript('mailpoet_email_editor', implode('', array_map(function ($key) use ($inline_script_data) {
      return sprintf("var %s=%s;", $key, json_encode($inline_script_data[$key]));
    }, array_keys($inline_script_data))), 'before');

    // Load CSS from Post Editor
    $this->wp->wpEnqueueStyle('wp-edit-post');
    // Load CSS for the format library - used for example in popover
    $this->wp->wpEnqueueStyle('wp-format-library');

    // Enqueue media library scripts
    $this->wp->wpEnqueueMedia();

    $this->preloadRestApiData($post);

    require_once ABSPATH . 'wp-admin/admin-header.php';
    echo '<div id="mailpoet-email-editor" class="block-editor block-editor__container hide-if-no-js"></div>';
  }

  private function preloadRestApiData(\WP_Post $post): void {
    $userThemePostId = $this->userTheme->get_user_theme_post()->ID;
    $templateSlug = get_post_meta($post->ID, '_wp_page_template', true);
    $routes = [
      '/wp/v2/mailpoet_email/' . intval($post->ID) . '?context=edit',
      '/wp/v2/types/mailpoet_email?context=edit',
      '/wp/v2/global-styles/' . intval($userThemePostId) . '?context=edit', // Global email styles
      '/wp/v2/block-patterns/patterns',
      '/wp/v2/templates?context=edit',
      '/wp/v2/block-patterns/categories',
      '/wp/v2/settings',
      '/wp/v2/types?context=view',
      '/wp/v2/taxonomies?context=view',
      '/wp/v2/templates/lookup?slug=' . $templateSlug,
    ];

    // Preload the data for the specified routes
    $preloadData = array_reduce(
      $routes,
      'rest_preload_api_request',
      []
    );

    // Add inline script to set up preloading middleware
    wp_add_inline_script(
      'wp-blocks',
      sprintf(
        'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
        wp_json_encode($preloadData)
      )
    );
  }
}
