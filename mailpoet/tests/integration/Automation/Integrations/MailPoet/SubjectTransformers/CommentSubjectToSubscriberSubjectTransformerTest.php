<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\SubjectTransformers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\SubjectTransformers\CommentSubjectToSubscriberSubjectTransformer;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;
use MailPoet\Test\DataFactories\Subscriber;

class CommentSubjectToSubscriberSubjectTransformerTest extends \MailPoetTest {
  public function testTransformReturnsSubscriberSubjectForExistingCommentAuthorEmail(): void {
    $email = 'comment-author-' . uniqid() . '@example.com';
    $subscriber = (new Subscriber())->withEmail($email)->create();
    $commentId = wp_insert_comment([
      'comment_author_email' => $email,
    ]);
    $this->assertNotFalse($commentId);

    $subject = $this->transform(new Subject(CommentSubject::KEY, ['comment_id' => $commentId]));

    $this->assertInstanceOf(Subject::class, $subject);
    $this->assertSame(SubscriberSubject::KEY, $subject->getKey());
    $this->assertSame(['subscriber_id' => $subscriber->getId()], $subject->getArgs());
  }

  public function testTransformReturnsNullWhenCommentDoesNotExist(): void {
    $this->assertNull($this->transform(new Subject(CommentSubject::KEY, ['comment_id' => 999999])));
  }

  public function testTransformReturnsNullWhenCommentEmailIsInvalid(): void {
    $commentId = wp_insert_comment([
      'comment_author_email' => 'not-an-email',
    ]);
    $this->assertNotFalse($commentId);

    $this->assertNull($this->transform(new Subject(CommentSubject::KEY, ['comment_id' => $commentId])));
  }

  public function testTransformReturnsNullWhenSubscriberDoesNotExist(): void {
    $commentId = wp_insert_comment([
      'comment_author_email' => 'missing-subscriber-' . uniqid() . '@example.com',
    ]);
    $this->assertNotFalse($commentId);

    $this->assertNull($this->transform(new Subject(CommentSubject::KEY, ['comment_id' => $commentId])));
  }

  public function testTransformRejectsDifferentSubject(): void {
    $this->expectException(\InvalidArgumentException::class);

    $this->transform(new Subject(SubscriberSubject::KEY, ['comment_id' => 123]));
  }

  private function transform(Subject $subject): ?Subject {
    $transformer = $this->diContainer->get(CommentSubjectToSubscriberSubjectTransformer::class);
    return $transformer->transform($subject);
  }
}
