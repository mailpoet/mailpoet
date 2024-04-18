import { parse } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { storeName } from '../../../store/constants';

const contentPattern =
  '<!-- wp:columns -->\n' +
  '<div class="wp-block-columns"><!-- wp:column {"width":"660px","backgroundColor":"base-2","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->\n' +
  '<div class="wp-block-column has-base-2-background-color has-background" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30);flex-basis:660px"><!-- wp:heading {"fontSize":"large"} -->\n' +
  '<h2 class="wp-block-heading has-large-font-size">Content pattern</h2>\n' +
  '<!-- /wp:heading -->\n' +
  '<!-- wp:paragraph -->\n' +
  "<p>Hello I'm content.</p>\n" +
  '<!-- /wp:paragraph -->\n' +
  '<!-- wp:buttons -->\n' +
  '<div class="wp-block-buttons"><!-- wp:button -->\n' +
  '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Click me!</a></div>\n' +
  '<!-- /wp:button --></div>\n' +
  '<!-- /wp:buttons --></div>\n' +
  '<!-- /wp:column --></div>\n' +
  '<!-- /wp:columns -->';

export function getTemplatesForPreview() {
  const templates = select(storeName).getEmailTemplates();
  if (!templates) {
    return [];
  }

  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return templates.map((template) => {
    // @ts-expect-error Missing property type
    const fullContent = template.content?.raw?.replace(
      /<!-- wp:(core)*\/*post-content( {.*})* \/-->/,
      contentPattern,
    );

    return {
      // @ts-expect-error Missing property type
      slug: template.slug,
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
      contentParsed: parse(fullContent),
      patternParsed: parse(contentPattern),
      template,
    };
  });
}
