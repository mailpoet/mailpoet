<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaFormRenderer;
use MailPoet\Captcha\PageRenderer;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;

class PageRendererTest extends \MailPoetTest {
  public function testItLeavesUnrelatedContentUntouchedWhenFormCannotRender(): void {
    $pageRenderer = $this->getPageRenderer(false);

    verify($pageRenderer->setPageContent('Unrelated post body'))->equals('Unrelated post body');
  }

  public function testItRemovesShortcodeWhenFormCannotRender(): void {
    $pageRenderer = $this->getPageRenderer(false);

    verify($pageRenderer->setPageContent('before [mailpoet_page] after'))->equals('before  after');
  }

  public function testItInjectsFormWhenItCanRender(): void {
    $pageRenderer = $this->getPageRenderer('<form>captcha</form>');

    verify($pageRenderer->setPageContent('before [mailpoet_page] after'))->equals('before <form>captcha</form> after');
  }

  private function getPageRenderer($formContent): PageRenderer {
    $pageRenderer = new PageRenderer(
      $this->diContainer->get(WPFunctions::class),
      $this->makeEmpty(CaptchaFormRenderer::class, ['render' => $formContent]),
      $this->diContainer->get(AssetsController::class)
    );
    $pageRenderer->render(['captcha_session_id' => 'abc']);
    return $pageRenderer;
  }
}
