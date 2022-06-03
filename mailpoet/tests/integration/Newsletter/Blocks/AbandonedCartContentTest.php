<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\WP\Functions as WPFunctions;

class AbandonedCartContentTest extends \MailPoetTest {
  /** @var AbandonedCartContent */
  private $block;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var array */
  private $productIds = [];

  private $accBlock = [
    'type' => 'abandonedCartContent',
    'amount' => '2',
    'withLayout' => true,
    'contentType' => 'product',
    'postStatus' => 'publish',
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h1',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => false,
    'featuredImagePosition' => 'alternate',
    'pricePosition' => 'below',
    'readMoreType' => 'none',
    'readMoreText' => '',
    'readMoreButton' => [],
    'sortBy' => 'newest',
    'showDivider' => true,
    'divider' => [
      'type' => 'divider',
      'context' => 'abandonedCartContent.divider',
      'styles' => [
        'block' => [
            'backgroundColor' => 'transparent',
            'padding' => '13px',
            'borderStyle' => 'solid',
            'borderWidth' => '3px',
            'borderColor' => '#aaaaaa',
          ],
        ],
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(AbandonedCartContent::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);

    // Clear old products
    $products = $this->wp->getPosts(['post_type' => 'product']);
    foreach ($products as $product) {
      $this->wp->wpDeletePost((int)$product->ID);
    }

    register_post_type("product", ['public' => true]);

    $this->productIds = [];
    $this->productIds[] = $this->createPost('Product 1', '2020-05-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 2', '2020-06-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 3', '2020-07-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 4', '2020-08-01 01:01:01', 'product');
  }

  public function testItDoesNotRenderIfNewsletterTypeIsNotAutomatic() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_STANDARD);
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->block->render($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfAutomaticNewsletterIsNotForAbandonedCart() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter, WooCommerceEmail::SLUG, 'some_event');
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->block->render($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItRendersLatestProductsInPreviewMode() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->block->render($newsletter, $this->accBlock, true);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('Product 4');
    expect($encodedResult)->stringContainsString('Product 3');
    expect($encodedResult)->stringNotContainsString('Product 2');
    expect($encodedResult)->stringNotContainsString('Product 1');
  }

  public function testItDoesNotRenderIfNoSendingTaskIsSupplied() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->block->render($newsletter, $this->accBlock, false);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfCartIsEmpty() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter, 1, [AbandonedCart::TASK_META_NAME => []]);
    $result = $this->block->render($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItRendersAbandonedCartContentBlock() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->block->render($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringNotContainsString('Product 4');
    expect($encodedResult)->stringContainsString('Product 3');
    expect($encodedResult)->stringContainsString('Product 2');
    expect($encodedResult)->stringContainsString('Product 1');
  }

  private function createPost(string $title, string $publishDate, string $type = 'post') {
    return $this->wp->wpInsertPost([
      'post_title' => $title,
      'post_status' => 'publish',
      'post_date' => $publishDate,
      'post_date_gmt' => $this->wp->getGmtFromDate($publishDate),
      'post_type' => $type,
    ]);
  }

  private function createNewsletter($subject, $type, $parent = null) {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($subject);
    $newsletter->setType($type);
    $newsletter->setParent($parent);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function setGroupAndEventOptions($newsletter, $group = WooCommerceEmail::SLUG, $event = AbandonedCart::SLUG) {
    (new NewsletterOption())->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_GROUP => $group,
      NewsletterOptionFieldEntity::NAME_EVENT => $event,
    ]);
  }

  private function createSendingTask($newsletter, $subscriberId = null, $meta = null) {
    $subscriberId = $subscriberId ?: 1; // dummy default value
    $meta = $meta ?: [AbandonedCart::TASK_META_NAME => array_slice($this->productIds, 0, 3)];
    $sendingTask = $this->automaticEmailScheduler->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
    return $sendingTask;
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
  }
}
