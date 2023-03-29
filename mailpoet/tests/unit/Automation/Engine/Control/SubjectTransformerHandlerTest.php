<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoetUnitTest;

class SubjectTransformerHandlerTest extends MailPoetUnitTest {
  public function testItFindsAllPossibleSubjects(): void {
    $triggerSubject = 'subject_a';
    $graphKeys = [
      $triggerSubject => ['subject_b1', 'subject_b2'],
      'subject_b1' => ['subject_c1'],
      'subject_b2' => ['subject_c2'],
      'subject_c1' => ['subject_d1'],
    ];

    $transformers = [];
    foreach ($graphKeys as $from => $tos) {
      foreach ($tos as $to) {
        $transformer = $this->createMock(SubjectTransformer::class);
        $transformer->method('returns')->willReturn($to);
        $transformer->method('accepts')->willReturn($from);
        $transformers[] = $transformer;
      }
    }

    $anotherRegisteredTransformer = $this->createMock(SubjectTransformer::class);
    $anotherRegisteredTransformer->method('returns')->willReturn('yet_another_subject');
    $anotherRegisteredTransformer->method('accepts')->willReturn('another_unrelated_subject');
    $transformers[] = $anotherRegisteredTransformer;

    $trigger = $this->createMock(Trigger::class);
    $trigger->expects($this->any())->method('getKey')->willReturn('trigger');
    $trigger->expects($this->any())->method('getSubjectKeys')->willReturn([$triggerSubject]);
    $registry = $this->createMock(Registry::class);
    $registry->expects($this->any())->method('getSubjectTransformers')->willReturn($transformers);
    $registry->expects($this->any())->method('getTrigger')->willReturnCallback(function($key) use ($trigger){
      return $key === 'trigger' ? $trigger : null;
    });
    $testee = new SubjectTransformerHandler($registry);

    $triggerData = $this->createMock(Step::class);
    $triggerData->expects($this->any())->method('getType')->willReturn(Step::TYPE_TRIGGER);
    $triggerData->expects($this->any())->method('getKey')->willReturn('trigger');
    $automation = $this->createMock(Automation::class);
    $automation->method('getSteps')->willReturn([$triggerData]);
    $result = $testee->getSubjectKeysForAutomation($automation);
    $this->assertEquals(['subject_a', 'subject_b1', 'subject_b2', 'subject_c1', 'subject_c2', 'subject_d1'], $result);
  }

  public function testItDoesNotRunInfiniteWhileFindingAllSubjects(): void {
    $triggerSubject = 'subject_a';
    $graphKeys = [
      $triggerSubject => 'subject_b',
      'subject_b' => 'subject_c',
      'subject_c' => 'subject_a',
    ];

    $transformers = [];
    foreach ($graphKeys as $from => $to) {
      $transformer = $this->createMock(SubjectTransformer::class);
      $transformer->method('returns')->willReturn($to);
      $transformer->method('accepts')->willReturn($from);
      $transformers[] = $transformer;
    }

    $trigger = $this->createMock(Trigger::class);
    $trigger->expects($this->any())->method('getKey')->willReturn('trigger');
    $trigger->expects($this->any())->method('getSubjectKeys')->willReturn([$triggerSubject]);
    $registry = $this->createMock(Registry::class);
    $registry->expects($this->any())->method('getSubjectTransformers')->willReturn($transformers);
    $registry->expects($this->any())->method('getTrigger')->willReturnCallback(function($key) use ($trigger){
      return $key === 'trigger' ? $trigger : null;
    });
    $testee = new SubjectTransformerHandler($registry);

    $triggerData = $this->createMock(Step::class);
    $triggerData->expects($this->any())->method('getType')->willReturn(Step::TYPE_TRIGGER);
    $triggerData->expects($this->any())->method('getKey')->willReturn('trigger');
    $automation = $this->createMock(Automation::class);
    $automation->method('getSteps')->willReturn([$triggerData]);
    $result = $testee->getSubjectKeysForAutomation($automation);
    $this->assertEquals(['subject_a', 'subject_b', 'subject_c'], $result);
  }

