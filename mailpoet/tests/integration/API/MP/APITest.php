<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use Codeception\Stub;
use MailPoet\API\MP\v1\API;
use MailPoet\Settings\SettingsController;

class APITest extends \MailPoetTest {
  const VERSION = 'v1';

  public function _before(): void {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', true);
  }

  private function getApi(): API {
    return $this->diContainer->get(API::class);

  }

  public function testItUsesMultipleListsSubscribeMethodWhenSubscribingToSingleList() {
    // subscribing to single list = converting list ID to an array and using
    // multiple lists subscription method
    $API = Stub::make($this->getApi(), [
      'subscribeToLists' => function() {
        return func_get_args();
      },
    ]);
    expect($API->subscribeToList(1, 2))->equals(
      [
        1,
        [
          2,
        ],
        [],
      ]
    );
  }

  public function testItUsesMultipleListsUnsubscribeMethodWhenUnsubscribingFromSingleList() {
    // unsubscribing from single list = converting list ID to an array and using
    // multiple lists unsubscribe method
    $API = Stub::make(API::class, [
      'unsubscribeFromLists' => function() {
        return func_get_args();
      },
    ]);
    expect($API->unsubscribeFromList(1, 2))
      ->equals([
        1,
        [
          2,
        ],
      ]
    );
  }
}
