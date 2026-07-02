import { matchColumns } from '../../../assets/js/src/subscribers/import-export/import/step-data-manipulation/match-columns.jsx';

type WindowGlobals = typeof globalThis & {
  window?: Window &
    typeof globalThis & {
      mailpoetColumns: Array<{ id: string; name: string }>;
      mailpoet_email_regex: RegExp;
    };
};

describe('Subscriber import column matching', () => {
  beforeEach(() => {
    const globals = global as WindowGlobals;
    globals.window = {
      mailpoetColumns: [
        { id: 'email', name: 'Email' },
        { id: 'first_name', name: 'First name' },
        { id: 'last_name', name: 'Last name' },
      ],
      mailpoet_email_regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    } as unknown as WindowGlobals['window'];
  });

  afterEach(() => {
    delete (global as WindowGlobals).window;
  });

  it('matches common name headers exactly', () => {
    const columns = matchColumns(
      [
        {
          0: 'first',
          1: 'last',
          2: 'customer@example.com',
        },
      ],
      {
        0: ' Given Name ',
        1: 'LAST',
        2: 'Email',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'first_name' },
      { column_id: 'last_name' },
      { column_id: 'email' },
    ]);
  });

  it('does not match unrelated headers containing name words', () => {
    const columns = matchColumns(
      [
        {
          0: 'customer@example.com',
          1: '2026-07-02',
          2: '2026-07-02',
        },
      ],
      {
        0: 'Email',
        1: 'FIRSTUPDATED',
        2: 'LASTUPDATED',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'email' },
      { column_id: 'ignore' },
      { column_id: 'ignore' },
    ]);
  });
});
