<?php
namespace MailPoet\Newsletter;

use \pQuery;

if(!defined('ABSPATH')) exit;

class PostsTransformer {

  const MAX_EXCERPT_LENGTH = 60;

  /**
   * Transforms a list of posts into editor format
   */
  static function transform($posts, $args) {
    $results = array();

    $total_posts = count($posts);
    $use_divider = (isset($args['showDivider'])) ? (bool)$args['showDivider'] : false;
    $title_list_only = $args['displayType'] === 'titleOnly' && $args['titleFormat'] === 'ul';

    foreach ($posts as $index => $post) {
      if ($title_list_only) {
        $results[] = self::getPostTitle($post, $args);
      } else {
        $postJSON = self::postToEditorJson($post, $args);
        $results = array_merge($results, $postJSON);

        if ($use_divider && $index + 1 < $total_posts) {
          $results[] = $args['divider'];
        }
      }
    }

    if ($title_list_only && !empty($results)) {
      $results = array(
        array(
          'type' => 'text',
          'text' => '<ul>' . implode('', $results) . '</ul>',
        ),
      );
    }

    return $results;
  }

  private static function postToEditorJson($post, $args) {
    if ($args['displayType'] === 'titleOnly') {
      $content = '';
    } elseif ($args['displayType'] === 'excerpt') {
      // get excerpt
      if(!empty($post->post_excerpt)) {
        $content = $post->post_excerpt;
      } else {
        // if excerpt is empty then try to find the "more" tag
        $excerpts = explode('<!--more-->', $post->post_content);
        if (count($excerpts) > 1) {
          // <!--more--> separator was present
          $content = $excerpts[0];
        } else {
          // Separator not present, try to shorten long posts
          $content = self::postContentToExcerpt(
            $post->post_content,
            self::MAX_EXCERPT_LENGTH
          );
        }
      }
    } else {
      $content = $post->post_content;
    }

    if (strlen($post->post_content) < strlen($content)) {
      $hideReadMore = true;
    } else {
      $hideReadMore = false;
    }

    $content = self::stripShortCodes($content);

    // remove wysija nl shortcode
    $content = preg_replace(
      '/\<div class="wysija-register">(.*?)\<\/div>/',
      '',
      $content
    );

    $content = self::convertEmbeddedContent($content);

    // convert h4 h5 h6 to h3
    $content = preg_replace('/<([\/])?h[456](.*?)>/', '<$1h3$2>', $content);

    if ($args['titlePosition'] === 'aboveBlock') {
      $content = self::getPostTitle($post, $args) . $content;
    }

    // convert currency signs
    $content = str_replace(
      array('$', '€', '£', '¥'),
      array('&#36;', '&euro;', '&pound;', '&#165;'),
      $content
    );

    // strip useless tags
    $tags_not_being_stripped = array(
      '<img>', '<p>', '<em>', '<span>', '<b>', '<strong>', '<i>', '<h1>',
      '<h2>', '<h3>', '<a>', '<ul>', '<ol>', '<li>', '<br>'
    );
    $content = strip_tags($content, implode('',$tags_not_being_stripped));

    $content = wpautop($content);

    if (!$hideReadMore && $args['readMoreType'] === 'link') {
      $content .= '<p><a href="' . get_permalink($post->ID)
        . '" target="_blank">' . stripslashes($args['readMoreText'])
        . '</a></p>';
    }

    // Append author and categories above and below contents
    foreach (array('above', 'below') as $position) {
      $position_field = $position . 'Text';
      if ($args['showCategories'] === $position_field || $args['showAuthor'] === $position_field) {
        $text = '';

        if ($args['showAuthor'] === $position_field) {
          $text .= self::getPostAuthor(
            $args['authorPrecededBy'],
            $post->post_author
          );
        }

        if ($args['showCategories'] === $position_field) {
          if (!empty($text)) $text .= '<br />';
          $text .= self::getPostCategories(
            $args['categoriesPrecededBy'],
            $post
          );
        }

        if (!empty($text)) $text = '<p>' . $text . '</p>';
        if ($position === 'above') $content = $text . $content;
        else if ($position === 'below') $content .= $text;
      }
    }

    $root = pQuery::parseStr($content);

    self::hoistImagesToRoot($root);
    $structure = self::transformTagsToJson($args, $post, $root);
    $updated_structure = self::mergeNeighboringBlocks($structure);

    if ($args['titlePosition'] === 'inTextBlock') {
      // Attach title to the first text block
      $text_block_index = null;
      foreach ($updated_structure as $index => $block) {
        if ($block['type'] === 'text') {
          $text_block_index = $index;
          break;
        }
      }

      $title = self::getPostTitle($post, $args);
      if ($text_block_index === null) {
        $updated_structure[] = array(
          'type' => 'text',
          'text' => $title,
        );
      } else {
        $updated_structure[$text_block_index]['text'] = $title . $updated_structure[$text_block_index]['text'];
      }
    }

    if (!$hideReadMore && $args['readMoreType'] === 'button') {
      $button = $args['readMoreButton'];
      $button['url'] = get_permalink($post->ID);
      $updated_structure[] = $button;
    }

    return $updated_structure;
  }

  /**
   * Hoists images to root level, preserves order
   * and inserts tags before top ancestor
   */
  private static function hoistImagesToRoot($root) {
    foreach ($root->query('img') as $item) {
      $top_ancestor = self::findTopAncestor($item);
      $offset = $top_ancestor->index();

      if ($item->hasParent('a')) {
        $item = $item->parent;
      }

      $item->changeParent($root, $offset);
    }
  }

