import { JSDOM } from 'jsdom';

const dom = new JSDOM('<!doctype html><html><body></body></html>');
const globalWithDom = global as unknown as Record<string, unknown>;
globalWithDom.window = dom.window;
globalWithDom.document = dom.window.document;
globalWithDom.Node = dom.window.Node;
globalWithDom.Element = dom.window.Element;

// eslint-disable-next-line import/first -- the DOM globals above must exist before the module loads.
import { sanitizePreviewHtml } from '../../../assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/post-content/sanitize-preview-html';

describe('sanitizePreviewHtml', () => {
  it('drops event handler attributes but keeps the element', () => {
    const result = sanitizePreviewHtml(
      '<p>before</p><img src="a.png" onerror="broken()"><p>after</p>',
    );
    expect(result).to.not.contain('onerror');
    expect(result).to.contain('<p>before</p>');
    expect(result).to.contain('<p>after</p>');
    expect(result).to.contain('src="a.png"');
  });

  it('unwraps elements that are not on the allow list', () => {
    expect(
      sanitizePreviewHtml('<iframe src="https://x.test"></iframe><p>kept</p>'),
    ).to.equal('<p>kept</p>');
    expect(
      sanitizePreviewHtml('<svg onload="broken()"></svg><p>kept</p>'),
    ).to.equal('<p>kept</p>');
    // The tag goes, the text inside it stays as text, the same as wp_kses.
    expect(sanitizePreviewHtml('<p>a</p><script>inert()</script>')).to.equal(
      '<p>a</p>inert()',
    );
  });

  it('checks the protocol of a cite attribute as well', () => {
    expect(
      sanitizePreviewHtml(
        '<blockquote cite="javascript:broken()">q</blockquote>',
      ),
    ).to.equal('<blockquote>q</blockquote>');
    expect(
      sanitizePreviewHtml('<blockquote cite="https://x.test">q</blockquote>'),
    ).to.contain('cite="https://x.test"');
  });

  it('drops a url attribute whose protocol an email client would not follow', () => {
    expect(
      sanitizePreviewHtml('<a href="javascript:broken()">text</a>'),
    ).to.equal('<a>text</a>');
    expect(
      sanitizePreviewHtml('<a href="VBscript:broken()">text</a>'),
    ).to.equal('<a>text</a>');
    expect(
      sanitizePreviewHtml('<a href="JaVaScRiPt:broken()">text</a>'),
    ).to.equal('<a>text</a>');
  });

  it('drops a url whose protocol is hidden behind control characters', () => {
    // A browser strips these before it reads the protocol, so the value still
    // resolves to a script url even though it does not start with one.
    const tab = String.fromCharCode(9);
    const newline = String.fromCharCode(10);
    const carriageReturn = String.fromCharCode(13);
    const control = String.fromCharCode(1);

    expect(
      sanitizePreviewHtml(`<a href="java${tab}script:broken()">t</a>`),
    ).to.equal('<a>t</a>');
    expect(
      sanitizePreviewHtml(`<a href="java${newline}script:broken()">t</a>`),
    ).to.equal('<a>t</a>');
    expect(
      sanitizePreviewHtml(
        `<a href="java${carriageReturn}script:broken()">t</a>`,
      ),
    ).to.equal('<a>t</a>');
    expect(
      sanitizePreviewHtml(`<a href="${control}javascript:broken()">t</a>`),
    ).to.equal('<a>t</a>');
  });

  it('keeps the other protocols wp_kses allows', () => {
    expect(sanitizePreviewHtml('<a href="tel:+123">t</a>')).to.contain(
      'href="tel:+123"',
    );
    expect(sanitizePreviewHtml('<a href="ftp://x.test/f">t</a>')).to.contain(
      'href="ftp://x.test/f"',
    );
    expect(sanitizePreviewHtml('<a href="mailto:a@x.test">t</a>')).to.contain(
      'href="mailto:a@x.test"',
    );
  });

  it('keeps relative and protocol-relative urls', () => {
    expect(sanitizePreviewHtml('<a href="/page">text</a>')).to.contain(
      'href="/page"',
    );
    expect(sanitizePreviewHtml('<img src="../a.png" alt="a">')).to.contain(
      'src="../a.png"',
    );
    expect(sanitizePreviewHtml('<a href="//cdn.test/x">text</a>')).to.contain(
      'href="//cdn.test/x"',
    );
  });

  it('keeps the formatting the email itself carries', () => {
    const result = sanitizePreviewHtml(
      '<p>keep <strong>bold</strong> <em>italic</em> <a href="https://x.test" title="t">link</a></p>',
    );
    expect(result).to.equal(
      '<p>keep <strong>bold</strong> <em>italic</em> <a href="https://x.test" title="t">link</a></p>',
    );
  });

  it('keeps lists, tables and blockquotes with their allowed attributes', () => {
    expect(sanitizePreviewHtml('<ul><li>one</li><li>two</li></ul>')).to.equal(
      '<ul><li>one</li><li>two</li></ul>',
    );
    expect(
      sanitizePreviewHtml(
        '<table><tbody><tr><td colspan="2">cell</td></tr></tbody></table>',
      ),
    ).to.contain('colspan="2"');
    expect(
      sanitizePreviewHtml(
        '<blockquote cite="https://x.test"><p>q</p></blockquote>',
      ),
    ).to.contain('cite="https://x.test"');
  });

  it('keeps text that sits outside any element', () => {
    expect(sanitizePreviewHtml('plain text')).to.equal('plain text');
    expect(sanitizePreviewHtml('lead <strong>b</strong><p>para</p>')).to.equal(
      'lead <strong>b</strong><p>para</p>',
    );
  });

  it('keeps the classes block markup relies on', () => {
    expect(sanitizePreviewHtml('<p class="alignleft">t</p>')).to.contain(
      'class="alignleft"',
    );
    expect(
      sanitizePreviewHtml(
        '<figure class="wp-block-image size-large">f</figure>',
      ),
    ).to.contain('class="wp-block-image size-large"');
  });

  it('keeps the text-level elements the editor writes', () => {
    expect(
      sanitizePreviewHtml('<p>H<sub>2</sub>O and x<sup>2</sup></p>'),
    ).to.equal('<p>H<sub>2</sub>O and x<sup>2</sup></p>');
    expect(
      sanitizePreviewHtml('<p><del>old</del><ins>new</ins><mark>hi</mark></p>'),
    ).to.equal('<p><del>old</del><ins>new</ins><mark>hi</mark></p>');
    expect(
      sanitizePreviewHtml(
        '<p><small>s</small><abbr title="a">b</abbr><kbd>k</kbd></p>',
      ),
    ).to.contain('title="a"');
    expect(sanitizePreviewHtml('<dl><dt>t</dt><dd>d</dd></dl>')).to.equal(
      '<dl><dt>t</dt><dd>d</dd></dl>',
    );
    expect(
      sanitizePreviewHtml('<address>a</address><q cite="https://x.test">q</q>'),
    ).to.contain('cite="https://x.test"');
  });

  it('keeps the table attributes email clients need', () => {
    const table = sanitizePreviewHtml(
      '<table width="600" border="0" cellpadding="4" cellspacing="0" align="center">' +
        '<tbody><tr valign="top"><td align="left" width="300">c</td></tr></tbody></table>',
    );
    expect(table).to.contain('width="600"');
    expect(table).to.contain('cellpadding="4"');
    expect(table).to.contain('align="center"');
    expect(table).to.contain('valign="top"');
  });

  it('drops attributes that are not on the allow list', () => {
    expect(sanitizePreviewHtml('<p style="color:red">styled</p>')).to.equal(
      '<p>styled</p>',
    );
    // class is kept, id is not: an id in the canvas can shadow a global.
    expect(sanitizePreviewHtml('<p class="x" id="y">t</p>')).to.equal(
      '<p class="x">t</p>',
    );
  });

  it('returns an empty string for empty input', () => {
    expect(sanitizePreviewHtml('')).to.equal('');
  });
});
