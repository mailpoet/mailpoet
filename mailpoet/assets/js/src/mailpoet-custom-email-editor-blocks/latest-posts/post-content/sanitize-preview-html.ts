import { removeInvalidHTML } from '@wordpress/dom';
import type {
  Schema,
  SchemaItem,
} from '@wordpress/dom/build-types/dom/clean-node-list';

/**
 * Mirrors CLASSIC_CONTENT_ALLOWED_HTML in
 * lib/EmailEditor/Integrations/MailPoet/Blocks/BlockTypes/LatestPosts.php, so the
 * editor preview shows the markup the email will carry. Keep the two in step.
 */
const ALLOWED_ATTRIBUTES: Record<string, string[]> = {
  a: ['href', 'title'],
  b: [],
  blockquote: ['cite'],
  br: [],
  cite: [],
  code: [],
  em: [],
  figcaption: [],
  figure: [],
  h1: [],
  h2: [],
  h3: [],
  h4: [],
  h5: [],
  h6: [],
  hr: [],
  i: [],
  img: ['src', 'alt', 'width', 'height'],
  li: [],
  ol: ['start'],
  p: [],
  pre: [],
  s: [],
  span: [],
  strong: [],
  table: [],
  tbody: [],
  td: ['colspan', 'rowspan'],
  tfoot: [],
  th: ['colspan', 'rowspan'],
  thead: [],
  tr: [],
  u: [],
  ul: [],
};

/**
 * wp_kses filters tags and attributes without enforcing nesting rules, so every
 * allowed element may contain any other allowed element and text. Anything outside
 * the list is dropped along with its attributes.
 */
function buildSchema(): Schema {
  const schema: Schema = {};
  // allowEmpty keeps void elements such as br, hr and img, and the empty
  // paragraphs wp_kses would leave in place.
  const children: Record<string, SchemaItem> = {
    '#text': { allowEmpty: true },
  };

  Object.keys(ALLOWED_ATTRIBUTES).forEach((tag) => {
    schema[tag] = {
      attributes: ALLOWED_ATTRIBUTES[tag],
      children,
      allowEmpty: true,
    };
    children[tag] = schema[tag];
  });

  // Without this, text sitting directly in the content, rather than inside an
  // element, is dropped along with the text of any top-level inline element.
  schema['#text'] = { allowEmpty: true };

  return schema;
}

const SCHEMA = buildSchema();

const URL_ATTRIBUTES = ['href', 'src'];
const SAFE_PROTOCOLS = ['http:', 'https:', 'mailto:'];

/**
 * The schema keeps an allowed attribute whatever its value, so a URL still has to
 * be checked on its own. Relative and protocol-relative URLs carry no protocol and
 * are left alone.
 */
function stripUnsafeUrls(doc: Document): void {
  URL_ATTRIBUTES.forEach((attribute) => {
    doc.body.querySelectorAll(`[${attribute}]`).forEach((element) => {
      const value = (element.getAttribute(attribute) ?? '').trim();
      const match = /^([a-z][a-z0-9+.-]*):/i.exec(value);
      if (match && !SAFE_PROTOCOLS.includes(`${match[1].toLowerCase()}:`)) {
        element.removeAttribute(attribute);
      }
    });
  });
}

/**
 * Returns markup that is safe to place in the editor canvas: the allow-list above,
 * with URL attributes limited to protocols an email client will follow.
 */
export function sanitizePreviewHtml(html: string): string {
  if (!html) {
    return '';
  }

  const doc = document.implementation.createHTMLDocument('');
  // The third argument keeps block-level elements; the content is not inline.
  doc.body.innerHTML = removeInvalidHTML(html, SCHEMA, false);
  stripUnsafeUrls(doc);

  return doc.body.innerHTML;
}
