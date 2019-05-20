<?php
namespace MailPoet\Newsletter\Editor;

use pQuery;
use pQuery\DomNode;
use MailPoet\Util\DOM as DOMUtil;

class StructureTransformer {

  function transform($content, $image_full_width) {
    $root = pQuery::parseStr($content);

    $this->hoistImagesToRoot($root);
    $structure = $this->transformTagsToBlocks($root, $image_full_width);
    $structure = $this->mergeNeighboringBlocks($structure);
    return $structure;
  }

  /**
   * Hoists images to root level, preserves order by splitting neighboring
   * elements and inserts tags as children of top ancestor
   */
  protected function hoistImagesToRoot(DomNode $root) {
    foreach ($root->query('img') as $item) {
      $top_ancestor = DOMUtil::findTopAncestor($item);
      $offset = $top_ancestor->index();

      if ($item->hasParent('a') || $item->hasParent('figure')) {
        $item = $item->parent;
      }

      DOMUtil::splitOn($item->getRoot(), $item);
    }
  }

  /**
   * Transforms HTML tags into their respective JSON objects,
   * turns other root children into text blocks
   */
  private function transformTagsToBlocks(DomNode $root, $image_full_width) {
    $children = $this->filterOutFiguresWithoutImages($root->children);
    return array_map(function($item) use ($image_full_width) {
      if ($this->isImageElement($item)) {
        $image = $item->tag === 'img' ? $item : $item->query('img')[0];
        $width = $image->getAttribute('width');
        $height = $image->getAttribute('height');
        return [
          'type' => 'image',
          'link' => $item->getAttribute('href') ?: '',
          'src' => $image->getAttribute('src'),
          'alt' => $image->getAttribute('alt'),
          'fullWidth' => $image_full_width,
          'width' => $width === null ? 'auto' : $width,
          'height' => $height === null ? 'auto' : $height,
          'styles' => [
            'block' => [
              'textAlign' => $this->getImageAlignment($image),
            ],
          ],
        ];
      } else {
        return [
          'type' => 'text',
          'text' => $item->toString(),
        ];
      }

    }, $children);
  }

  private function filterOutFiguresWithoutImages(array $items) {
    $items = array_filter($items, function (DomNode $item) {
      if ($item->tag === 'figure' && $item->query('img')->count() === 0) {
        return false;
      }
      return true;
    });
    return array_values($items);
  }

  private function isImageElement(DomNode $item) {
    return $item->tag === 'img' || (in_array($item->tag, ['a', 'figure'], true) && $item->query('img')->count() > 0);
  }

  private function getImageAlignment(DomNode $image) {
    $alignItem = $image->hasParent('figure') ? $image->parent : $image;
    if ($alignItem->hasClass('aligncenter')) {
      $align = 'center';
    } elseif ($alignItem->hasClass('alignleft')) {
      $align = 'left';
    } elseif ($alignItem->hasClass('alignright')) {
      $align = 'right';
    } else {
      $align = 'left';
    }
    return $align;
  }

  /**
   * Merges neighboring blocks when possible.
   * E.g. 2 adjacent text blocks may be combined into one.
   */
  private function mergeNeighboringBlocks(array $structure) {
    $updated_structure = [];
    $text_accumulator = '';
    foreach ($structure as $item) {
      if ($item['type'] === 'text') {
        $text_accumulator .= $item['text'];
      }
      if ($item['type'] !== 'text') {
        if (!empty($text_accumulator)) {
          $updated_structure[] = [
            'type' => 'text',
            'text' => trim($text_accumulator),
          ];
          $text_accumulator = '';
        }
        $updated_structure[] = $item;
      }
    }

    if (!empty($text_accumulator)) {
      $updated_structure[] = [
        'type' => 'text',
        'text' => trim($text_accumulator),
      ];
    }

    return $updated_structure;
  }

}
