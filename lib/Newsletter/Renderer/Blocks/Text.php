<?php namespace MailPoet\Newsletter\Renderer\Blocks;

class Text {

  static $typeFace = array(
    'Arial' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
    'Comic Sans MS' => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
    'Courier New' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
    'Georgia' => "Georgia, Times, 'Times New Roman', serif",
    'Lucida' => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
    'Tahoma' => "Tahoma, Verdana, Segoe, sans-serif",
    'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
    'Trebuchet MS' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    'Verdana' => "Verdana, Geneva, sans-serif"
  );

  static function render($element) {
    $html = $element['text'];

    $html = self::convertBlockquotesToTables($html);
    $html = self::addLineBreakAfterTags($html);
    $html = self::removeEmptyTags($html);
    $html = self::convertEmptyParagraphsToLineBreaks($html);
    $html = self::convertParagraphsToTables($html);
    $html = self::removeLastBreakLine($html);

    $template = '
    <tr>
      <td class="mailpoet_col mailpoet_text mailpoet_padded" valign="top">' . $html . ' </td>
    </tr>';
    
    return $template;
  }

  static function removeLastBreakLine($html) {
    return preg_replace('/<br>([^<br>]*)$/s', '', $html);
  }
  
  static function convertParagraphsToTables($html) {
    $html = preg_replace('/<p>(.*?)<\/p>/', '
    <table cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td>
          <span class="paragraph">
            $1
          </span>
        </td>
      </tr>
    </table>', $html);

    return preg_replace('/<p(.+style=\".*?\")?>(.*?)<\/p>/', '
    <table cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td $1>
          <span class="paragraph">
            $2
          </span>
        </td>
      </tr>
    </table>', $html);
  }
  
  static function convertEmptyParagraphsToLineBreaks($html) {
    return preg_replace('/<p(?:.+style=\".*?\")?><\/p>/', '<br>', $html);
  }
  
  static function addLineBreakAfterTags($html) {
    return preg_replace('/(<\/(ul|ol|h\d)>)/', '$1<br>', $html);
  }
  
  static function convertBlockquotesToTables($html) {
    $template = '
    <table cellpadding="0" cellspacing="0" border="0" class="mailpoet_blockquote">
      <tbody>
        <tr>
          <td valign="top">$1</td>
        </tr>
      </tbody>
    </table>
    <br>';
    
    return preg_replace('/<blockquote>(.*?)<\/blockquote>/s', $template, $html);
  }
  
  static function removeEmptyTags($html) {
    $pattern = <<<'EOD'
    ~
    <
    (?:
    	!--[^-]*(?:-(?!->)[^-]*)*-->[^<]*(*SKIP)(*F) # skip comments
      |
    	( # group 1
    		(span|em|strong)     # tag name in group 2
    		[^"'>]* #'"# all that is not a quote or a closing angle bracket
    		(?: # quoted attributes
    			"[^\\"]*(?:\\.[^\\"]*)*+" [^"'>]* #'"# double quote
    		  |
    			'[^\\']*(?:\\.[^\\']*)*+' [^"'>]* #'"# single quote
    		)*+
    		>
    		\s*
    		(?:
    			<!--[^-]*(?:-(?!->)[^-]*)*+--> \s* # html comments
    		  |
    			<(?1) \s*                          # recursion with the group 1
    		)*+
    		</\2> # closing tag
    	) # end of the group 1
    )
    ~sxi    
EOD;
    
    return preg_replace($pattern, '', $html);
  }
  
}