<?php declare(strict_types = 1);

namespace MailPoet\Form;

use Codeception\Util\Fixtures;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\TrackingConsentCapture;
use MailPoet\Subscribers\TrackingConsentController;

class RendererTest extends \MailPoetTest {
  public function testItRendersFormBody() {
    $formBody = Fixtures::get('form_body_template');
    $renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->assertInstanceOf(Renderer::class, $renderer);
    $formHtml = $renderer->renderBlocks($formBody);
    verify($formHtml)->stringContainsString('<input type="email" name="data[email]"/>'); // honey pot
    verify($formHtml)->stringContainsString('input type="submit" class="mailpoet_submit" value="Subscribe!"'); // Subscribe button
  }

  public function testItHidesTheTrackingConsentBlockWhenTheSiteTracksEveryone() {
    $renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->assertInstanceOf(Renderer::class, $renderer);

    // Default state ("Track everyone, don't ask"): a block the merchant placed
    // deliberately still renders nothing, so nobody is asked a question the
    // site owner has chosen not to ask.
    $formHtml = $renderer->renderBlocks([$this->getConsentBlock()], [], null, false, false);
    verify($formHtml)->stringNotContainsString($this->getConsentFieldName());
    verify($formHtml)->stringNotContainsString('Allow tracking of email opens');
  }

  public function testItRendersTheTrackingConsentBlockUncheckedWhenAsking() {
    $settings = ContainerWrapper::getInstance()->get(SettingsController::class);
    $this->assertInstanceOf(SettingsController::class, $settings);
    $settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
    $renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->assertInstanceOf(Renderer::class, $renderer);

    $formHtml = $renderer->renderBlocks([$this->getConsentBlock()], [], null, false, false);
    verify($formHtml)->stringContainsString($this->getConsentFieldName());
    verify($formHtml)->stringContainsString('Allow tracking of email opens');
    // A pre-ticked consent box is not valid consent (CJEU Planet49).
    verify($formHtml)->stringNotContainsString('checked="checked"');
  }

  /** The rendered input name is obfuscated against spambots, like every other form field. */
  private function getConsentFieldName(): string {
    $obfuscator = ContainerWrapper::getInstance()->get(FieldNameObfuscator::class);
    $this->assertInstanceOf(FieldNameObfuscator::class, $obfuscator);
    return 'data[' . $obfuscator->obfuscate(TrackingConsentCapture::FIELD_ID) . ']';
  }

  private function getConsentBlock(): array {
    return [
      'type' => 'checkbox',
      'id' => TrackingConsentCapture::FIELD_ID,
      'name' => 'Tracking consent',
      'params' => [
        'label' => 'Email activity tracking',
        'values' => [['value' => 'Allow tracking of email opens and link clicks']],
      ],
    ];
  }
}
