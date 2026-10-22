import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import sinon from 'sinon';
import { JSDOM } from 'jsdom';
import {
  HelpTooltipContent,
  tooltipHtmlToReact,
} from '../../../assets/js/src/common/tooltip-html';

function render(html: string): string {
  return renderToStaticMarkup(
    createElement('div', null, tooltipHtmlToReact(html)),
  )
    .replace(/^<div>/, '')
    .replace(/<\/div>$/, '');
}

describe('tooltipHtmlToReact', () => {
  const globals = global as unknown as Record<string, unknown>;
  const names = ['DOMParser', 'document', 'window'];
  const originals = names.map((name) => globals[name]);

  before(() => {
    const { window } = new JSDOM('<!doctype html><html><body></body></html>');
    globals.window = window;
    globals.document = window.document;
    globals.DOMParser = window.DOMParser;
  });

  let errorSpy: sinon.SinonSpy;

  beforeEach(() => {
    errorSpy = sinon.spy(console, 'error');
  });

  afterEach(() => {
    const called = errorSpy.called;
    errorSpy.restore();
    expect(called, 'React logged an error').to.equal(false);
  });

  after(() => {
    names.forEach((name, i) => {
      if (originals[i] === undefined) delete globals[name];
      else globals[name] = originals[i];
    });
  });

  it('renders allowed tags and adds rel to target=_blank links', () => {
    const html = render(
      '<b>bold</b> and <a href="https://example.com" target="_blank">link</a>',
    );
    expect(html).to.equal(
      '<b>bold</b> and <a href="https://example.com" target="_blank" rel="noopener noreferrer">link</a>',
    );
  });

  it('drops scripts, images and event handlers', () => {
    const html = render(
      '<b>x</b><script>marker()</script><img src="x" onerror="marker()">',
    );
    expect(html).to.equal('<b>x</b>');
    expect(html).to.not.contain('marker');
  });

  it('drops unsafe href values', () => {
    const html = render(
      '<a href="javascript:void(0)">x</a><a href=" JaVaScRiPt:void(0)">y</a>',
    );
    expect(html).to.equal('xy');
  });

  it('renders a link without href as plain text', () => {
    expect(render('<a>plain</a>')).to.equal('plain');
  });

  it('unwraps unknown tags and drops their attributes', () => {
    const html = render('<div onclick="x">kept <em>text</em></div>');
    expect(html).to.equal('kept <em>text</em>');
  });

  it('does not double-encode entities', () => {
    const html = render('Fish &amp; chips');
    expect(html).to.equal('Fish &amp; chips');
  });

  it('keeps plain text with shortcodes unchanged', () => {
    const text = 'Hello [subscriber:firstname | default:reader]';
    expect(render(text)).to.equal(text);
  });

  it('sets keys on child elements', () => {
    render('<b>a</b><br><i>b</i>');
    render('<div><b>a</b></div><b>b</b>');
  });

  it('wraps strings in a span and passes other nodes through', () => {
    expect(
      renderToStaticMarkup(
        createElement(HelpTooltipContent, { tooltip: '<b>x</b>' }),
      ),
    ).to.equal(
      '<span style="pointer-events:all;max-width:400px;display:inline-block"><b>x</b></span>',
    );
    expect(
      renderToStaticMarkup(
        createElement(HelpTooltipContent, {
          tooltip: createElement('em', null, 'n'),
        }),
      ),
    ).to.equal('<em>n</em>');
  });
});
