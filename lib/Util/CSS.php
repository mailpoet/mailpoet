<?php
namespace MailPoet\Util;
use csstidy;

/*
  Copyright 2013-2014, François-Marie de Jouvencel

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* A class to inline CSS.
*
* It honours !important attributes and doesn't choke on complex styles.
*
*
*/

class CSS {
  private $parsed_css = array();

  public static function splitMediaQueries($css) {
    $start = 0;
    $queries = '';

    while(($start = strpos($css, "@media", $start)) !== false) {
      // stack to manage brackets
      $s = array();

      // get the first opening bracket
      $i = strpos($css, "{", $start);

      // if $i is false, then there is probably a css syntax error
      if($i !== false) {
        // push bracket onto stack
        array_push($s, $css[$i]);

        // move past first bracket
        $i++;

        while(!empty($s)) {
          // if the character is an opening bracket, push it onto the stack, otherwise pop the stack
          if($css[$i] == "{") {
            array_push($s, "{");
          } else if($css[$i] == "}") {
            array_pop($s);
          }

          $i++;
        }

        $queries .= substr($css, $start-1, $i+1-$start) . "\n";
        $css = substr($css, 0, $start-1) . substr($css, $i);
        $i = $start;
      }
    }

    return array($css, $queries);
  }

  public function parseCSS($text) {
    $css  = new csstidy();
    $css->settings['compress_colors'] = false;
    $css->parse($text);

    $rules    = array();
    $position   = 0;

    foreach($css->css as $declarations) {
      foreach($declarations as $selectors => $properties) {
        foreach(explode(",", $selectors) as $selector) {
          $rules[] = array(
            'position'    => $position,
            'specificity'   => self::calculateCSSSpecifity($selector),
            'selector'    => $selector,
            'properties'  => $properties
          );
        }

        $position += 1;
      }
    }

    usort($rules, function($a, $b) {
      if($a['specificity'] > $b['specificity']) {
        return 1;
      } else if($a['specificity'] < $b['specificity']) {
        return -1;
      } else {
        if($a['position'] > $b['position']) {
          return 1;
        } else {
          return -1;
        }
      }
    });

    return $rules;
  }

  /**
   * The following function fomes from CssToInlineStyles.php - here is the original licence FOR THIS FUNCTION
   *
   * CSS to Inline Styles class
   *
   * @author    Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
   * @version   1.2.1
   * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
   * @license   BSD License
   */

  public static function calculateCSSSpecifity($selector) {
      // cleanup selector
    $selector = str_replace(array('>', '+'), array(' > ', ' + '), $selector);

      // init var
    $specifity = 0;

      // split the selector into chunks based on spaces
    $chunks = explode(' ', $selector);

      // loop chunks
    foreach ($chunks as $chunk) {
          // an ID is important, so give it a high specifity
      if(strstr($chunk, '#') !== false) $specifity += 100;

          // classes are more important than a tag, but less important then an ID
      elseif(strstr($chunk, '.')) $specifity += 10;

          // anything else isn't that important
      else $specifity += 1;
    }

      // return
    return $specifity;
  }

  /*
  * Turns a CSS style string (like: "border: 1px solid black; color:red")
  * into an array of properties (like: array("border" => "1px solid black", "color" => "red"))
  */
  public static function styleToArray($str) {
    $array = array();

    if(trim($str) === '') return $array;

    foreach(explode(';', $str) as $kv) {
      if($kv === '') {
        continue;
      }

      list($selector, $rule) = explode(':', $kv, 2);
      $array[trim($selector)] = trim($rule);
    }

    return $array;
  }

  /*
  * Reverses what styleToArray does, see above.
  * array("border" => "1px solid black", "color" => "red") yields "border: 1px solid black; color:red"
  */
  public static function arrayToStyle($array) {
    $parts = array();
    foreach($array as $k => $v) {
      $parts[] = "$k:$v";
    }
    return implode(';', $parts);
  }

  /*
  * The core of the algorithm, takes a URL and returns the HTML found there with the CSS inlined.
  * If you pass $contents then the original HTML is not downloaded and $contents is used instead.
  * $url is mandatory as it is used to resolve the links to the stylesheets found in the HTML.
  */
  function inlineCSS($url, $contents=null) {
    $html = \pQuery::parseStr($contents);

    if(!is_object($html)) {
      return false;
    }

    $css_blocks = '';

    // Find all <style> blocks and cut styles from them (leaving media queries)
    foreach($html->query('style') as $style) {
      list($_css_to_parse, $_css_to_keep) = self::splitMediaQueries($style->getInnerText());
      $css_blocks .= $_css_to_parse;
      if(!empty($_css_to_keep)) {
        $style->setInnerText($_css_to_keep);
      } else {
        $style->setOuterText('');
      }
    }

    $raw_css = '';
    if(!empty($css_blocks)) {
      $raw_css .= $css_blocks;
    }

    // Get the CSS rules by decreasing order of specificity.
    // This is an array with, amongst other things, the keys 'properties', which hold the CSS properties
    // and the 'selector', which holds the CSS selector
    $rules = $this->parseCSS($raw_css);

    // We loop over each rule by increasing order of specificity, find the nodes matching the selector
    // and apply the CSS properties
    foreach ($rules as $rule) {
      foreach($html->query($rule['selector']) as $node) {
        // I'm leaving this for debug purposes, it has proved useful.
        /*
        if($node->already_styled === 'yes')
        {
          echo "<PRE>";
          echo "Rule:\n";
          print_r($rule);
          echo "\n\nOld style:\n";
          echo $node->style."\n";
          print_r(self::styleToArray($node->style));
          echo "\n\nNew style:\n";
          print_r(array_merge(self::styleToArray($node->style), $rule['properties']));
          echo "</PRE>";
          die();
        }//*/

        // Unserialize the style array, merge the rule's CSS into it...
        $nodeStyles = self::styleToArray($node->style);
        $style = array_merge($nodeStyles, $rule['properties']);

        // !important node styles should take precedence over other styles
        $style = array_merge($style, preg_grep("/important/i", $nodeStyles));

        // And put the CSS back as a string!
        $node->style = self::arrayToStyle($style);

        // I'm leaving this for debug purposes, it has proved useful.
        /*
        if($rule['selector'] === 'table.table-recap td')
        {
          $node->already_styled = 'yes';
        }//*/
      }
    }

    // Now a tricky part: do a second pass with only stuff marked !important
    // because !important properties do not care about specificity, except when fighting
    // against another !important property
    foreach ($rules as $rule) {
      foreach($rule['properties'] as $key => $value) {
        if(strpos($value, '!important') !== false) {
          foreach($html->find($rule['selector']) as $node) {
            $style = self::styleToArray($node->style);
            $style[$key] = $value;
            $node->style = self::arrayToStyle($style);
            // remove all !important tags (inlined styles take precedent over others anyway)
            $node->style = str_replace("!important", "", $node->style);
          }
        }
      }
    }

    // Let simple_html_dom give us back our HTML with inline CSS!
    return (string)$html;
  }
}
