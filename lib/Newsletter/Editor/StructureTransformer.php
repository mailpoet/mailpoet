<?php
namespace MailPoet\Newsletter\Editor;

use pQuery;

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
   * Hoists images to root level, preserves order
   * and inserts tags before top ancestor
   */
  private function hoistImagesToRoot($root) {
    foreach($root->query('img') as $item) {
      $top_ancestor = $this->findTopAncestor($item);
      $offset = $top_ancestor->index();

      if($item->hasParent('a')) {
        $item = $item->parent;
      }

      $item->changeParent($root, $offset);
    }
  }

  private static function findTopAncestor($item) {
    while($item->parent->parent !== null) {
      $item = $item->parent;
    }
    return $item;
  }

  /**
   * Transforms HTML tags into their respective JSON objects,
   * turns other root children into text blocks
   */
  private function transformTagsToBlocks($root, $image_full_width) {
    return array_map(function($item) use ($image_full_width) {
      if($item->tag === 'img' || $item->tag === 'a' && $item->query('img')) {
        if($item->tag === 'a') {
          $link = $item->getAttribute('href');
          $image = $item->children[0];
        } else {
          $link = '';
          $image = $item;
        }

        return array(
          'type' => 'image',
          'link' => $link,
          'src' => $image->getAttribute('src'),
          'alt' => $image->getAttribute('alt'),
          'fullWidth' => $image_full_width,
          'width' => $image->getAttribute('width'),
          'height' => $image->getAttribute('height'),
          'styles' => array(
            'block' => array(
              'textAlign' => 'center',
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
