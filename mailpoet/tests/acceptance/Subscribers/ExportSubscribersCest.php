<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class ExportSubscribersCest {
  public function exportSubscribers(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Hobbyists';
    $segment = $segmentFactory->withName($segmentName)->create();
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->withEmail('one@fake.fake')->create();
    $subscriberFactory->withSegments([$segment])->withEmail('two@fake.fake')->create();
    $subscriberFactory->withSegments([$segment])->withEmail('three@fake.fake')->create();
    $i->wantTo('Export a list of subscribers');
    $i->login();
    $i->amOnMailPoetPage('Subscribers');
    //export those users
    $i->click(['xpath' => '//*[@id="mailpoet_export_button"]']);
    //choose new list
    $i->selectOptionInSelect2($segmentName);
    //export
    $i->click('#mailpoet-export-button');
    $i->waitForText('3 subscribers were exported. Get the exported file here.');
    $i->seeNoJSErrors();
  }
}
