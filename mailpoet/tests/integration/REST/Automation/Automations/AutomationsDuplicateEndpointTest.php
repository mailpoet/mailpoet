<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\REST\Automation\AutomationTest;

require_once __DIR__ . '/../AutomationTest.php';

//phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

class AutomationsDuplicateEndpointTest extends AutomationTest {
  private const ENDPOINT_PATH = '/mailpoet/v1/automations/%d/duplicate';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $id = $this->automationStorage->createAutomation(
      new Automation(
        'Testing automation',
        ['root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [])],
        wp_get_current_user()
      )
    );
    $automation = $this->automationStorage->getAutomation($id);
    $this->assertInstanceOf(Automation::class, $automation);
    $this->automation = $automation;
  }

  public function testEditorIsAllowed(): void {
    wp_set_current_user($this->editorUserId);
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame('Copy of Testing automation', $data['data']['name']);
    $this->assertNotNull($this->automationStorage->getAutomation($this->automation->getId() + 1));
  }

  public function testGuestNotAllowed(): void {
    wp_set_current_user(0);
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $this->assertSame([
      'code' => 'rest_forbidden',
      'message' => 'Sorry, you are not allowed to do that.',
      'data' => ['status' => 401],
    ], $data);

    $automation = $this->automationStorage->getAutomation($this->automation->getId());
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertSame('Testing automation', $automation->getName());
    $this->assertNull($this->automationStorage->getAutomation($this->automation->getId() + 1));
  }

  public function testItDuplicatesAnAutomation(): void {
    $data = $this->post(sprintf(self::ENDPOINT_PATH, $this->automation->getId()));

    $id = $this->automation->getId() + 1;
    $user = wp_get_current_user();
    $createdAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['created_at'] ?? null);
    $updatedAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, $data['data']['updated_at'] ?? null);

    $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
    $this->assertInstanceOf(DateTimeImmutable::class, $updatedAt);
    $this->assertEquals($createdAt, $updatedAt);

    $expected = [
      'id' => $id,
      'name' => 'Copy of Testing automation',
      'status' => 'draft',
      'created_at' => $createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $updatedAt->format(DateTimeImmutable::W3C),
      'activated_at' => null,
      'author' => [
        'id' => $user->ID,
        'name' => $user->display_name,
      ],
      'stats' => [
        'automation_id' => $id,
        'totals' => [
          'entered' => 0,
          'in_progress' => 0,
          'exited' => 0,
        ],
        'emails' => [
          'sent' => 0,
          'opened' => 0,
          'clicked' => 0,
          'revenue' => [
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
            'value' => 0,
            'count' => 0,
          ],
        ],
      ],
      'steps' => [
        'root' => [
          'id' => 'root',
          'type' => 'root',
          'key' => 'core:root',
          'args' => [],
          'next_steps' => [],
          'filters' => null,
        ],
      ],
      'meta' => [],
    ];
    $this->assertSame(['data' => $expected], $data);

    $expectedAutomation = Automation::fromArray(
      array_merge($expected, ['steps' => json_encode($expected['steps']), 'version_id' => 1])
    );
    $automation = $this->automationStorage->getAutomation($id);
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertTrue($automation->equals($expectedAutomation));
  }

  public function testItDuplicatesAutomationEmailWhenDuplicatingAutomation(): void {
    $newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSubject('Original email');
    $newsletter->setSenderName('Sender');
    $newsletter->setSenderAddress('sender@example.com');
    $newslettersRepository->persist($newsletter);
    $newslettersRepository->flush();
    $originalNewsletterId = $newsletter->getId();
    $this->assertIsInt($originalNewsletterId);

    $automationId = $this->automationStorage->createAutomation(
      new Automation(
        'Testing automation with email',
        [
          'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('send-email')]),
          'send-email' => new Step(
            'send-email',
            Step::TYPE_ACTION,
            'mailpoet:send-email',
            [
              'email_id' => $originalNewsletterId,
              'subject' => 'Original email',
              'sender_name' => 'Sender',
              'sender_address' => 'sender@example.com',
            ],
            []
          ),
        ],
        wp_get_current_user()
      )
    );

    $data = $this->post(sprintf(self::ENDPOINT_PATH, $automationId));

    $sendEmailStep = $this->getStepByKey($data['data']['steps'], 'mailpoet:send-email');
    $this->assertIsArray($sendEmailStep);
    $duplicatedNewsletterId = (int)$sendEmailStep['args']['email_id'];
    $this->assertNotSame($originalNewsletterId, $duplicatedNewsletterId);
    $this->assertSame('Copy of Original email', $sendEmailStep['args']['subject']);

    $duplicatedNewsletter = $newslettersRepository->findOneBy(['id' => $duplicatedNewsletterId]);
    $this->assertInstanceOf(NewsletterEntity::class, $duplicatedNewsletter);
    $this->assertSame('Copy of Original email', $duplicatedNewsletter->getSubject());
    $this->assertSame(NewsletterEntity::STATUS_ACTIVE, $duplicatedNewsletter->getStatus());

    $originalNewsletter = $newslettersRepository->findOneBy(['id' => $originalNewsletterId]);
    $this->assertInstanceOf(NewsletterEntity::class, $originalNewsletter);
    $this->assertSame('Original email', $originalNewsletter->getSubject());
  }

  public function testItPreservesMetaAndStepFiltersWhenDuplicatingAutomation(): void {
    $filters = new Filters(
      Filters::OPERATOR_AND,
      [
        new FilterGroup(
          'group-1',
          FilterGroup::OPERATOR_AND,
          [
            new Filter(
              'filter-1',
              'string',
              'mailpoet:subscriber:email',
              'contains',
              ['value' => 'example.com']
            ),
          ]
        ),
      ]
    );
    $automation = new Automation(
      'Testing automation with meta and filters',
      [
        'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('delay')]),
        'delay' => new Step(
          'delay',
          Step::TYPE_ACTION,
          'core:delay',
          [
            'delay' => 1,
            'delay_type' => 'HOURS',
          ],
          [],
          $filters
        ),
      ],
      wp_get_current_user()
    );
    $automation->setMeta('mailpoet:run-once-per-subscriber', true);
    $automationId = $this->automationStorage->createAutomation($automation);

    $data = $this->post(sprintf(self::ENDPOINT_PATH, $automationId));

    $this->assertSame(['mailpoet:run-once-per-subscriber' => true], $data['data']['meta']);

    $delayStep = $this->getStepByKey($data['data']['steps'], 'core:delay');
    $this->assertIsArray($delayStep);
    $this->assertSame($filters->toArray(), $delayStep['filters']);
  }

  private function getStepByKey(array $steps, string $key): ?array {
    foreach ($steps as $step) {
      if ($step['key'] === $key) {
        return $step;
      }
    }
    return null;
  }
}
