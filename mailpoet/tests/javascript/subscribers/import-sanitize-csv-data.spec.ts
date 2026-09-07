import { sanitizeCSVData } from '../../../assets/js/src/subscribers/import-export/import/sanitize-csv-data.jsx';

type WindowGlobals = typeof globalThis & {
  window?: Window &
    typeof globalThis & {
      roleBasedEmails: string[];
      mailpoet_email_regex: RegExp;
    };
};

describe('Subscriber import CSV sanitizing', () => {
  beforeEach(() => {
    const globals = global as WindowGlobals;
    globals.window = {
      roleBasedEmails: [],
      mailpoet_email_regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    } as WindowGlobals['window'];
  });

  it('takes off the apostrophe the export adds to values a spreadsheet would evaluate', () => {
    const result = sanitizeCSVData([
      ['Email', 'Phone'],
      ['jane@example.com', "'+420123456789"],
      ['john@example.com', "'-5"],
    ]) as { subscribers: string[][] };

    expect(result.subscribers[0][1]).to.equal('+420123456789');
    expect(result.subscribers[1][1]).to.equal('-5');
  });

  it('takes it off a column heading so it still matches its custom field', () => {
    const result = sanitizeCSVData([
      ['Email', "'- Phone"],
      ['jane@example.com', '123'],
      ['john@example.com', '456'],
    ]) as { header: string[] };

    expect(result.header[1]).to.equal('- Phone');
  });

  it('gives back one apostrophe when the value itself started with one', () => {
    const result = sanitizeCSVData([
      ['Email', 'Note'],
      ['jane@example.com', "''=x"],
      ['john@example.com', 'plain'],
    ]) as { subscribers: string[][] };

    expect(result.subscribers[0][1]).to.equal("'=x");
  });

  it('keeps an apostrophe that belongs to the value', () => {
    const result = sanitizeCSVData([
      ['Email', 'Note'],
      ['jane@example.com', "'Tis the season"],
      ['john@example.com', 'plain'],
    ]) as { subscribers: string[][] };

    expect(result.subscribers[0][1]).to.equal("'Tis the season");
  });
});
