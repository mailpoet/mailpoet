<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;

class PostContent extends AbstractBlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return $this->renderContent($blockContent, $parsedBlock, $settingsController);
  }

  protected function renderContent(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    global $post;

    /** This filter is documented in wp-includes/post-template.php */
    return apply_filters('the_content', str_replace(']]>', ']]&gt;', $post->post_content)); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
