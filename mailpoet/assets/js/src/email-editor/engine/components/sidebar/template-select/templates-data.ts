import { parse } from '@wordpress/blocks';

const contentPattern =
  '<!-- wp:heading {"fontSize":"large"} -->' +
  '<h2 class="wp-block-heading has-large-font-size">Content pattern</h2>' +
  '<!-- /wp:heading -->' +
  '<!-- wp:columns -->' +
  '<div class="wp-block-columns"><!-- wp:column -->' +
  '<div class="wp-block-column"><!-- wp:paragraph -->' +
  '<p>Paragraph in column</p>' +
  '<!-- /wp:paragraph --></div>' +
  '<!-- /wp:column -->' +
  '<!-- wp:column -->' +
  '<div class="wp-block-column"><!-- wp:buttons -->' +
  '<div class="wp-block-buttons"><!-- wp:button -->' +
  '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Hello button</a></div>' +
  '<!-- /wp:button --></div>' +
  '<!-- /wp:buttons --></div>' +
  '<!-- /wp:column --></div>' +
  '<!-- /wp:columns -->';

const template1 = {
  id: 't1',
  content:
    '<!-- wp:heading -->' +
    '<h2 class="wp-block-heading">HEADER 1</h2>' +
    '<!-- /wp:heading -->' +
    '<!-- wp:post-content /-->' +
    '<!-- wp:heading -->' +
    '<h2 class="wp-block-heading">FOOTER 1</h2>' +
    '<!-- /wp:heading -->',
};

const template2 = {
  id: 't2',
  content:
    '<!-- wp:heading -->' +
    '<h2 class="wp-block-heading">HEADER 2</h2>\n' +
    '<!-- /wp:heading -->\n' +
    '<!-- wp:post-content /-->\n' +
    '<!-- wp:heading -->\n' +
    '<h2 class="wp-block-heading">FOOTER 2</h2>\n' +
    '<!-- /wp:heading -->',
};
export function getTemplatesForPreview() {
  const templates = [template1, template2];
  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return templates.map((template) => {
    const fullContent = template.content.replace(
      '<!-- wp:post-content /-->',
      contentPattern,
    );
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return {
      id: template.id,
      contentParsed: parse(fullContent),
      patternParsed: parse(contentPattern),
      template,
    };
  });
}
