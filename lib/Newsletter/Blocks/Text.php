<?php namespace MailPoet\Newsletter\Blocks;

class Text {

  static function render($element) {
    // convert <blockquote> elements to tables
    $blockquoteTemplate = '<table cellpadding="0" cellspacing="0" border="0" class="mailpoet_blockquote">
						     <tbody>
							   <tr>
								 <td valign="top">
								   $1
							     </td>
							   </tr>
						     </tbody>
						   </table>
						   <br>';
    $element['text'] = preg_replace('/<blockquote>(.*?)<\/blockquote>/s', $blockquoteTemplate, $element['text']);

    // add line breaks after tags
    $element['text'] = preg_replace('/(<\/(ul|ol|h\d)>)/', '$1<br>', $element['text']);

    $element['text'] = self::removeEmptyHTMLTags($element['text']);

    // convert empty <p> tags to line breaks
    $element['text'] = preg_replace('/<p(?:.+style=\".*?\")?><\/p>/', '<br>', $element['text']);

    // convert <p> to <span>
    $element['text'] = preg_replace('/<p>(.*?)<\/p>/', '<table cellpadding="0" cellspacing="0" border="0"><tr><td><span class="paragraph">$1</span></td></tr></table>', $element['text']);
    $element['text'] = preg_replace('/<p(.+style=\".*?\")?>(.*?)<\/p>/', '<table cellpadding="0" cellspacing="0" border="0"><tr><td $1><span class="paragraph">$2</span></td></tr></table>', $element['text']);

    // remove the last break line
    $element['text'] = preg_replace('/<br>([^<br>]*)$/s', '', $element['text']);

    $template = '
<tr>
  <td class="mailpoet_col mailpoet_text mailpoet_padded" valign="top">' . $element['text'] . ' </td>
</tr>';

    return $template;
  }

  static function removeEmptyHTMLTags($html) {
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