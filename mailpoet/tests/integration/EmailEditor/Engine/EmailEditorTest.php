<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

use MailPoet\WP\Functions;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  /** @var callable */
  private $postRegisterCallback;

  public function _before() {
    parent::_before();
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
    $this->postRegisterCallback = function ($postTypes) {
      $postTypes[] = [
        'name' => 'custom_email_type',
        'args' => [],
      ];
      return $postTypes;
    };
    add_filter('mailpoet_email_editor_post_types', $this->postRegisterCallback);
    $this->emailEditor->initialize();
  }

  public function testItRegistersCustomPostTypeAddedViaHook() {
    $postTypes = get_post_types();
    $this->assertArrayHasKey('custom_email_type', $postTypes);
  }

  public function testItRestrictsAllowedBlockTypes() {
    $noEmailContext = new \WP_Block_Editor_Context();
    $blockTypes = get_allowed_block_types($noEmailContext);
    expect($blockTypes)->equals(true); // true means all blocks are allowed

    $noEmailContext = new \WP_Block_Editor_Context($settings = ['post' => (object)['post_type' => 'custom_email_type']]);
    $blockTypes = get_allowed_block_types($noEmailContext);
    expect($blockTypes)->contains('core/paragraph');
    expect($blockTypes)->contains('core/heading');
    expect($blockTypes)->contains('core/columns');
    expect($blockTypes)->contains('core/column');
    expect(count((array)$blockTypes))->equals(4);
  }

  public function testItWorksWithEmptyEditorContext() {
    $settings = [
      'test' => 1,
    ];
    $blockTypes = [
      'core/paragraph',
    ];
    $wp = $this->diContainer->get(Functions::class);
    $wp->removeAllFilters('block_editor_settings_all');
    $wp->removeAllFilters('allowed_block_types_all');
    $this->emailEditor->initialize();
    // Test without the context argument
    $filteredSettings = $wp->applyFilters('block_editor_settings_all', $settings);
    $filteredBlockTypes = $wp->applyFilters('allowed_block_types_all', $blockTypes);
    expect($filteredSettings)->equals($settings);
    expect($filteredBlockTypes)->equals($blockTypes);
    // Test with the context argument as null
    $filteredSettings = $wp->applyFilters('block_editor_settings_all', $settings, null);
    $filteredBlockTypes = $wp->applyFilters('allowed_block_types_all', $blockTypes, null);
    expect($filteredSettings)->equals($settings);
    expect($filteredBlockTypes)->equals($blockTypes);
  }

  public function _after() {
    parent::_after();
    remove_filter('mailpoet_email_editor_post_types', $this->postRegisterCallback);
  }
}
