<?php
$pomo = dirname( __FILE__ ) . '/pomo';
require_once( "$pomo/entry.php" );
require_once( "$pomo/translations.php" );

/**
 * Responsible for extracting translatable strings from PHP source files
 * in the form of Translations instances
 */
class StringExtractor {

  public $rules = [
        '__' => [ 'string' ],
        '_e' => [ 'string' ],
        '_n' => [ 'singular', 'plural' ],
    ];

  public $comment_prefix = 'translators:';

  public function __construct( $rules = [] ) {
      $this->rules = $rules;
  }

  public function extract_from_directory( $dir, $excludes = [], $includes = [], $prefix = '' ) {
      $old_cwd = getcwd();
      chdir( $dir );
      $translations = new Translations;
      $file_names = (array)scandir( '.' );
    foreach ($file_names as $file_name) {
      if ( '.' == $file_name || '..' == $file_name ) {
        continue;
      }
      if ( preg_match( '/\.php$/', $file_name ) && $this->does_file_name_match( $prefix . $file_name, $excludes, $includes ) ) {
          $extracted = $this->extract_from_file( $file_name, $prefix );
          $translations->merge_originals_with( $extracted );
      } else if(preg_match('/\.html|hbs$/', $file_name)) {
          $extracted = $this->extract_from_file( $file_name, $prefix );
          $translations->merge_originals_with($extracted);
      }
      if ( is_dir( $file_name ) && ! $this->is_directory_excluded( $prefix . $file_name, $excludes ) ) {
          $extracted = $this->extract_from_directory( $file_name, $excludes, $includes, $prefix . $file_name . '/' );
          $translations->merge_originals_with( $extracted );
      }
    }
      chdir( $old_cwd );
      return $translations;
  }

  public function extract_from_file( $file_name, $prefix ) {
      $code = file_get_contents( $file_name );
      return $this->extract_from_code( $code, $prefix . $file_name );
  }

  public function does_file_name_match( $path, $excludes, $includes ) {
    if ( $includes ) {
        $matched_any_include = false;
      foreach($includes as $include) {
        if ( preg_match( '|^' . $include . '$|', $path ) ) {
            $matched_any_include = true;
            break;
        }
      }
      if ( ! $matched_any_include ) {
          return false;
      }
    }
    if ( $excludes ) {
      foreach($excludes as $exclude) {
        if ( preg_match( '|^' . $exclude . '$|', $path ) ) {
          return false;
        }
      }
    }
      return true;
  }

  public function is_directory_excluded( $directory, $excludes ) {
    if ( $excludes ) {
      foreach($excludes as $exclude) {
        if (
            preg_match( '|^' . $exclude . '$|', $directory ) ||
            preg_match( '|^' . $exclude . '$|', $directory . '/' )
        ) {
            return true;
        }
      }
    }
      return false;
  }

  public function entry_from_call( $call, $file_name ) {
      $rule = isset( $this->rules[$call['name']] ) ? $this->rules[$call['name']] : null;
    if ( ! $rule ) {
        return null;
    }
      $entry = new Translation_Entry;
      $multiple = [];
      $complete = false;
    for($i = 0; $i < count( $rule ); ++$i) {
      if ( $rule[$i] && ( ! isset( $call['args'][$i] ) || ! is_string( $call['args'][$i] ) || '' == $call['args'][$i] ) ) {
          return false;
      }
      switch( $rule[$i] ) {
        case 'string':
          if ( $complete ) {
            $multiple[] = $entry;
            $entry = new Translation_Entry;
            $complete = false;
          }
            $entry->singular = $call['args'][$i];
            $complete = true;
          break;
        case 'singular':
          if ( $complete ) {
              $multiple[] = $entry;
              $entry = new Translation_Entry;
              $complete = false;
          }
            $entry->singular = $call['args'][$i];
            $entry->is_plural = true;
          break;
        case 'plural':
            $entry->plural = $call['args'][$i];
            $entry->is_plural = true;
            $complete = true;
          break;
        case 'context':
            $entry->context = $call['args'][$i];
          foreach($multiple as &$single_entry) {
              $single_entry->context = $entry->context;
          }
          break;
      }
    }
    if ( isset( $call['line'] ) && $call['line'] ) {
        $references = [ $file_name . ':' . $call['line'] ];
        $entry->references = $references;
      foreach($multiple as &$single_entry) {
          $single_entry->references = $references;
      }
    }
    if ( isset( $call['comment'] ) && $call['comment'] ) {
        $comments = rtrim( $call['comment'] ) . "\n";
        $entry->extracted_comments = $comments;
      foreach($multiple as &$single_entry) {
          $single_entry->extracted_comments = $comments;
      }
    }
    if ( $multiple && $entry ) {
        $multiple[] = $entry;
        return $multiple;
    }

      return $entry;
  }

