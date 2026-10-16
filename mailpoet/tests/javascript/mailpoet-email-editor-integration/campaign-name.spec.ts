import { getCampaignNameForSubject } from '../../../assets/js/src/mailpoet-email-editor-integration/sidebar/campaign-name';

const defaultName = 'New Email';

describe('getCampaignNameForSubject', () => {
  it('follows the subject while the name is the default name', () => {
    expect(
      getCampaignNameForSubject({
        name: defaultName,
        previousSubject: 'Subject',
        newSubject: 'Autumn sale',
        defaultName,
      }),
    ).to.equal('Autumn sale');
  });

  it('follows the subject while the name is empty', () => {
    expect(
      getCampaignNameForSubject({
        name: '',
        previousSubject: 'Subject',
        newSubject: 'Autumn sale',
        defaultName,
      }),
    ).to.equal('Autumn sale');
  });

  it('follows the subject while the name matches the previous subject', () => {
    expect(
      getCampaignNameForSubject({
        name: 'Autumn sal',
        previousSubject: 'Autumn sal',
        newSubject: 'Autumn sale',
        defaultName,
      }),
    ).to.equal('Autumn sale');
  });

  it('keeps a name the user picked', () => {
    expect(
      getCampaignNameForSubject({
        name: 'October promo',
        previousSubject: 'Autumn sal',
        newSubject: 'Autumn sale',
        defaultName,
      }),
    ).to.equal(null);
  });

  it('keeps the name of an existing email whose name differs from its subject', () => {
    expect(
      getCampaignNameForSubject({
        name: 'Welcome series #1',
        previousSubject: 'Hello there',
        newSubject: 'Hello there!',
        defaultName,
      }),
    ).to.equal(null);
  });

  it('goes back to the default name when the subject is cleared', () => {
    expect(
      getCampaignNameForSubject({
        name: 'Autumn sale',
        previousSubject: 'Autumn sale',
        newSubject: '',
        defaultName,
      }),
    ).to.equal(defaultName);
  });
});
