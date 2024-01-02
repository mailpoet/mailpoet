<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Exception\ElementNotFound;
use MailPoet\Test\DataFactories\Automation;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use Throwable;

class SwitchingLanguagesCest {
  public function _before(\AcceptanceTester $i) {
    $i->wantToTest('Prepare data for testing');

    $segmentFactory = new Segment();
    $segment = $segmentFactory
      ->withName('Simple segment')
      ->create();

    $emailSubject = 'Simple newsletter';
    $newsletter = new Newsletter();
    $newsletter
      ->withSendingQueue()
      ->withSubject($emailSubject)
      ->withSegments([$segment])
      ->withSentStatus()
      ->create();

    $automation = new Automation();
    $automation
      ->withName('Simple automation')
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->withMeta('mailpoet:run-once-per-subscriber', false)
      ->withStatusActive()
      ->create();

    $form = new Form();
    $form->withName('Simple form')
      ->withDisplayBelowPosts()
      ->create();

    $subscriber = new Subscriber();
    $subscriber
      ->withEmail('test@example.com')
      ->withStatus('unconfirmed')
      ->create();
  }

  public function switchLanguage(AcceptanceTester $i): void {
    // We don't want to run the test on release branch because in our release process
    // the language packs are not prepared at the time we create the branch
    if (getenv('CIRCLE_BRANCH') === 'release') {
      return;
    }
    $i->login();

    $i->wantTo('Switch WordPress language to German');
    $i->amOnAdminPage('/options-general.php');
    $i->selectOption('WPLANG', ['value' => 'de_DE']);
    $i->click('[name="submit"]');
    $i->waitForText('Die Einstellungen wurden gespeichert.');

    $i->wantTo('Update translations to make sure strings are downloaded');

    // translations may not be scheduled for update yet, retry multiple times in that case
    for ($attempts = 0; $attempts < 5; $attempts++) {
      try {
        $i->wait($attempts);
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
    $i->waitForText('Neue E-Mail');

    $i->wantTo('Check Emails filter strings');
    $i->waitForText('Alle');
    $i->waitForText('Entwurf');
    $i->waitForText('Geplant');
    $i->waitForText('Senden');
    $i->waitForText('Gesendet');

    $i->wantTo('Check Emails listing strings (translated with MailPoet.i18n)');
    $i->waitForText('Geklickt, Geöffnet');
    $i->waitForText('Newsletter');
    $i->waitForText('Beitrags-Benachrichtigungen');
    $i->waitForText('Wiederaufnahme-E-Mails');

    $i->wantTo('Check Automation listing strings (translated with @wordpress/i18n)');
    $i->amOnMailpoetPage('automation');
    $i->waitForText('Erstelle deine eigenen Automatisierungen');
    $i->waitForText('Neue Automatisierung');
    $i->waitForText('Wesentliche Dinge erforschen');
    $i->waitForText('Bearbeiten');
    $i->waitForText('Eingetragen');
    $i->waitForText('Aktiv');

    $i->wantTo('Check some Forms page strings');
    $i->amOnMailpoetPage('forms');
    $i->waitForText('Neues Formular');
    $i->waitForText('Unterhalb der Seiten');
    $i->waitForText('Registrierungen');
    $i->waitForText('Änderungsdatum');

    $i->wantTo('Check Subscribers filter strings and button');
    $i->amOnMailpoetPage('subscribers');
    $i->waitForText('Neu hinzufügen');
    $i->waitForText('Alle');
    $i->waitForText('Eingetragen');
    $i->waitForText('Unbestätigt');
    $i->waitForText('Ausgetragen');
    $i->waitForText('Inaktiv');
    $i->waitForText('Bounced');

    $i->wantTo('Check Subscribers listing strings');
    $i->waitForText('Jetzt neu berechnen');
    $i->waitForText('Abonnent');
    $i->waitForText('Unbestätigt');
    $i->waitForText('Schlagwörter');
    $i->waitForText('Unbekannt');

    $i->wantTo('Check some Lists strings');
    $i->amOnMailpoetPage('lists');
    $i->waitForText('Abonnenten in deinem Tarif');
    $i->waitForText('Listen-Bewertung');
    $i->waitForText('Neue Liste');
    $i->waitForText('Ausgetragen');

    $i->wantTo('Check Settings tabs strings');
    $i->amOnMailpoetPage('settings');
    $i->waitForText('Grundlagen');
    $i->waitForText('Registrierungsbestätigung');
    $i->waitForText('Senden mit ...');
    $i->waitForText('Fortgeschritten');
    $i->waitForText('Schlüssel-Aktivierung');

    $i->wantTo('Check some Settings strings');
    $i->waitForText('Standardabsender');
    $i->waitForText('Einstellungen speichern');
  }

  public function _after(AcceptanceTester $i) {
    try {
      $i->cli(['language', 'core', 'uninstall', 'de_DE']);
      $i->cli(['language', 'plugin', 'uninstall', 'mailpoet', 'de_DE']);
    } catch (Throwable $e) {
      // language already uninstalled
    }
  }
}