  public function testItReturnsOnlyKeysInCommonForMultipleTriggers(): void {
    $trigger1Keys = ['a', 'b', 'c'];
    $trigger1 = $this->createMock(Trigger::class);
    $trigger1->expects($this->any())->method('getKey')->willReturn('trigger1');
    $trigger1->expects($this->any())->method('getSubjectKeys')->willReturn($trigger1Keys);

    $trigger2Keys = ['b', 'c', 'd'];
    $trigger2 = $this->createMock(Trigger::class);
    $trigger2->expects($this->any())->method('getKey')->willReturn('trigger2');
    $trigger2->expects($this->any())->method('getSubjectKeys')->willReturn($trigger2Keys);

    $registry = $this->createMock(Registry::class);
    $registry->expects($this->any())->method('getSubjectTransformers')->willReturn([]);
    $registry->expects($this->any())->method('getTrigger')->willReturnCallback(function($key) use ($trigger1, $trigger2){
      if ($key === 'trigger1') {
        return $trigger1;
      }
      if ($key === 'trigger2') {
        return $trigger2;
      }
      return null;
    });
    $testee = new SubjectTransformerHandler($registry);

    $trigger1Data = $this->createMock(Step::class);
    $trigger1Data->expects($this->any())->method('getType')->willReturn(Step::TYPE_TRIGGER);
    $trigger1Data->expects($this->any())->method('getKey')->willReturn('trigger1');

    $trigger2Data = $this->createMock(Step::class);
    $trigger2Data->expects($this->any())->method('getType')->willReturn(Step::TYPE_TRIGGER);
    $trigger2Data->expects($this->any())->method('getKey')->willReturn('trigger2');

    $automation = $this->createMock(Automation::class);
    $automation->method('getSteps')->willReturn([$trigger1Data, $trigger2Data]);
    $result = $testee->getSubjectKeysForAutomation($automation);
    $this->assertEquals(['b', 'c'], $result);
  }

  public function testItProvidesAllSubjects(): void {

    $subjectTransformerStart = $this->createMock(SubjectTransformer::class);
    $subjectTransformerStart->expects($this->any())->method('accepts')->willReturn('from');
    $subjectTransformerStart->expects($this->any())->method('returns')->willReturn('middle');
    $subjectTransformerStart->expects($this->any())->method('transform')->willReturnCallback(function($subject) {
      if ($subject->getKey() === 'from') {
        return new Subject('middle', []);
      }
      return $subject;
    });

    $subjectTransformerEnd = $this->createMock(SubjectTransformer::class);
    $subjectTransformerEnd->expects($this->any())->method('accepts')->willReturn('middle');
    $subjectTransformerEnd->expects($this->any())->method('returns')->willReturn('to');
    $subjectTransformerEnd->expects($this->any())->method('transform')->willReturnCallback(function($subject) {
      if ($subject->getKey() === 'middle') {
        return new Subject('to', []);
      }
      return $subject;
    });

    $unrelatedTransformer = $this->createMock(SubjectTransformer::class);
    $unrelatedTransformer->expects($this->any())->method('accepts')->willReturn('unrelated');
    $unrelatedTransformer->expects($this->never())->method('returns')->willReturn('some-other-unrelated');
    $unrelatedTransformer->expects($this->never())->method('transform');


    $transformer = [
      $subjectTransformerEnd,
      $subjectTransformerStart,
      $unrelatedTransformer,
    ];
    $trigger = $this->createMock(Trigger::class);
    $trigger->expects($this->any())->method('getSubjectKeys')->willReturn(['from']);

    $registry = $this->createMock(Registry::class);
    $registry->expects($this->any())->method('getTrigger')->willReturnCallback(
      function($key) {
        if ($key !== 'trigger') {
          return null;
        }

        $trigger = $this->createMock(Trigger::class);
        $trigger->expects($this->any())->method('getKey')->willReturn('trigger');
        $trigger->expects($this->any())->method('getSubjectKeys')->willReturn(['from']);
        return $trigger;
      }
    );
    $registry->expects($this->any())->method('getSubjectTransformers')->willReturn($transformer);
    $testee = new SubjectTransformerHandler($registry);

    $subject = new Subject('from', ['key' => 'value']);
    $subjects = $testee->getAllSubjects([$subject]);
    $this->assertNotNull($subjects);
    $this->assertCount(3, $subjects);
    $this->assertSame(['from', 'middle', 'to'], array_map(function(Subject $subject): string { return $subject->getKey();

    }, $subjects));
  }
}
