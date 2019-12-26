<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class CreateNewSegmentCest {
  public function createUserRoleSegment(\AcceptanceTester $I) {
    $I->wantTo('Create a new WP user role segment');
    $segment_title = 'Create User Role Segment Test';
    $I->login();
    $I->amOnMailpoetPage('Segments');
    $I->click('[data-automation-id="new-segment"]');
    $I->seeInCurrentUrl('#/new');
    $I->fillField(['name' => 'name'], $segment_title);
    $I->fillField(['name' => 'description'], 'Lorem ipsum dolor amed');
    $I->selectOption('form select[name=segmentType]', 'WordPress user roles');
    $I->selectOption('form select[name=wordpressRole]', 'Editor');
    $I->click('Save');
    $I->amOnMailpoetPage('Segments');
    $I->waitForText($segment_title, 20);
  }

  public function createEmailSegment(\AcceptanceTester $I) {
    $I->wantTo('Create a new email segment');
    $email_subject = 'Segment Email Test';
    $newsletter_factory = new Newsletter();
    $newsletter_factory->withSubject($email_subject)->create();
    $segment_title = 'Create Email Segment Test';
    $I->login();
    $I->amOnMailpoetPage('Segments');
    $I->click('[data-automation-id="new-segment"]');
    $I->seeInCurrentUrl('#/new');
    $I->fillField(['name' => 'name'], $segment_title);
    $I->fillField(['name' => 'description'], 'Lorem ipsum dolor amed');
    $I->selectOption('form select[name=segmentType]', 'Email');
    $I->selectOption('form select[name=action]', 'opened');
    $I->click('#select2-newsletter_id-container');
    $I->selectOptionInSelect2($email_subject);
    $I->click('Save');
    $I->amOnMailpoetPage('Segments');
    $I->waitForText($segment_title, 20);
  }
}
