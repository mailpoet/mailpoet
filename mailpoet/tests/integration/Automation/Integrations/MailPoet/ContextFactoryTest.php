<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet;

use MailPoet\Automation\Integrations\MailPoet\ContextFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;

class ContextFactoryTest extends \MailPoetTest {
  private ContextFactory $contextFactory;

  private SettingsController $settings;

  private UserFlagsController $userFlags;

  public function _before(): void {
    parent::_before();
    wp_set_current_user(1);
    $this->contextFactory = $this->diContainer->get(ContextFactory::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->userFlags = $this->diContainer->get(UserFlagsController::class);
    $this->userFlags->delete('remember_email_editor_choice');
    $this->userFlags->delete('last_email_editor_choice');
  }

  public function _after(): void {
    $this->userFlags->delete('remember_email_editor_choice');
    $this->userFlags->delete('last_email_editor_choice');
    parent::_after();
  }

  public function testItExposesBlockEmailEditorAvailability(): void {
    $context = $this->contextFactory->getContextData();

    $this->assertArrayHasKey('block_email_editor_enabled', $context);
    $this->assertIsBool($context['block_email_editor_enabled']);
    $this->assertArrayHasKey('editor_choice_modal_enabled', $context);
    $this->assertIsBool($context['editor_choice_modal_enabled']);
    $this->assertArrayHasKey('last_email_editor_choice', $context);
  }

  public function testItFollowsTheSettingWhenTheUserHasNotDecided(): void {
    $this->settings->set('editor_choice_modal.enabled', true);
    $this->assertSame($this->isBlockEmailEditorEnabled(), $this->isEditorChoiceModalEnabled());

    $this->settings->set('editor_choice_modal.enabled', false);
    $this->assertFalse($this->isEditorChoiceModalEnabled());
  }

  public function testTheUserDecisionOverridesTheSetting(): void {
    $this->settings->set('editor_choice_modal.enabled', true);
    $this->userFlags->set('remember_email_editor_choice', 1);
    $this->assertFalse($this->isEditorChoiceModalEnabled());

    $this->settings->set('editor_choice_modal.enabled', false);
    $this->userFlags->set('remember_email_editor_choice', 0);
    $this->assertSame($this->isBlockEmailEditorEnabled(), $this->isEditorChoiceModalEnabled());
  }

  public function testItExposesTheLastEditorChoice(): void {
    $this->assertNull($this->contextFactory->getContextData()['last_email_editor_choice']);

    $this->userFlags->set('last_email_editor_choice', 'block');
    $this->assertSame('block', $this->contextFactory->getContextData()['last_email_editor_choice']);
  }

  private function isEditorChoiceModalEnabled(): bool {
    return (bool)$this->contextFactory->getContextData()['editor_choice_modal_enabled'];
  }

  private function isBlockEmailEditorEnabled(): bool {
    return (bool)$this->contextFactory->getContextData()['block_email_editor_enabled'];
  }
}
