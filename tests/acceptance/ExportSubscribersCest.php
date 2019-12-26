<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class ExportSubscribersCest {
  public function exportSubscribers(\AcceptanceTester $I) {
    $segment_factory = new Segment();
    $segment_name = 'Hobbyists';
    $segment = $segment_factory->withName($segment_name)->create();
    $subscriber_factory = new Subscriber();
    $subscriber_factory->withSegments([$segment])->withEmail('one@fake.fake')->create();
    $subscriber_factory->withSegments([$segment])->withEmail('two@fake.fake')->create();
    $subscriber_factory->withSegments([$segment])->withEmail('three@fake.fake')->create();
    $I->wantTo('Export a list of subscribers');
    $I->login();
    $I->amOnMailPoetPage('Subscribers');
    //export those users
    $I->click(['xpath' => '//*[@id="mailpoet_export_button"]']);
    //choose new list
    $I->selectOptionInSelect2($segment_name);
    //export
    $I->click('.button-primary.mailpoet_export_process');
    $I->waitForText('3 subscribers were exported. Get the exported file here.');
    $I->seeNoJSErrors();
  }
}
