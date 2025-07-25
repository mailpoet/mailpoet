<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Builder\UpdateStepsController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoetTest;

class UpdateStepsControllerTest extends MailPoetTest {
  public function testItDuplicatesNewsletterOnStepDuplication(): void {
    $newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    // Create a newsletter to be used by the action
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSubject('Original Subject');
    $newsletter->setPreheader('Original Preheader');
    $newsletter->setSenderName('Sender');
    $newsletter->setSenderAddress('sender@example.com');
    $newslettersRepository->persist($newsletter);
    $newslettersRepository->flush();
    $originalNewsletterId = $newsletter->getId();

    // Prepare a SendEmailAction step with stepDuplicated flag
    $step = new Step(
      'a1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      [
        'email_id' => $originalNewsletterId,
        'stepDuplicated' => true,
        'subject' => 'Original Subject',
      ],
      [new NextStep('end')]
    );
    $automation = new Automation('Test automation', [$step->getId() => $step], \wp_get_current_user());

    $updateStepsController = $this->diContainer->get(UpdateStepsController::class);
    $updateStepsController->updateSteps($automation, [
      $step->getId() => $step->toArray(),
    ]);

    $updatedStep = $automation->getStep('a1');
    $this->assertNotNull($updatedStep);
    $this->assertNotEquals($originalNewsletterId, $updatedStep->getArgs()['email_id']);
    $duplicatedNewsletter = $newslettersRepository->findOneBy(['id' => $updatedStep->getArgs()['email_id']]);
    $this->assertNotNull($duplicatedNewsletter);
    $this->assertEquals('Copy of Original Subject', $duplicatedNewsletter->getSubject());
  }
}
