import { JSDOM } from 'jsdom';
import { getGroupedNewsletterOptions } from '../../../../../../../assets/js/src/segments/dynamic/dynamic-segments-filters/fields/email/newsletter-options';

type Newsletter = {
  id: string;
  name: string;
  subject: string;
  sent_at: string | null;
  type: 'standard' | 'automation' | 'automation_transactional';
};

const setBrowserGlobals = (): void => {
  const dom = new JSDOM('<!doctype html><html><body></body></html>');
  const globals = global as typeof globalThis & { window: Window };
  globals.window = dom.window as unknown as Window & typeof globalThis;
  globals.document = dom.window.document;
};

const makeNewsletter = (overrides: Partial<Newsletter>): Newsletter => ({
  id: '1',
  name: 'A newsletter',
  subject: 'subject',
  sent_at: null,
  type: 'standard',
  ...overrides,
});

const formatDate = (date: string): string => `formatted(${date})`;

describe('getGroupedNewsletterOptions', () => {
  before(() => {
    setBrowserGlobals();
  });

  it('returns empty arrays for an empty list', () => {
    const { flatOptions, groupedOptions } = getGroupedNewsletterOptions(
      [],
      formatDate,
    );
    expect(flatOptions).to.deep.equal([]);
    expect(groupedOptions).to.deep.equal([]);
  });

  it('returns empty arrays when the newsletters list is undefined', () => {
    const { flatOptions, groupedOptions } = getGroupedNewsletterOptions(
      undefined,
      formatDate,
    );
    expect(flatOptions).to.deep.equal([]);
    expect(groupedOptions).to.deep.equal([]);
  });

  it('groups standard newsletters under the Standard emails label', () => {
    const { groupedOptions } = getGroupedNewsletterOptions(
      [makeNewsletter({ id: '7', name: 'Welcome', type: 'standard' })],
      formatDate,
    );
    expect(groupedOptions).to.have.lengthOf(1);
    expect(groupedOptions[0].label).to.equal('Standard emails');
    expect(groupedOptions[0].options).to.have.lengthOf(1);
    expect(groupedOptions[0].options[0]).to.include({
      label: 'Welcome',
      value: 7,
    });
  });

  it('groups automation and automation_transactional under the Automation emails label', () => {
    const { groupedOptions } = getGroupedNewsletterOptions(
      [
        makeNewsletter({ id: '11', name: 'Drip 1', type: 'automation' }),
        makeNewsletter({
          id: '12',
          name: 'Receipt',
          type: 'automation_transactional',
        }),
      ],
      formatDate,
    );
    expect(groupedOptions).to.have.lengthOf(1);
    expect(groupedOptions[0].label).to.equal('Automation emails');
    const automationOptionValues = groupedOptions[0].options.map(
      (option) => option.value,
    );
    expect(automationOptionValues).to.deep.equal([11, 12]);
  });

  it('orders the Standard group before the Automation group', () => {
    const { groupedOptions } = getGroupedNewsletterOptions(
      [
        makeNewsletter({ id: '20', name: 'Drip', type: 'automation' }),
        makeNewsletter({ id: '21', name: 'Welcome', type: 'standard' }),
      ],
      formatDate,
    );
    expect(groupedOptions.map((group) => group.label)).to.deep.equal([
      'Standard emails',
      'Automation emails',
    ]);
  });

  it('omits a group when there are no newsletters of that kind', () => {
    const { groupedOptions } = getGroupedNewsletterOptions(
      [makeNewsletter({ id: '1', type: 'standard' })],
      formatDate,
    );
    expect(groupedOptions.map((group) => group.label)).to.deep.equal([
      'Standard emails',
    ]);
  });

  it('flat options mirror the grouped options in order', () => {
    const { flatOptions, groupedOptions } = getGroupedNewsletterOptions(
      [
        makeNewsletter({ id: '3', type: 'automation' }),
        makeNewsletter({ id: '1', type: 'standard' }),
        makeNewsletter({ id: '2', type: 'standard' }),
      ],
      formatDate,
    );
    const flatValues = flatOptions.map((option) => option.value);
    const groupedValues = groupedOptions.flatMap((group) =>
      group.options.map((option) => option.value),
    );
    expect(flatValues).to.deep.equal(groupedValues);
    expect(flatValues).to.deep.equal([1, 2, 3]);
  });

  it('uses the not sent yet tag when sent_at is missing', () => {
    const { flatOptions } = getGroupedNewsletterOptions(
      [makeNewsletter({ id: '1', sent_at: null, type: 'standard' })],
      formatDate,
    );
    expect(flatOptions[0].tag).to.equal('Not sent yet');
  });

  it('formats the tag through the injected formatter when sent_at is present', () => {
    const { flatOptions } = getGroupedNewsletterOptions(
      [
        makeNewsletter({
          id: '1',
          sent_at: '2025-01-15 09:30:00',
          type: 'standard',
        }),
      ],
      formatDate,
    );
    expect(flatOptions[0].tag).to.equal('formatted(2025-01-15 09:30:00)');
  });

  it('coerces the newsletter id from string to number', () => {
    const { flatOptions } = getGroupedNewsletterOptions(
      [makeNewsletter({ id: '42', type: 'standard' })],
      formatDate,
    );
    expect(flatOptions[0].value).to.equal(42);
    expect(typeof flatOptions[0].value).to.equal('number');
  });

  it('omits the sent-at tag on automation emails because sent_at lives on the queue', () => {
    const { flatOptions } = getGroupedNewsletterOptions(
      [
        makeNewsletter({
          id: '1',
          sent_at: '2025-01-15 09:30:00',
          type: 'automation',
        }),
        makeNewsletter({
          id: '2',
          sent_at: null,
          type: 'automation_transactional',
        }),
      ],
      formatDate,
    );
    expect(flatOptions[0].tag).to.equal(undefined);
    expect(flatOptions[1].tag).to.equal(undefined);
  });
});
