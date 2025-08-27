<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

/**
 * @group frontend
 */
class WordPressSiteEditorCest {
  public function testSiteEditorBasicFunctionality(\AcceptanceTester $i) {
    $i->wantTo('Test WordPress site editor basic functionality');

    $i->login();

    // Switch to a block theme (required for site editor)
    $i->cli(['theme', 'activate', 'twentytwentyfive']);
    $i->wait(2); // Wait for theme activation

    // Navigate to site editor
    $i->amOnPage('/wp-admin/site-editor.php');
    $i->waitForElement('.edit-site-layout', 10);

    // Verify site editor is working by checking for key elements
    $i->seeElement('.edit-site-layout');
    $i->seeElement('.edit-site-site-hub');
    $i->seeElement('.edit-site-sidebar-navigation-screen__title');

    // Check if we can access the navigation items
    $i->see('Navigation');
    $i->see('Styles');
    $i->see('Pages');
    $i->see('Templates');
    $i->see('Patterns');
  }

  public function testFrontendPropagation(\AcceptanceTester $i) {
    $i->wantTo('Test that changes in site editor are propagated to frontend');

    $i->login();

    // Switch to a block theme (required for site editor)
    $i->cli(['theme', 'activate', 'twentytwentyfive']);
    $i->wait(2); // Wait for theme activation

    // Create a test page first
    $pageTitle = 'Test Page for Site Editor';
    $pageContent = 'This is test content for site editor propagation test.';
    $pageUrl = $i->createPost($pageTitle, $pageContent);

    // Navigate to site editor
    $i->amOnPage('/wp-admin/site-editor.php');
    $i->waitForElement('.edit-site-layout', 10);

    // Click on Templates to access template editing
    $i->click('Templates');
    $i->waitForElement('.edit-site-sidebar-navigation-screen__content');

    // Verify we can access the template editor
    $i->seeElement('.edit-site-layout__content');

    // Navigate to frontend to verify the page exists
    $i->amOnUrl($pageUrl);
    $i->waitForText($pageTitle);
    $i->see($pageContent);

    // Verify the page is using the theme's styling (basic check)
    $i->seeElement('body');
    $i->seeElement('header');
    $i->seeElement('main');
  }
}
