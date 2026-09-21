import { removeInvalidHTML } from '@wordpress/dom';

// Taken from the function's own signature: @wordpress/dom does not export these
// types from its package root, and a deep import into build-types would break the
// build the first time the package moves a file.
type Schema = Parameters<typeof removeInvalidHTML>[1];
type SchemaItem = Schema[string];

/**
 * Mirrors CLASSIC_CONTENT_ALLOWED_HTML in
 * lib/EmailEditor/Integrations/MailPoet/Blocks/BlockTypes/LatestPosts.php, which is
 * what a classic post's content is reduced to before it reaches the email. Keep the
 * two in step. A block post is rendered block by block instead and can carry more
 * than this, so for those the preview shows less than the email does.
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
 * allowed element may contain any other allowed element and text. An element outside
 * the list is unwrapped rather than deleted: the tag and its attributes go and the
 * text inside it stays, which is what wp_kses does with the same content.
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

// The URI attributes wp_kses_uri_attributes() covers that this allow-list permits.
const URL_ATTRIBUTES = ['href', 'src', 'cite'];

/** wp_allowed_protocols(), the set wp_kses() applies to the same content. */
const SAFE_PROTOCOLS = [
  'fax:',
  'feed:',
  'ftp:',
  'ftps:',
  'gopher:',
  'http:',
  'https:',
  'irc:',
  'irc6:',
  'ircs:',
  'mailto:',
  'mms:',
  'news:',
  'nntp:',
  'rtsp:',
  'sms:',
  'svn:',
  'tel:',
  'telnet:',
  'urn:',
  'webcal:',
  'xmpp:',
];

// Only the protocol of the parsed result is read, so any absolute base works. A
// fixed one keeps the answer the same in the editor and under test.
const PROTOCOL_BASE = 'https://email-preview.invalid';

/**
 * The URL parser rather than a pattern: a browser drops tabs, newlines and leading
 * control characters before it reads the protocol, so a pattern that does not would
 * keep a value the browser still resolves to a script URL. A value the parser
 * rejects is dropped too, since nothing can follow it safely.
 */
function hasSafeProtocol(value: string): boolean {
  try {
    return SAFE_PROTOCOLS.includes(new URL(value, PROTOCOL_BASE).protocol);
  } catch {
    return false;
  }
}

/** The schema keeps an allowed attribute whatever its value, so URLs need their own pass. */
function stripUnsafeUrls(doc: Document): void {
  URL_ATTRIBUTES.forEach((attribute) => {
    doc.body.querySelectorAll(`[${attribute}]`).forEach((element) => {
      if (!hasSafeProtocol(element.getAttribute(attribute) ?? '')) {
        element.removeAttribute(attribute);
      }
    });
  });
}

/**
 * Returns markup that is safe to place in the editor canvas: the allow-list above,
 * with URL attributes limited to the protocols wp_kses() allows.
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
