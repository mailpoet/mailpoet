const ELEMENT_NODE = 1;
const TEXT_NODE = 3;

// Tags kept in messages, with the attributes each of them may keep.
const allowedAttributes: Record<string, string[]> = {
  a: ['href', 'target', 'rel', 'class'],
  b: [],
  br: [],
  button: ['class', 'type', 'value'],
  code: [],
  del: [],
  em: [],
  i: [],
  ins: [],
  p: ['class'],
  span: ['class'],
  strong: [],
};

// Tags removed together with their content. Any other tag is replaced by its content.
const removedTags = [
  'embed',
  'iframe',
  'math',
  'noembed',
  'noframes',
  'noscript',
  'object',
  'plaintext',
  'script',
  'style',
  'svg',
  'template',
  'textarea',
  'title',
  'xmp',
];

const allowedProtocols = ['http:', 'https:', 'mailto:'];

const isAllowedHref = (href: string): boolean => {
  try {
    return allowedProtocols.includes(
      new URL(href, window.location.href).protocol,
    );
  } catch {
    return false;
  }
};

const filterAttributes = (element: Element, allowed: string[]): void => {
  Array.from(element.attributes).forEach(({ name, value }) => {
    const isAllowed =
      allowed.includes(name) ||
      (element.nodeName.toLowerCase() === 'a' && name.startsWith('data-'));
    if (!isAllowed || (name === 'href' && !isAllowedHref(value))) {
      element.removeAttribute(name);
    }
  });
};

const filterChildren = (parent: Element): void => {
  Array.from(parent.childNodes).forEach((node) => {
    if (node.nodeType === TEXT_NODE) {
      return;
    }
    if (node.nodeType !== ELEMENT_NODE) {
      parent.removeChild(node);
      return;
    }
    const element = node as Element;
    const tag = element.nodeName.toLowerCase();
    if (removedTags.includes(tag)) {
      parent.removeChild(element);
      return;
    }
    filterChildren(element);
    if (!Object.prototype.hasOwnProperty.call(allowedAttributes, tag)) {
      element.replaceWith(...Array.from(element.childNodes));
      return;
    }
    filterAttributes(element, allowedAttributes[tag]);
  });
};

/**
 * Keeps the simple markup used in notices and dialog titles (links, bold, line breaks, …)
 * and turns everything else into plain text.
 */
export const filterMessageHtml = (html: string): string => {
  // The HTML is parsed in a separate document, so nothing in it is loaded or run.
  const { body } = document.implementation.createHTMLDocument('');
  body.innerHTML = html;
  filterChildren(body);
  return body.innerHTML;
};
