import { getRecentEmailTitle } from '../../../assets/js/src/mailpoet-email-editor-integration/recent-email-title';

const defaultName = 'New Email';

describe('getRecentEmailTitle', () => {
  [
    {
      about: 'shows both when name and subject differ',
      name: 'October promo',
      subject: 'Autumn sale',
      expected: 'October promo – Autumn sale',
    },
    {
      about: 'shows one when name and subject match',
      name: 'Autumn sale',
      subject: 'Autumn sale',
      expected: 'Autumn sale',
    },
    {
      about: 'shows the subject when the name is the default name',
      name: defaultName,
      subject: 'Autumn sale',
      expected: 'Autumn sale',
    },
    {
      about: 'shows the subject when the name is empty',
      name: '',
      subject: 'Autumn sale',
      expected: 'Autumn sale',
    },
    {
      about: 'shows the name when the subject is empty',
      name: 'October promo',
      subject: '',
      expected: 'October promo',
    },
    {
      about: 'returns nothing when there is no name or subject',
      name: defaultName,
      subject: '',
      expected: '',
    },
  ].forEach(({ about, name, subject, expected }) => {
    it(about, () => {
      expect(getRecentEmailTitle({ name, subject, defaultName })).to.equal(
        expected,
      );
    });
  });
});
