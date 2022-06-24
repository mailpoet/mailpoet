<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Exceptions\UnexpectedSubjectType;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Engine\Workflows\SubjectFactory;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;

class MailPoetSubjectFactory implements SubjectFactory {


  private $segmentsRepository;

  private $subscribersRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function canHandle(string $key): bool {
    return in_array($key, ['mailpoet:segment', 'mailpoet:subscriber'], true);
  }

  public function forKey(string $key): Subject {
    if ($key === 'mailpoet:segment') {
      return new SegmentSubject($this->segmentsRepository);
    }
    if ($key === 'mailpoet:subscriber') {
      return new SubscriberSubject($this->subscribersRepository);
    }
    throw new UnexpectedSubjectType("Can not handle $key");
  }
}
