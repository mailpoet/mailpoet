<?php

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;

class WorkflowRunStorageTest extends \MailPoetTest
{

  /** @var WorkflowRunStorage */
  private $testee;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    $this->testee = $this->diContainer->get(WorkflowRunStorage::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItGeneratesSubjectsWhenCreatingTheWorkflowRun() {
    $segment = new SegmentEntity('testItGeneratesSubjectsWhenCreatingTheWorkflowRun', SegmentEntity::TYPE_DEFAULT, '');
    $this->segmentRepository->persist($segment);
    $this->segmentRepository->flush();
    $segment = $this->segmentRepository->findOneBy(['name' => 'testItGeneratesSubjectsWhenCreatingTheWorkflowRun']);
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    /** @var SegmentEntity $segment */
    $segmentSubject->load(['segment_id' => $segment->getId()]);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'test@example.com']);
    $subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    /** @var SubscriberEntity $subscriber */
    $subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);

    $workflowRun = new WorkflowRun(1, 'triggerKey', [$subscriberSubject, $segmentSubject]);
    $workflowId = $this->testee->createWorkflowRun($workflowRun);
    $workflowRunFromStorage = $this->testee->getWorkflowRun($workflowId);
    $this->assertInstanceOf(WorkflowRun::class, $workflowRunFromStorage);
    /** @var WorkflowRun $workflowRunFromStorage */
    $subjects = $workflowRunFromStorage->getSubjects();

    $this->assertCount(2, $subjects);
    $this->assertInstanceOf(SubscriberSubject::class, $subjects[0]);
    $this->assertEquals($subscriber->getId(), $subjects[0]->pack()['subscriber_id']);
    $this->assertInstanceOf(SegmentSubject::class, $subjects[1]);
    $this->assertEquals($segment->getId(), $subjects[1]->pack()['segment_id']);
  }
}
