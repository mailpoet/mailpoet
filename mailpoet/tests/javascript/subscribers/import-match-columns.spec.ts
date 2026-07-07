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

  it('matches surname variants for the last name column', () => {
    const columns = matchColumns(
      [
        {
          0: 'John',
          1: 'Doe',
        },
      ],
      {
        0: 'Given name',
        1: 'Surname',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'first_name' },
      { column_id: 'last_name' },
    ]);

    const familyName = matchColumns(
      [
        {
          0: 'Doe',
        },
      ],
      {
        0: 'Family name',
      },
    );

    expect(familyName).to.deep.equal([{ column_id: 'last_name' }]);
  });

  it('matches single-word name headers without a space', () => {
    const columns = matchColumns(
      [
        {
          0: 'John',
          1: 'Doe',
        },
      ],
      {
        0: 'FirstName',
        1: 'lastname',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'first_name' },
      { column_id: 'last_name' },
    ]);
  });

  it('matches name headers regardless of the word separator', () => {
    const separatorVariants = [
      ['first_name', 'last_name'],
      ['first-name', 'last-name'],
      ['first.name', 'last.name'],
      ['First Name', 'Last Name'],
    ];

    separatorVariants.forEach(([firstHeader, lastHeader]) => {
      const columns = matchColumns(
        [
          {
            0: 'John',
            1: 'Doe',
          },
        ],
        {
          0: firstHeader,
          1: lastHeader,
        },
      );

      expect(columns).to.deep.equal([
        { column_id: 'first_name' },
        { column_id: 'last_name' },
      ]);
    });
  });

  it('matches abbreviated fname and lname headers', () => {
    const columns = matchColumns(
      [
        {
          0: 'John',
          1: 'Doe',
        },
      ],
      {
        0: 'fname',
        1: 'lname',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'first_name' },
      { column_id: 'last_name' },
    ]);
  });

  it('does not match unrelated headers containing name words', () => {
    const columns = matchColumns(
      [
        {
          0: 'customer@example.com',
          1: '2026-07-02',
          2: '2026-07-02',
          3: '2026-07-02',
          4: '5',
        },
      ],
      {
        0: 'Email',
        1: 'FIRSTUPDATED',
        2: 'LASTUPDATED',
        3: 'first_seen',
        4: 'last_order',
      },
    );

    expect(columns).to.deep.equal([
      { column_id: 'email' },
      { column_id: 'ignore' },
      { column_id: 'ignore' },
      { column_id: 'ignore' },
      { column_id: 'ignore' },
    ]);
  });
});
