<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Exception\ElementNotFound;
use Throwable;

class SwitchingLanguagesCest {
  public function switchLanguage(AcceptanceTester $i): void {
    // We don't want to run the test on release branch because in our release process
    // the language packs are not prepared at the time we crate the branch
    if (getenv('CIRCLE_BRANCH') === 'release') {
      return;
    }
    $i->login();

    $i->wantTo('Switch WordPress language to German');
    $i->amOnAdminPage('/options-general.php');
    $i->selectOption('WPLANG', ['value' => 'de_DE']);
    $i->click('[name="submit"]');

    $i->wantTo('Update translations to make sure strings are downloaded');

    // translations may not be scheduled for update yet, retry multiple times in that case
    for ($attemtps = 0; $attemtps < 5; $attemtps++) {
      try {
        $i->wait($attemtps);
        $i->amOnAdminPage('update-core.php');
        $i->waitForText('WordPress-Aktualisierungen');
        // Wait before clicking the update button to prevent triggering too many requests too translate.wordpress.com within one second
        $i->wait(1);
        $i->click('Übersetzungen aktualisieren');
        $i->waitForText('Weiter zur WordPress-Aktualisierungs-Seite');
        break;
      } catch (ElementNotFound $e) {
        // translations are not yet scheduled for update, or are already up-to-date
      }
    }

    $i->wantTo('Check menu strings (translated in PHP)');
    // The page load after updating languages also triggers a WP check that calls translate.wordpress.com so we wait a bit to prevent triggering too many requests error
    $i->wait(1);
    $i->amOnMailpoetPage('newsletters');
    $i->waitForText('E-Mails');
    $i->waitForText('Automatisierungen');
    $i->waitForText('Formulare');
    $i->waitForText('Abonnenten');
    $i->waitForText('Listen');
    $i->waitForText('Einstellungen');
    $i->waitForText('Hilfe');

    $i->wantTo('Check email listing strings (translated with MailPoet.i18n)');
    $i->waitForText('Newsletter');
    $i->waitForText('Willkommens-E-Mail');
    $i->waitForText('Benachrichtigung über neueste Beiträge');
    $i->waitForText('Wiederaufnahme-E-Mails');

    $i->wantTo('Check automation templates strings (translated with @wordpress/i18n)');
    $i->amOnMailpoetPage('automation-templates');
    $i->waitForText('Wähle dein Automatisierungstemplate');
    $i->waitForText('Von Grund');
    $i->waitForText('Von Grund auf neu erstellen');
  }

  public function _after(AcceptanceTester $i) {
    try {
      $i->cli(['language', 'core', 'uninstall', 'de_DE']);
    } catch (Throwable $e) {
      // language already uninstalled
    }
  }
}
