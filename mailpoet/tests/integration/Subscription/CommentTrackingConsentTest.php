<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

/**
 * Tracking consent captured on the WordPress comment form (CNIL/Garante).
 *
 * The comment path can defer the subscribe until the comment is approved, so
 * the consent given at comment time has to survive that gap.
 */
class CommentTrackingConsentTest extends \MailPoetTest {
  private Comment $comment;

  private SubscribersRepository $subscribersRepository;

  private SettingsController $settings;

  private int $commentId;

  public function _before() {
    parent::_before();
    $this->comment = $this->diContainer->get(Comment::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('signup_confirmation.enabled', false);
    $this->settings->set('subscribe.on_comment.segments', [1]);

    $commentId = wp_insert_comment([
      'comment_post_ID' => 1,
      'comment_author' => 'Commenter',
      'comment_author_email' => 'commenter-consent@example.com',
      'comment_content' => 'Hello',
      'comment_approved' => 1,
    ]);
    $this->commentId = (int)$commentId;
  }

  public function _after() {
    unset($_POST['mailpoet']);
    wp_delete_comment($this->commentId, true);
    parent::_after();
  }

  private function askEveryone(): void {
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  public function testItRecordsConsentTickedOnAnApprovedComment() {
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_comment' => true, 'tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    $subscriber = $this->getSubscriber();
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT);
  }

  public function testConsentSurvivesTheModerationQueue() {
    // The subscribe is deferred until approval, so the choice made at comment
    // time has to be remembered and replayed, not silently dropped.
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_comment' => true, 'tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::PENDING_APPROVAL);

    verify($this->subscribersRepository->findOneBy(['email' => 'commenter-consent@example.com']))->null();

    // Approval happens later, in a request that carries no POST data at all.
    unset($_POST['mailpoet']);
    $this->comment->onStatusUpdate($this->commentId, 'approve');

    $subscriber = $this->getSubscriber();
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT);
  }

  public function testADeclineSurvivesTheModerationQueue() {
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_comment' => true, 'tracking_consent' => false];
    $this->comment->onSubmit($this->commentId, Comment::PENDING_APPROVAL);

    unset($_POST['mailpoet']);
    $this->comment->onStatusUpdate($this->commentId, 'approve');

    verify($this->getSubscriber()->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);
  }

  public function testCommentingDoesNotImplyConsent() {
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_comment' => true];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    verify($this->getSubscriber()->getTrackingConsent())
      ->notEquals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testItRecordsNothingWhenTheSiteTracksEveryone() {
    $_POST['mailpoet'] = ['subscribe_on_comment' => true, 'tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    verify($this->getSubscriber()->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  public function testAConsentOnlyCommenterWithAnExistingSubscriberRowIsRecorded() {
    // The gap this fixes: onSubmit() only read tracking_consent inside the
    // subscribe_on_comment branch, so somebody already on the list who answered
    // the tracking question without re-subscribing had their answer dropped.
    $this->askEveryone();
    (new SubscriberFactory())->withEmail('commenter-consent@example.com')->create();

    $_POST['mailpoet'] = ['tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    $this->entityManager->clear();
    $subscriber = $this->getSubscriber();
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT);
  }

  public function testAConsentOnlyCommenterIsRecordedWhileAwaitingModeration() {
    // Updating an existing row's consent does not depend on the comment being
    // approved, unlike subscribing, which does.
    $this->askEveryone();
    (new SubscriberFactory())->withEmail('commenter-consent@example.com')->create();

    $_POST['mailpoet'] = ['tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::PENDING_APPROVAL);

    $this->entityManager->clear();
    verify($this->getSubscriber()->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testAConsentOnlyCommenterWithNoExistingRowCreatesNothing() {
    // A comment is not a signup. Answering a tracking question must never put
    // somebody on a list they did not ask to join.
    $this->askEveryone();

    $_POST['mailpoet'] = ['tracking_consent' => true];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    $this->entityManager->clear();
    verify($this->subscribersRepository->findOneBy(['email' => 'commenter-consent@example.com']))->null();
  }

  public function testAConsentOnlyCommenterDoesNotDowngradeAnExistingGrant() {
    $this->askEveryone();
    $existing = (new SubscriberFactory())->withEmail('commenter-consent@example.com')->create();
    $existing->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'given on the manage page'
    );
    $this->subscribersRepository->flush();

    $_POST['mailpoet'] = ['tracking_consent' => false];
    $this->comment->onSubmit($this->commentId, Comment::APPROVED);

    $this->entityManager->clear();
    verify($this->getSubscriber()->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testTheFieldIsHiddenUntilTheSiteAsks() {
    $withoutAsking = $this->comment->extendLoggedInForm('');
    verify($withoutAsking)->stringNotContainsString('mailpoet[tracking_consent]');

    $this->askEveryone();
    $whenAsking = $this->comment->extendLoggedInForm('');
    verify($whenAsking)->stringContainsString('mailpoet[tracking_consent]');
    verify($whenAsking)->stringNotContainsString('checked');
  }

  private function getSubscriber(): SubscriberEntity {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'commenter-consent@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return $subscriber;
  }
}
