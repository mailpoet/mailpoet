<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Hooks;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Hooks\AutomationEditorLoadingHooks;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\WP\Functions as WPFunctions;

class AutomationEditorLoadingHooksTest extends \MailPoetTest {
  private AutomationStorage $automationStorage;
  private NewslettersRepository $newslettersRepository;
  private WPFunctions $wp;
  private AutomationEditorLoadingHooks $hooks;

  public function _before(): void {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->hooks = $this->diContainer->get(AutomationEditorLoadingHooks::class);
  }

  public function testItDisconnectsEmptyBlockEditorEmailUsingCanonicalWpPost(): void {
    $postId = $this->createEmailPost('<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->');
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->withWpPostId($postId)
      ->create();
    $automation = $this->createAutomationWithSendEmailStep(
      [
        'email_id' => $newsletter->getId(),
        'email_wp_post_id' => $postId,
        'subject' => $newsletter->getSubject(),
      ],
      Automation::STATUS_ACTIVE
    );

    $this->hooks->beforeEditorLoad($automation->getId());

    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $updatedStep = $updatedAutomation->getStep('send-email');
    $this->assertInstanceOf(Step::class, $updatedStep);
    $this->assertArrayNotHasKey('email_id', $updatedStep->getArgs());
    $this->assertArrayNotHasKey('email_wp_post_id', $updatedStep->getArgs());
    $this->assertSame(Automation::STATUS_DRAFT, $updatedAutomation->getStatus());
    $this->assertNull($this->newslettersRepository->findOneById($newsletter->getId()));
    $this->assertNull($this->wp->getPost($postId));
  }

  public function testItSynchronizesStaleWpPostIdFromCanonicalNewsletterPost(): void {
    $postId = $this->createEmailPost('<!-- wp:paragraph --><p>Hello world</p><!-- /wp:paragraph -->');
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->withWpPostId($postId)
      ->create();
    $automation = $this->createAutomationWithSendEmailStep([
      'email_id' => $newsletter->getId(),
      'email_wp_post_id' => 12345,
      'subject' => $newsletter->getSubject(),
    ]);

    $this->hooks->beforeEditorLoad($automation->getId());

    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $updatedStep = $updatedAutomation->getStep('send-email');
    $this->assertInstanceOf(Step::class, $updatedStep);
    $this->assertSame($postId, $updatedStep->getArgs()['email_wp_post_id']);
  }

  public function testItClearsStaleWpPostIdForClassicNewsletter(): void {
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->create();
    $automation = $this->createAutomationWithSendEmailStep([
      'email_id' => $newsletter->getId(),
      'email_wp_post_id' => 12345,
      'subject' => $newsletter->getSubject(),
    ]);

    $this->hooks->beforeEditorLoad($automation->getId());

    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $updatedStep = $updatedAutomation->getStep('send-email');
    $this->assertInstanceOf(Step::class, $updatedStep);
    $this->assertSame($newsletter->getId(), $updatedStep->getArgs()['email_id']);
    $this->assertArrayNotHasKey('email_wp_post_id', $updatedStep->getArgs());
  }

  private function createEmailPost(string $content): int {
    $postId = $this->wp->wpInsertPost([
      'post_type' => 'mailpoet_email',
      'post_status' => 'private',
      'post_title' => 'Automation Email',
      'post_content' => $content,
    ]);
    $this->assertIsInt($postId);
    $this->assertGreaterThan(0, $postId);
    return $postId;
  }

  private function createAutomationWithSendEmailStep(array $args, string $status = Automation::STATUS_DRAFT): Automation {
    $root = new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('send-email')]);
    $sendEmail = new Step('send-email', Step::TYPE_ACTION, SendEmailAction::KEY, $args, []);
    return (new AutomationFactory())
      ->withSteps([$root, $sendEmail])
      ->withStatus($status)
      ->create();
  }
}
