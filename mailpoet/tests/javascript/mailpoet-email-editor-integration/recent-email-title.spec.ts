import { JSDOM } from 'jsdom';
import { getRecentEmailTitle } from '../../../assets/js/src/mailpoet-email-editor-integration/recent-email-title';

const defaultName = 'New Email';
const defaultSubject = 'Subject';

describe('getRecentEmailTitle', () => {
  let previousDocument: Document;

  // decodeEntities needs a document.
  before(() => {
    previousDocument = globalThis.document;
    globalThis.document = new JSDOM('').window.document;
  });

  after(() => {
    globalThis.document = previousDocument;
  });

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
      about: 'treats a stored entity-encoded name as matching the subject',
      name: 'Sale &amp; more',
      subject: 'Sale & more',
      expected: 'Sale & more',
    },
    {
      about: 'decodes a stored name that differs from the subject',
      name: 'Tips &amp; tricks',
      subject: 'Autumn sale',
      expected: 'Tips & tricks – Autumn sale',
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
      about: 'shows only the name when the subject is the default subject',
      name: 'Template Two',
      subject: defaultSubject,
      expected: 'Template Two',
    },
    {
      about: 'returns nothing when name and subject are both defaults',
      name: defaultName,
      subject: defaultSubject,
      expected: '',
    },
    {
      about: 'returns nothing when there is no name or subject',
      name: defaultName,
      subject: '',
      expected: '',
    },
  ].forEach(({ about, name, subject, expected }) => {
    it(about, () => {
      expect(
        getRecentEmailTitle({ name, subject, defaultName, defaultSubject }),
      ).to.equal(expected);
    });
  });
});
