<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\Env;
use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use MailPoet\EmailEditor\Engine\User_Theme;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditorLoader {

  private WPFunctions $wp;
  private Settings_Controller $settingsController;
  private Theme_Controller $themeController;
  private User_Theme $userTheme;
  private CdnAssetUrl $cdnAssetUrl;
  private NewslettersRepository $newslettersRepository;

  public function __construct(
    WPFunctions $wp,
    Settings_Controller $settingsController,
    Theme_Controller $themeController,
    User_Theme $userTheme,
    CdnAssetUrl $cdnAssetUrl,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->settingsController = $settingsController;
    $this->themeController = $themeController;
    $this->userTheme = $userTheme;
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function initialize(): void {
    $this->wp->addAction('mailpoet_email_editor_admin_initialized', [$this, 'initializeAdmin']);
    $this->wp->addFilter('block_editor_settings_all', [$this, 'blockEditorSettings'], 10, 2);
    $this->wp->addFilter('rest_request_after_callbacks', [$this, 'modifyThemeResponse'], 10, 3);
  }

  public function initializeAdmin(): void {
    if (!$this->isEmailEditorPage()) {
      return;
    }
    $this->wp->addAction('mailpoet_is_email_editor_page', [$this, 'isEmailEditorPage']);
    $this->enqueueBlockEditorAssets();
  }

  public function isEmailEditorPage($isEditorPage = false): bool {
    if ($isEditorPage) {
      return true;
    }
    $current_screen = get_current_screen();
    return $current_screen && $current_screen->post_type === EmailEditor::MAILPOET_EMAIL_POST_TYPE && $current_screen->base === 'post'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function enqueueBlockEditorAssets() {
    $postId = isset($_GET['post']) ? intval($_GET['post']) : 0;
    $post = $this->wp->getPost($postId);
    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => $postId]);
    if (!$newsletter instanceof NewsletterEntity) {
      return;
    }

    // Email editor rich text JS - Because we Personalization Tags depend on Gutenberg 19.8.0 and higher
    // the following code replaces used Rich Text for the version containing the necessary changes.
    $assetsParams = require Env::$assetsPath . '/dist/js/email-editor/rich-text.asset.php';
    $this->wp->wpDeregisterScript('wp-rich-text');
    $this->wp->wpEnqueueScript(
      'wp-rich-text',
      Env::$assetsUrl . '/dist/js/email-editor/rich-text.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );

    $assetsParams = require Env::$assetsPath . '/dist/js/email-editor-unified/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor-unified/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpLocalizeScript(
      'mailpoet_email_editor',
      'MailPoetEmailEditor',
      [
        'editor_settings' => $this->settingsController->get_settings(),
        'editor_theme' => $this->themeController->get_base_theme()->get_raw_data(),
        'user_theme_post_id' => $this->userTheme->get_user_theme_post()->ID,
        'current_post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
        'current_post_id' => $post->ID,
        'mailpoet_cdn_url' => $this->cdnAssetUrl->generateCdnUrl(""),
        'urls' => [
          'listings' => admin_url('admin.php?page=mailpoet-newsletters'),
          'send' => admin_url('admin.php?page=mailpoet-newsletters#/send/' . $newsletter->getId()),
        ],
      ]
    );

    $this->wp->wpEnqueueStyle(
      'email_editor_unified',
      Env::$assetsUrl . '/dist/js/email-editor-unified/email_editor.css',
      [],
      $assetsParams['version']
    );
  }

  public function blockEditorSettings($settings, $editorContext) {
    if (!$this->isEmailEditorPage()) {
      return $settings;
    }
    $controllerSettings = $this->settingsController->get_settings();
    return $controllerSettings;
  }

  public function modifyThemeResponse($response, $handler, $request) {
//    ///wp/v2/global-styles/themes/twentytwentyfive
//    if (strpos($request->get_route(), '/wp/v2/global-styles/themes/') === 0) {
//      $response->data = $this->themeController->get_base_theme()->get_raw_data();
//      return $response;
//    }
//    ///wp/v2/global-styles/id
//    if (strpos($request->get_route(), '/wp/v2/global-styles/') === 0) {
//      $response->data = $this->userTheme->get_theme()->get_raw_data();
//      return $response;
//    }
    return $response;
  }
}
