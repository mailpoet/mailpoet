import { JSDOM } from 'jsdom';
import { loadPublicBundle } from '../../../assets/js/src/public-loader';

const base = 'https://example.com/wp-content/plugins/mailpoet/assets/dist/js';

describe('loadPublicBundle', () => {
  let doc: Document;

  const scripts = () =>
    Array.from(doc.querySelectorAll('script')).map((s) =>
      s.getAttribute('src'),
    );

  const setup = (srcs: string[]) => {
    const dom = new JSDOM(
      `<!doctype html><head></head><body>${srcs
        .map((src) => `<script src="${src}"></script>`)
        .join('')}<p id="after"></p></body>`,
    );
    doc = dom.window.document;
    return Array.from(doc.querySelectorAll('script'));
  };

  it('loads public.min.js next to the current script and keeps the query string', () => {
    const [current] = setup([`${base}/public.js?mp_ver=1.2`]);
    loadPublicBundle(doc, current);
    expect(scripts()).to.deep.equal([
      `${base}/public.js?mp_ver=1.2`,
      `${base}/public.min.js?mp_ver=1.2`,
    ]);
    const added = current.nextElementSibling as HTMLScriptElement;
    expect(added.async).to.equal(false);
  });

  it('handles a URL without a query string', () => {
    const [current] = setup([`${base}/public.js`]);
    loadPublicBundle(doc, current);
    expect(scripts()[1]).to.equal(`${base}/public.min.js`);
  });

  it('handles a URL with a hash', () => {
    const [current] = setup([`${base}/public.js?mp_ver=1#top`]);
    loadPublicBundle(doc, current);
    expect(scripts()[1]).to.equal(`${base}/public.min.js?mp_ver=1#top`);
  });

  it('falls back to the last public.js script when currentScript is missing', () => {
    setup([
      `${base}/public.js?a=1`,
      'https://example.com/other.js',
      `${base}/public.js?mp_ver=2`,
    ]);
    loadPublicBundle(doc, null);
    expect(scripts()).to.include(`${base}/public.min.js?mp_ver=2`);
    expect(scripts()).to.not.include(`${base}/public.min.js?a=1`);
  });

  it('does not load the bundle twice', () => {
    const [current] = setup([
      `${base}/public.min.js?mp_ver=1.2`,
      `${base}/public.js?mp_ver=1.2`,
    ]);
    loadPublicBundle(doc, current);
    expect(scripts()).to.have.length(2);
  });

  it('does nothing when no public.js script exists', () => {
    setup(['https://example.com/other.js']);
    loadPublicBundle(doc, null);
    expect(scripts()).to.deep.equal(['https://example.com/other.js']);
  });
});
