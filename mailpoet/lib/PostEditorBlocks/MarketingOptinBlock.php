<?php declare(strict_types=1);

namespace MailPoet\PostEditorBlocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Class MarketingOptinBlock
 *
 * Class for integrating marketing optin block with WooCommerce Checkout.
 */
class MarketingOptinBlock implements IntegrationInterface {
  /** @var array */
  private $options;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    array $options,
    WPFunctions $wp
  ) {
    $this->options = $options;
    $this->wp = $wp;
  }

  public function get_name(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    return 'mailpoet';
  }

  /**
   * Register block scripts and assets.
   */
  public function initialize() {
    $script_asset_path = Env::$assetsPath . '/dist/js/marketing_optin_block/marketing-optin-block-frontend.asset.php';
    $script_asset = file_exists($script_asset_path)
      ? require $script_asset_path
      : [
        'dependencies' => [],
        'version' => Env::$version,
      ];
    $this->wp->wpRegisterScript(
      'mailpoet-marketing-optin-block-frontend',
      Env::$assetsUrl . '/dist/js/marketing_optin_block/marketing-optin-block-frontend.js',
      $script_asset['dependencies'],
      $script_asset['version'],
      true
    );
    $this->registerEditorTranslations();
  }

  /**
   * Returns an array of script handles to enqueue in the frontend context.
   *
   * @return string[]
   */
  public function get_script_handles() { // phpcs:ignore
    return ['mailpoet-marketing-optin-block-frontend'];
  }

  /**
   * Returns an array of script handles to enqueue in the editor context.
   *
   * @return string[]
   */
  public function get_editor_script_handles() { // phpcs:ignore
    return [];
  }

  /**
   * An array of key, value pairs of data made available to the block on the client side.
   *
   * @return array
   */
  public function get_script_data() { // phpcs:ignore
    return $this->options;
  }

  /**
   * Workaround for registering script translations.
   * Currently, we don't generate translation files for scripts. This method enqueues an inline script
   * that renders same output as a script translation file would render, when rendered via wp_scripts()->print_translations.
   * Note that keys need to match strings in JS files
   */
  private function registerEditorTranslations() {
    $handle = 'mailpoet-marketing-optin-block-editor-script';
    $editorTranslations = <<<JS
( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( "mailpoet", { "locale_data": { "messages": { "": {} } } } );
JS;

    $translations = [
      '' => ['domain' => 'messages'],
      'marketing-opt-in-label' => [__('Marketing opt-in', 'mailpoet')],
      'marketing-opt-in-not-shown' => [__('MailPoet marketing opt-in would be shown here if enabled. You can enable from the settings page.', 'mailpoet')],
      'marketing-opt-in-enable' => [__('Enable opt-in for Checkout', 'mailpoet')],
    ];
    $editorTranslations = str_replace('{ "messages": { "": {} }', '{ "messages": ' . json_encode($translations), $editorTranslations);
    $this->wp->wpAddInlineScript($handle, $editorTranslations, 'before');
  }
}
