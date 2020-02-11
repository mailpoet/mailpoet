<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class DisplayFormBellowPostCest {

  /** @var Segment */
  private $segments;

  /** @var Form */
  private $forms;

  protected function _inject(Segment $segments, Form $forms) {
    $this->segments = $segments;
    $this->forms = $forms;
  }

  public function displayForm(\AcceptanceTester $i) {
    $i->wantTo('Test form rendering bellow posts');

    $segmentName = 'Fancy List';
    $segment = $this->segments->withName($segmentName)->create();
    $formName = 'My fancy form';
    $this->forms->withName($formName)->withSegments([$segment])->create();

    $postTitle = 'Lorem';
    $postText = 'Ipsum';
    $post = $i->cliToArray(['post', 'create', '--format=json', '--porcelain', '--post_status=publish', '--post_type=post', '--post_title=' . $postTitle, '--post_content=' . $postText]);
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->click('.form-sidebar-form-placement-panel');
    $i->checkOption('[data-automation-id="place-form-bellow-all-posts-toggle"] input');
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');

    // see the post
    $i->amOnPage('/wp-admin/post.php?action=edit&post=' . $post[0]);
    $i->waitForText($postTitle, 30);
    $i->click('View Post');
    $i->waitForText($postTitle);
    $i->seeElement('[data-automation-id="subscribe-submit-button"]');
  }
}