  /**
   * Transforms HTML tags into their respective JSON objects,
   * turns other root children into text blocks
   */
  private static function transformTagsToJson($args, $post, $root) {
    $structure = array();

    // Prepend featured image if current post has one
    if(in_array($args['displayType'], array('full', 'excerpt')) && has_post_thumbnail($post->ID)) {
      $thumbnail_id = get_post_thumbnail_id($post->ID);

      // get attachment data (src, width, height)
      $image_info = wp_get_attachment_image_src(
        $thumbnail_id,
        'single-post-thumbnail'
      );

      // get alt text
      $alt_text = trim(strip_tags(get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)));
      if(strlen($alt_text) === 0) {
        // if the alt text is empty then use the post title
        $alt_text = trim(strip_tags($post->post_title));
      }

      $structure[] = array(
        'type' => 'image',
        'link' => '',
        'src' => $image_info[0],
        'alt' => $alt_text,
        'padded' => (bool)$args['imagePadded'],
        'width' => $image_info[1],
        'height' => $image_info[2],
        'styles' => array(
          'block' => array(
            'textAlign' => 'center',
          ),
        ),
      );
    }

    foreach ($root->children as $item) {
      if ($item->tag === 'img' || $item->tag === 'a' && $item->query('img')) {
        $link = '';
        $image = $item;
        if ($item->tag === 'a') {
          $link = $item->getAttribute('href');
          $image = $item->children[0];
        }

        $structure[] = array(
          'type' => 'image',
          'link' => $link,
          'src' => $image->getAttribute('src'),
          'alt' => $image->getAttribute('alt'),
          'padded' => (bool)$args['imagePadded'],
          'width' => $image->getAttribute('width'),
          'height' => $image->getAttribute('height'),
          'styles' => array(
            'block' => array(
              'textAlign' => 'center',
            ),
          ),
        );
      } else {
        $structure[] = array(
          'type' => 'text',
          'text' => $item->toString(),
        );
      }
    }

    return $structure;
  }

  /**
   * Merges neighboring blocks when possible.
   * E.g. 2 adjacent text blocks may be combined into one.
   */
  private static function mergeNeighboringBlocks($structure) {
    $updated_structure = array();
    $text_accumulator = '';
    foreach ($structure as $item) {
      if ($item['type'] === 'text') {
        $text_accumulator .= $item['text'];
      }
      if ($item['type'] !== 'text') {
        if (!empty($text_accumulator)) {
          $updated_structure[] = array(
            'type' => 'text',
            'text' => trim($text_accumulator),
          );
          $text_accumulator = '';
        }
        $updated_structure[] = $item;
      }
    }

    if (!empty($text_accumulator)) {
      $updated_structure[] = array(
        'type' => 'text',
        'text' => trim($text_accumulator),
      );
    }

    return $updated_structure;
  }

  private static function findTopAncestor($item) {
    while ($item->parent->parent !== null) {
      $item = $item->parent;
    }
    return $item;
  }

  private static function stripShortCodes($content) {
    if(strlen(trim($content)) === 0) {
      return '';
    }
    // remove captions
    $content = preg_replace(
      "/\[caption.*?\](.*<\/a>)(.*?)\[\/caption\]/",
      '$1',
      $content
    );

    // remove other shortcodes
    $content = preg_replace('/\[[^\[\]]*\]/', '', $content);

    return $content;
  }

  private static function convertEmbeddedContent($content = '') {
    // remove embedded video and replace with links
    $content = preg_replace(
      '#<iframe.*?src=\"(.+?)\".*><\/iframe>#',
      '<a href="$1">'.__('Click here to view media.').'</a>',
      $content
    );

    // replace youtube links
    $content = preg_replace(
      '#http://www.youtube.com/embed/([a-zA-Z0-9_-]*)#Ui',
      'http://www.youtube.com/watch?v=$1',
      $content
    );

    return $content;
  }

  private static function getPostAuthor($preceded_by, $author_id) {

    if(!empty($author_id)) {
      $author_name = get_the_author_meta('display_name', (int)$author_id);

      // check if the user specified a label to be displayed before the author's name
      if(strlen(trim($preceded_by)) > 0) {
        $author_name = stripslashes(trim($preceded_by)).' '.$author_name;
      }
      return $author_name;
    }

    return '';
  }

  private static function getPostCategories($preceded_by, $post) {
    $content = '';

    // Get categories
    $categories = wp_get_post_terms(
      $post->ID,
      get_object_taxonomies($post->post_type),
      array('fields' => 'names')
    );
    if(!empty($categories)) {
      // check if the user specified a label to be displayed before the author's name
      if(strlen(trim($preceded_by)) > 0) {
        $content = stripslashes(trim($preceded_by)).' ';
      }

      $content .= join(', ', $categories);
    }

    return $content;
  }

  private static function getPostTitle($post, $args) {
    $title = $post->post_title;

    if ((bool)$args['titleIsLink']) {
      $title = '<a href="' . get_permalink($post->ID) . '">' . $title . '</a>';
    }

    if (in_array($args['titleFormat'], array('h1', 'h2', 'h3'))) {
      $tag = $args['titleFormat'];
    } elseif ($args['titleFormat'] === 'ul') {
      $tag = 'li';
    } else {
      $tag = 'h1';
    }

    $alignment = (in_array($args['titleAlignment'], array('left', 'right', 'center'))) ? $args['titleAlignment'] : 'left';

    return '<' . $tag . ' style="text-align: ' . $alignment . '">' . $title . '</' . $tag . '>';
  }

  /**
   * make an excerpt with a certain number of words
   * @param type $text
   * @param type $num_words
   * @param type $more
   * @return type
   */
  private static function postContentToExcerpt($text, $num_words = 8, $more = ' &hellip;'){
    return wp_trim_words($text, $num_words, $more);
  }
}
