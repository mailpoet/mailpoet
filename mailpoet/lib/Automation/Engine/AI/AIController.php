<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\AI;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\WordPress;

class AIController {
  /** @var Registry */
  private $registry;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    Registry $registry,
    WordPress $wordPress
  ) {
    $this->registry = $registry;
    $this->wordPress = $wordPress;
  }

  public function generateAutomation(string $prompt): Automation {
    $triggers = $this->getAllTriggers();
    $actions = $this->getAllActions();

    // call ChatGPT AI API
    $response = \wp_remote_post('https://api.openai.com/v1/chat/completions', [
      'timeout' => 180,
      'headers' => [
        'content-type' => 'application/json; charset=utf-8',
        'authorization' => 'Bearer <token>',
      ],
      'body' => Json::encode([
        'model' => 'gpt-3.5-turbo',
        'n' => 3,
        'messages' => [
          [
            'role' => 'system',
            'content' => implode("\n", [
              implode(' ', [
                'MailPoet Automations are an email and marketing automation tool in WordPress.',
                'An automation is a graph of steps that will be executed in response to a specified action.',
                'A step is either a Trigger or an Action.',
                'Each Automation must start with a Trigger.',
                'It is followed by one or more steps.',
                'An Automation is represented by a JSON that contains a map of the steps.',
                'All your responses must contain only a valid JSON representing the automation structure.',
              ]),
            ]),
          ],
          [
            'role' => 'system',
            'content' => 'These are all the available triggers: '
              . implode(
                "\n",
                array_map(function ($trigger) {
                  return Json::encode($trigger);
                }, $triggers),
              ),
          ],
          [
            'role' => 'system',
            'content' => 'These are all the available actions: '
              . implode(
                "\n",
                array_map(function ($action) {
                  return Json::encode($action);
                }, $actions),
              ),
          ],
          ['role' => 'user', 'content' => 'welcome email series'],
          ['role' => 'assistant', 'content' => '{"root":{"id":"root","type":"root","key":"core:root","args":[],"next_steps":[{"id":"4rhde7xfe04kgc0g"}],"filters":null},"4rhde7xfe04kgc0g":{"id":"4rhde7xfe04kgc0g","type":"trigger","key":"mailpoet:someone-subscribes","args":[],"next_steps":[{"id":"20017s8kz9i8kcsc"}],"filters":null},"20017s8kz9i8kcsc":{"id":"20017s8kz9i8kcsc","type":"action","key":"mailpoet:send-email","args":{"name":"Welcome email","subject":"Subject","preheader":"","sender_name":"jj","sender_address":"jj@jj.jj"},"next_steps":[{"id":"4pw7d40ar8g0c8c8"}],"filters":null},"4pw7d40ar8g0c8c8":{"id":"4pw7d40ar8g0c8c8","type":"action","key":"core:delay","args":{"delay_type":"DAYS","delay":2},"next_steps":[{"id":"jux45feylz4ww488"}],"filters":null},"jux45feylz4ww488":{"id":"jux45feylz4ww488","type":"action","key":"mailpoet:send-email","args":{"name":"Follow-up email","subject":"Subject","preheader":"","sender_name":"jj","sender_address":"jj@jj.jj"},"next_steps":[],"filters":null}}'],
          ['role' => 'user', 'content' => $prompt],
        ],
      ]),
    ]);

    if (\is_wp_error($response)) {
      throw new \Exception('failed');
    }

    $data = Json::decode(wp_remote_retrieve_body($response));
    $choices = $data['choices'];

    $content = null;
    foreach ($choices as $choice) {
      try {
        $content = Json::decode($choice['message']['content']);
        break;
      } catch (\Throwable $e) {
      }
    }

    $steps = [];
    foreach ($content as $index => $step) {
      $steps[(string)$index] = Step::fromArray($step);
    }

    return new Automation('Test', $steps, $this->wordPress->wpGetCurrentUser(), 123);
  }

  private function getAllTriggers(): array {
    $triggers = [];
    foreach ($this->registry->getTriggers() as $trigger) {
      $triggers[] = [
        'key' => $trigger->getKey(),
        'name' => $trigger->getName(),
        'args_schema' => $trigger->getArgsSchema(),
      ];
    }
    return $triggers;
  }

  private function getAllActions(): array {
    $triggers = [];
    foreach ($this->registry->getActions() as $action) {
      $triggers[] = [
        'key' => $action->getKey(),
        'name' => $action->getName(),
        'args_schema' => $action->getArgsSchema(),
      ];
    }
    return $triggers;
  }
}
