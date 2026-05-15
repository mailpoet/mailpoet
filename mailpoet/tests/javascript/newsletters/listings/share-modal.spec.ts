import { getShareLinks } from '../../../../assets/js/src/newsletters/listings/share-links';

describe('Newsletter share modal helpers', () => {
  it('builds encoded social sharing URLs', () => {
    const links = getShareLinks(
      'https://example.com/mailpoet-email/123-email/',
      'Spring sale & updates',
    );

    expect(links.find((link) => link.name === 'facebook')?.url).to.contain(
      'u=https%3A%2F%2Fexample.com%2Fmailpoet-email%2F123-email%2F',
    );
    expect(links.find((link) => link.name === 'x')?.url).to.contain(
      'text=Spring%20sale%20%26%20updates',
    );
    expect(links.find((link) => link.name === 'email')?.url).to.equal(
      'mailto:?subject=Spring%20sale%20%26%20updates&body=https%3A%2F%2Fexample.com%2Fmailpoet-email%2F123-email%2F',
    );
  });
});
