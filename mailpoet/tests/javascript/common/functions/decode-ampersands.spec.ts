import { decodeAmpersands } from '../../../../assets/js/src/common/functions/decode-ampersands';

describe('decodeAmpersands', () => {
  it('decodes the HTML-escaped forms of "&"', () => {
    expect(decodeAmpersands('https://example.com/?a=1&amp;b=2')).to.equal(
      'https://example.com/?a=1&b=2',
    );
    expect(
      decodeAmpersands('https://example.com/?a=1&#038;b=2&#38;c=3'),
    ).to.equal('https://example.com/?a=1&b=2&c=3');
  });

  it('decodes once and leaves other entities as they are', () => {
    expect(decodeAmpersands('https://example.com/?q=&amp;amp;&quot;')).to.equal(
      'https://example.com/?q=&amp;&quot;',
    );
  });
});