  public function extract_from_code( $code, $file_name) {
      $translations = new Translations;
      $extension = pathinfo($file_name, PATHINFO_EXTENSION);
      $function_calls = $this->find_function_calls(array_keys( $this->rules ), $code, $extension);
    foreach($function_calls as $call) {
        $entry = $this->entry_from_call( $call, $file_name );
      if ( is_array( $entry ) ) {
        foreach($entry as $single_entry) {
            $translations->add_entry_or_merge( $single_entry );
        }
      } elseif ( $entry ) {
            $translations->add_entry_or_merge( $entry );
      }
    }
      return $translations;
  }

    /**
     * Finds all function calls in $code and returns an array with an associative array for each function:
     *    - name - name of the function
     *    - args - array for the function arguments. Each string literal is represented by itself, other arguments are represented by null.
     *  - line - line number
     */
  public function find_function_calls( $function_names, $code, $extension = '.php') {
      $function_calls = [];

    if($extension === 'php') {
        $tokens = token_get_all( $code );
        $latest_comment = false;
        $in_func = false;
      foreach($tokens as $token) {
        $id = $text = null;
        if ( is_array( $token ) ) {
            list( $id, $text, $line ) = $token;
        }
        if ( T_WHITESPACE == $id ) {
            continue;
        }
        if ( T_STRING == $id && in_array( $text, $function_names ) && ! $in_func ) {
            $in_func = true;
            $paren_level = -1;
            $args = [];
            $func_name = $text;
            $func_line = $line;
            $func_comment = $latest_comment ? $latest_comment : '';

            $just_got_into_func = true;
            $latest_comment = false;
            continue;
        }
        if ( T_COMMENT == $id ) {
            $text = preg_replace( '%^\s+\*\s%m', '', $text );
            $text = str_replace( [ "\r\n", "\n" ], ' ', $text );
            $text = trim( preg_replace( '%^/\*|//%', '', preg_replace( '%\*/$%', '', $text ) ) );
          if ( 0 === stripos( $text, $this->comment_prefix ) ) {
                $latest_comment = $text;
          }
        }
        if ( ! $in_func ) {
            continue;
        }
        if ( '(' == $token ) {
            $paren_level++;
          if ( 0 == $paren_level ) { // Start of first argument.
                $just_got_into_func = false;
                $current_argument = null;
                $current_argument_is_just_literal = true;
          }
            continue;
        }
        if ( $just_got_into_func ) {
            // There wasn't an opening paren just after the function name -- this means it is not a function.
            $in_func = false;
            $just_got_into_func = false;
        }
        if ( ')' == $token ) {
          if ( 0 == $paren_level ) {
                $in_func = false;
                $args[] = $current_argument;
                $call = [ 'name' => $func_name, 'args' => $args, 'line' => $func_line ];
            if ( $func_comment ) {
              $call['comment'] = $func_comment;
            }
                $function_calls[] = $call;
          }
            $paren_level--;
            continue;
        }
        if ( ',' == $token && 0 == $paren_level ) {
            $args[] = $current_argument;
            $current_argument = null;
            $current_argument_is_just_literal = true;
            continue;
        }
        if ( T_CONSTANT_ENCAPSED_STRING == $id && $current_argument_is_just_literal ) {
            // We can use eval safely, because we are sure $text is just a string literal.
            eval( '$current_argument = ' . $text . ';' );
            continue;
        }
        $current_argument_is_just_literal = false;
        $current_argument = null;
      }
    } elseif(in_array($extension, ['html', 'hbs'])) {

      $function_patterns = [
      '/(__)\(\s*(([\'"]).+?\3)\s*\)/',
      '/(_x)\(\s*([\'"].+?[\'"],\s*[\'"].+?[\'"])\s*\)/',
      '/(_n)\(\s*([\'"].+?[\'"],\s*[\'"].+?[\'"],\s*.+?)\s*\)/',
      ];

      $matches = [];

      foreach($function_patterns as $pattern) {
        preg_match_all($pattern, $code, $function_matches, PREG_OFFSET_CAPTURE);
        for($i = 0; $i < count($function_matches[1]); $i += 1) {
          $matches[] = [
          'call' => $function_matches[0][$i][0],
          'call_offset' => $function_matches[0][$i][1],
          'name' => $function_matches[1][$i][0],
          'arguments' => $function_matches[2][$i][0],
          ];
        }
      }

      foreach($matches as $match) {
        list($text_before_match) = str_split($code, $match['call_offset']);
        $number_of_newlines = strlen($text_before_match) - strlen(str_replace("\n", "", $text_before_match));
        $line_number = $number_of_newlines + 1;

        $arguments_pattern = "/(?s)(?<!\\\\)(\"|')(?:[^\\\\]|\\\\.)*?\\1|[^,\\s]+/";
        preg_match_all($arguments_pattern, $match['arguments'], $arguments_matches);

        $arguments = [];
        foreach($arguments_matches[0] as $argument) {
          // Remove surrounding quotes of the same type from argument strings
          $arguments[] = preg_replace("/^(('|\")+)(.*)\\1$/", "\\3", stripslashes($argument));
        }

        $call = [
        'name' => $match['name'],
        'args' => $arguments,
        'line' => $line_number,
        ];
        $function_calls[] = $call;
      }
    }

      return $function_calls;
  }
}
