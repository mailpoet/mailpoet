<?php

namespace MailPoet\Automation\Engine\Data;

use MailPoet\RuntimeException;

class WorkflowTemplate
{

  public const CATEGORY_WELCOME = 1;
  public const CATEGORY_ABANDONED_CART = 2;
  public const CATEGORY_REENGAGEMENT = 3;
  public const CATEGORY_WOOCOMMERCE = 4;
  public const ALL_CATEGORIES = [
    self::CATEGORY_WELCOME,
    self::CATEGORY_ABANDONED_CART,
    self::CATEGORY_REENGAGEMENT,
    self::CATEGORY_WOOCOMMERCE,
  ];

  /** @var string */
  private $slug;

  /** @var int */
  private $category;

  /** @var string */
  private $description;

  /** @var Workflow */
  private $workflow;

  public function __construct(string $slug, int $category, string $description, Workflow $workflow) {
    if (! in_array($category, self::ALL_CATEGORIES)) {
      throw new RuntimeException("$category is not a valid category.");
    }
    $this->slug = $slug;
    $this->category = $category;
    $this->description = $description;
    $this->workflow = $workflow;
  }

  public function getSlug() : string {
    return $this->slug;
  }

  public function getName() : string {
    return $this->workflow->getName();
  }

  public function getCategory() : int {
    return $this->category;
  }

  public function getDescription() : string {
    return $this->description;
  }

  public function getWorkflow() : Workflow {
    return $this->workflow;
  }

  public function toArray() : array {
    return [
      'slug' => $this->getSlug(),
      'name' => $this->getName(),
      'category' => $this->getCategory(),
      'description' => $this->getDescription(),
      'workflow' => $this->getWorkflow()->toArray(),
    ];
  }
}
