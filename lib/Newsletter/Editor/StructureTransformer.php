<?php
namespace MailPoet\Newsletter\Editor;

use pQuery;
use MailPoet\Util\DOM as DOMUtil;

if(!defined('ABSPATH')) exit;

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
  protected function hoistImagesToRoot($root) {
    foreach($root->query('img') as $item) {
      $top_ancestor = DOMUtil::findTopAncestor($item);
      $offset = $top_ancestor->index();

      if($item->hasParent('a') || $item->hasParent('figure')) {
        $item = $item->parent;
      }

      DOMUtil::splitOn($item->getRoot(), $item);
    }
  }

  /**
   * Transforms HTML tags into their respective JSON objects,
   * turns other root children into text blocks
   */
  private function transformTagsToBlocks($root, $image_full_width) {
    return array_map(function($item) use ($image_full_width) {
      if($item->tag === 'img' || in_array($item->tag, ['a', 'figure'], true) && $item->query('img')) {
        $image = $item->tag === 'img' ? $item : $item->query('img')[0];

        // when <figure> exist, it carries align class, otherwise it's on <img>
        $alignItem = $item->tag === 'figure' ? $item : $image;
        if($alignItem->hasClass('aligncenter')) {
          $align = 'center';
        } elseif($alignItem->hasClass('alignleft')) {
          $align = 'left';
        } elseif($alignItem->hasClass('alignright')) {
          $align = 'right';
        } else {
          $align = 'left';
        }

        $width = $image->getAttribute('width');
        $height = $image->getAttribute('height');
        return array(
          'type' => 'image',
          'link' => $item->getAttribute('href') ?: '',
          'src' => $image->getAttribute('src'),
          'alt' => $image->getAttribute('alt'),
          'fullWidth' => $image_full_width,
          'width' => $width === null ? 'auto' : $width,
          'height' => $height === null ? 'auto' : $height,
          'styles' => array(
            'block' => array(
              'textAlign' => $align,
            ),
          ),
        );
      } else {
        return array(
          'type' => 'text',
          'text' => $item->toString()
        );
      }

    }, $root->children);
  }

  /**
   * Merges neighboring blocks when possible.
   * E.g. 2 adjacent text blocks may be combined into one.
   */
  private function mergeNeighboringBlocks($structure) {
    $updated_structure = array();
    $text_accumulator = '';
    foreach($structure as $item) {
      if($item['type'] === 'text') {
        $text_accumulator .= $item['text'];
      }
      if($item['type'] !== 'text') {
        if(!empty($text_accumulator)) {
          $updated_structure[] = array(
            'type' => 'text',
            'text' => trim($text_accumulator),
          );
          $text_accumulator = '';
        }
        $updated_structure[] = $item;
      }
    }

    if(!empty($text_accumulator)) {
      $updated_structure[] = array(
        'type' => 'text',
        'text' => trim($text_accumulator),
      );
    }

    return $updated_structure;
  }

}
