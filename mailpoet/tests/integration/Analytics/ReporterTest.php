<?php declare(strict_types = 1);

namespace MailPoet\Analytics;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Segment;
use MailPoetVendor\Carbon\Carbon;

class ReporterTest extends \MailPoetTest {
  private Reporter $reporter;

  public function _before() {
    parent::_before();
    $this->reporter = $this->diContainer->get(Reporter::class);
  }

  public function testItWorksWithStandardNewslettersAndStandardSegments(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
  }

  public function testItWorksWithStandardNewslettersAndDynamicSegments(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subMonths(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
    $this->assertEquals(1, $processed['Number of standard newsletters sent to segment in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent to segment in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent to segment in last 3 months']);
  }

  public function testItWorksWithStandardNewslettersAndFilterSegments(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
    $this->assertEquals(1, $processed['Number of standard newsletters filtered by segment in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters filtered by segment in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters filtered by segment in last 3 months']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 7 days']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 30 days']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewsletters(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewslettersSentToSegments(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subMonths(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewslettersFilteredBySegment(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['not' => 'relevant']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['not' => 'relevant']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['not' => 'relevant']]]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of post notification campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns filtered by segment in the last 3 months']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 7 days']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 30 days']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmails(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmailsSentToSegment(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subMonths(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmailsFilteredBySegment(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subMonths(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of re-engagement campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns filtered by segment in the last 3 months']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 7 days']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 30 days']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithLegacyWelcomeEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getData();
    $this->assertSame(1, $processed['Number of legacy welcome email campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy welcome email campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy welcome email campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyAbandonedCartEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'cart_product_ids' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'cart_product_ids' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'cart_product_ids' => ['1235']]]]);
    $processed = $this->reporter->getData();
    $this->assertSame(1, $processed['Number of legacy abandoned cart campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy abandoned cart campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy abandoned cart campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyPurchasedProductEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProducts' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProducts' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProducts' => ['1235']]]]);
    $processed = $this->reporter->getData();
    $this->assertSame(1, $processed['Number of legacy purchased product campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy purchased product campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy purchased product campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyPurchasedInCategoryEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProductCategories' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProductCategories' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProductCategories' => ['1235']]]]);
    $processed = $this->reporter->getData();
    $this->assertSame(1, $processed['Number of legacy purchased in category campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy purchased in category campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy purchased in category campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyFirstPurchaseEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '3']]]);
    $processed = $this->reporter->getData();
    $this->assertSame(1, $processed['Number of legacy first purchase campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy first purchase campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy first purchase campaigns sent in the last 3 months']);
  }

  public function testItWorksForAutomationEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProductCategories' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProductCategories' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subMonths(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProductCategories' => ['1235']]]]);

    $processed = $this->reporter->getData();

    $this->assertSame(1, $processed['Number of automations campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of automations campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of automations campaigns sent in the last 3 months']);
  }

  public function testItReportsSentCampaignTotals(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);

    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '4']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '5']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subMonths(2), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '6']]]);

    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '7', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '8', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '9', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);

    $processed = $this->reporter->getData();
    $this->assertEquals(3, $processed['Number of campaigns sent in the last 7 days']);
    $this->assertEquals(6, $processed['Number of campaigns sent in the last 30 days']);
    $this->assertEquals(9, $processed['Number of campaigns sent in the last 3 months']);

    $this->assertEquals(1, $processed['Number of campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of campaigns sent to segment in the last 3 months']);

    $this->assertEquals(1, $processed['Number of campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of campaigns filtered by segment in the last 3 months']);
  }

  public function testItDoesNotDoubleCountDuplicateCampaignIds(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subMonths(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 3 months']);
  }

  public function testItReportsFormsAnalyticsData(): void {
    // Create a basic form
    (new Form())->withName('Basic Form')->create();

    // Create a form with first name field
    (new Form())->withName('Form with First Name')
      ->withFirstName()
      ->create();

    // Create a form with last name field
    (new Form())->withName('Form with Last Name')
      ->withLastName()
      ->create();

    // Create a form with custom field
    $customField = (new CustomField())
      ->withType(CustomFieldEntity::TYPE_TEXT)
      ->withParams(['required' => '1'])
      ->create();

    (new Form())->withName('Form with Custom Field')
      ->withCustomField($customField)
      ->create();

    // Create a form with multiple custom fields
    $customField2 = (new CustomField())
      ->withType(CustomFieldEntity::TYPE_CHECKBOX)
      ->withParams(['required' => '0', 'values' => [['value' => 'Option 1', 'is_checked' => '']]])
      ->create();

    (new Form())->withName('Form with Multiple Custom Fields')
      ->withCustomField($customField)
      ->withCustomField($customField2)
      ->create();

    $processed = $this->reporter->getData();

    // Test basic form counts
    $this->assertEquals(5, $processed['Forms > Number of active forms']);
    $this->assertEquals(1, $processed['Forms > Number of active forms with first name']);
    $this->assertEquals(1, $processed['Forms > Number of active forms with last name']);
    $this->assertEquals(2, $processed['Forms > Number of active forms with custom fields']);
    $this->assertEquals(1, $processed['Forms > Min custom fields']);
    $this->assertEquals(2, $processed['Forms > Max custom fields']);
    $this->assertEquals(1.5, $processed['Forms > Average custom fields']);
  }

  public function testItReportsFormsDisplayTypeCounts(): void {
    // Create forms with different display types
    $form1 = (new Form())->withName('Below Posts Form')->create();
    $form1->setSettings([
      'form_placement' => [
        FormEntity::DISPLAY_TYPE_BELOW_POST => ['enabled' => '1'],
      ],
    ]);
    $this->entityManager->flush();

    $form2 = (new Form())->withName('Fixed Bar Form')->create();
    $form2->setSettings([
      'form_placement' => [
        FormEntity::DISPLAY_TYPE_FIXED_BAR => ['enabled' => '1'],
      ],
    ]);
    $this->entityManager->flush();

    $form3 = (new Form())->withName('Popup Form')->create();
    $form3->setSettings([
      'form_placement' => [
        FormEntity::DISPLAY_TYPE_POPUP => ['enabled' => '1'],
      ],
    ]);
    $this->entityManager->flush();

    $form4 = (new Form())->withName('Slide In Form')->create();
    $form4->setSettings([
      'form_placement' => [
        FormEntity::DISPLAY_TYPE_SLIDE_IN => ['enabled' => '1'],
      ],
    ]);
    $this->entityManager->flush();

    // No placement settings, should be counted as Others (widget) form
    $form5 = (new Form())->withName('Others Form')->create();
    $form5->setSettings([
      'form_placement' => [],
    ]);
    $this->entityManager->flush();

    $processed = $this->reporter->getData();

    $this->assertEquals(5, $processed['Forms > Number of active forms']);
    $this->assertEquals(1, $processed['Forms > Number of active Below pages forms']);
    $this->assertEquals(1, $processed['Forms > Number of active Fixed bar forms']);
    $this->assertEquals(1, $processed['Forms > Number of active Pop-up forms']);
    $this->assertEquals(1, $processed['Forms > Number of active Slide–in forms']);
    $this->assertEquals(1, $processed['Forms > Number of active Others (widget) forms']);
  }

  public function testItReportsFormsWithComplexCustomFields(): void {
    // Create a form with many custom fields
    $customFields = [];
    for ($i = 1; $i <= 5; $i++) {
      $customFields[] = (new CustomField())
        ->withType(CustomFieldEntity::TYPE_TEXT)
        ->withParams(['required' => '0'])
        ->create();
    }

    (new Form())
      ->withName('Form with Many Custom Fields')
      ->withCustomField($customFields[0])
      ->withCustomField($customFields[1])
      ->withCustomField($customFields[2])
      ->withCustomField($customFields[3])
      ->withCustomField($customFields[4])
      ->create();

    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Forms > Number of active forms with custom fields']);
    $this->assertEquals(5, $processed['Forms > Min custom fields']);
    $this->assertEquals(5, $processed['Forms > Max custom fields']);
    $this->assertEquals(5.0, $processed['Forms > Average custom fields']);
  }

  public function testItReportsFormsWithNoCustomFields(): void {
    // Create forms without custom fields
    (new Form())->withName('Basic Form')->create();
    (new Form())->withName('Form with First Name')->withFirstName()->create();
    (new Form())->withName('Form with Last Name')->withLastName()->create();

    $processed = $this->reporter->getData();

    $this->assertEquals(3, $processed['Forms > Number of active forms']);
    $this->assertEquals(0, $processed['Forms > Number of active forms with custom fields']);
    $this->assertEquals(0, $processed['Forms > Min custom fields']);
    $this->assertEquals(0, $processed['Forms > Max custom fields']);
    $this->assertEquals(0, $processed['Forms > Average custom fields']);
  }

  public function testItReportsFormsWithMixedFieldTypes(): void {
    $customFieldFactory = new CustomField();

    // Create a form with first name, last name, and custom fields
    $customField = $customFieldFactory
      ->withType(CustomFieldEntity::TYPE_SELECT)
      ->withParams(['required' => '1', 'values' => [['value' => 'Option 1'], ['value' => 'Option 2']]])
      ->create();

    $form = (new Form())->withName('Complete Form')
      ->withFirstName()
      ->withLastName()
      ->withCustomField($customField)
      ->create();

    $processed = $this->reporter->getData();

    $this->assertEquals(1, $processed['Forms > Number of active forms']);
    $this->assertEquals(1, $processed['Forms > Number of active forms with first name']);
    $this->assertEquals(1, $processed['Forms > Number of active forms with last name']);
    $this->assertEquals(1, $processed['Forms > Number of active forms with custom fields']);
    $this->assertEquals(1, $processed['Forms > Min custom fields']);
    $this->assertEquals(1, $processed['Forms > Max custom fields']);
    $this->assertEquals(1.0, $processed['Forms > Average custom fields']);
  }

  public function testItDoesNotReportFormsWithDisabledStatus(): void {
    // Create an enabled form
    (new Form())->withName('Active Form')->create();

    // Create a disabled form
    $disabledForm = (new Form())->withName('Disabled Form')->create();
    $disabledForm->setStatus(FormEntity::STATUS_DISABLED);
    $this->entityManager->flush();

    $processed = $this->reporter->getData();

    // Only enabled forms should be counted
    $this->assertEquals(1, $processed['Forms > Number of active forms']);
  }

  public function testItDoesNotReportFormsWithDeletedStatus(): void {
    // Create an active form
    (new Form())->withName('Active Form')->create();

    // Create a deleted form
    $deletedForm = (new Form())->withName('Deleted Form')->create();
    $deletedForm->setDeletedAt(Carbon::now());
    $this->entityManager->flush();

    $processed = $this->reporter->getData();

    // Only non-deleted forms should be counted
    $this->assertEquals(1, $processed['Forms > Number of active forms']);
  }

  private function createSentNewsletter(string $type, Carbon $sentAt, array $segments, array $otherOptions = []): void {
    $sendingQueueOptions = ['processed_at' => $sentAt];

    $extraSendingQueueOptions = $otherOptions['sendingQueueOptions'] ?? null;

    if (is_array($extraSendingQueueOptions)) {
      $sendingQueueOptions = array_merge($sendingQueueOptions, $extraSendingQueueOptions);
    }

    (new NewsletterFactory())
      ->withType($type)
      ->withSegments($segments)
      ->withSendingQueue($sendingQueueOptions)
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->create();
  }
}
