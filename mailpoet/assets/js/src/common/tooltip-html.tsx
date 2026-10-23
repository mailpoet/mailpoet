import { createElement, ReactNode } from 'react';

const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'a', 'br', 'span', 'p', 'code'];
const DROP_WITH_CONTENT = ['script', 'style', 'template', 'iframe', 'noscript'];
const ALLOWED_HREF_PREFIXES = ['http:', 'https:', 'mailto:', '#', '/'];

function convertAttributes(element: Element): Record<string, string> {
  const props: Record<string, string> = {};
  const tag = element.tagName.toLowerCase();

  const href = (element.getAttribute('href') ?? '').trim();
  if (
    tag === 'a' &&
    ALLOWED_HREF_PREFIXES.some((prefix) =>
      href.toLowerCase().startsWith(prefix),
    )
  ) {
    props.href = href;
  }
  ['target', 'rel', 'title'].forEach((name) => {
    const value = element.getAttribute(name);
    if (value !== null) props[name] = value;
  });
  const className = element.getAttribute('class');
  if (className !== null) props.className = className;
  if (props.target === '_blank') {
    const tokens = (props.rel ?? '')
      .toLowerCase()
      .split(/\s+/)
      .filter((token) => token && token !== 'opener');
    props.rel = Array.from(new Set([...tokens, 'noopener', 'noreferrer'])).join(
      ' ',
    );
  }
  return props;
}

function convertNodes(nodes: NodeListOf<ChildNode>): ReactNode[] {
  const result: ReactNode[] = [];
  nodes.forEach((node) => {
    if (node.nodeType === 3) {
      result.push(node.textContent);
      return;
    }
    if (node.nodeType !== 1) return;
    const element = node as Element;
    const tag = element.tagName.toLowerCase();
    if (DROP_WITH_CONTENT.includes(tag)) return;
    const children = convertNodes(element.childNodes);
    if (!ALLOWED_TAGS.includes(tag)) {
      result.push(...children);
      return;
    }
    const attributes = convertAttributes(element);
    if (tag === 'a' && !attributes.href) {
      result.push(...children);
      return;
    }
    const props = { ...attributes, key: result.length };
    result.push(
      tag === 'br'
        ? createElement(tag, props)
        : createElement(tag, props, ...children),
    );
  });
  return result;
}

// DOMParser documents are inert: scripts do not run and images do not load.
export function tooltipHtmlToReact(html: string): ReactNode {
  const doc = new DOMParser().parseFromString(html, 'text/html');
  return convertNodes(doc.body.childNodes);
}

export function HelpTooltipContent({ tooltip }: { tooltip: ReactNode }) {
  if (typeof tooltip !== 'string') return <>{tooltip}</>;
  return (
    <span
      style={{
        pointerEvents: 'all',
        maxWidth: '400px',
        display: 'inline-block',
      }}
    >
      {tooltipHtmlToReact(tooltip)}
    </span>
  );
}
