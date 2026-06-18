import {
  buildLogsUrl,
  dateFromString,
  formatDateAsYmd,
  getDateRangeError,
  isStrictDateString,
  parseLogsUrlState,
} from '../../../assets/js/src/logs/url-state';

describe('logs URL state', () => {
  const defaultFrom = '2026-05-11';

  it('uses the default date and default pagination', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs',
      defaultFrom,
    );

    expect(state).to.deep.equal({
      page: 1,
      perPage: 20,
      search: undefined,
      filters: { from: defaultFrom },
    });
  });

  it('maps legacy offset and limit to DataViews pagination', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&offset=35&limit=20',
      defaultFrom,
    );

    expect(state.page).to.equal(2);
    expect(state.perPage).to.equal(20);
  });

  it('prefers logs_page and per_page over legacy pagination', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&logs_page=4&per_page=50&offset=20&limit=10',
      defaultFrom,
    );

    expect(state.page).to.equal(4);
    expect(state.perPage).to.equal(50);
  });

  it('does not use the WordPress admin page parameter for listing pagination', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=8&per_page=30',
      defaultFrom,
    );

    expect(state.page).to.equal(1);
    expect(state.perPage).to.equal(30);
  });

  it('trims search and ignores invalid URL dates', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&search=%20error%20&from=2026-2-1&to=2026-05-18',
      defaultFrom,
    );

    expect(state.search).to.equal('error');
    expect(state.filters).to.deep.equal({
      from: defaultFrom,
      to: '2026-05-18',
    });
  });

  it('parses name and level filters from the URL', () => {
    const state = parseLogsUrlState(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&log_name=cron&log_name=mailer&log_level=300&log_level=400&from=',
      defaultFrom,
    );

    expect(state.filters).to.deep.equal({
      from: defaultFrom,
      name: ['cron', 'mailer'],
      level: [300, 400],
    });
  });

  it('builds canonical listing URL params while preserving admin routing', () => {
    const url = buildLogsUrl(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&offset=20&limit=10&foo=bar',
      {
        type: 'table',
        page: 3,
        perPage: 40,
        search: ' warning ',
      },
      { from: '2026-05-01', to: '2026-05-18' },
    );
    const searchParams = new URL(url).searchParams;

    expect(searchParams.get('page')).to.equal('mailpoet-logs');
    expect(searchParams.get('foo')).to.equal('bar');
    expect(searchParams.get('search')).to.equal('warning');
    expect(searchParams.get('from')).to.equal('2026-05-01');
    expect(searchParams.get('to')).to.equal('2026-05-18');
    expect(searchParams.get('logs_page')).to.equal('3');
    expect(searchParams.get('per_page')).to.equal('40');
    expect(searchParams.has('offset')).to.equal(false);
    expect(searchParams.has('limit')).to.equal(false);
  });

  it('serializes name and level filters as repeated query params', () => {
    const url = buildLogsUrl(
      'http://example.test/wp-admin/admin.php?page=mailpoet-logs&log_name=stale&log_level=100',
      {
        type: 'table',
        page: 1,
        perPage: 20,
      },
      { name: ['cron', 'mailer'], level: [300, 400] },
    );
    const searchParams = new URL(url).searchParams;

    expect(searchParams.getAll('log_name')).to.deep.equal(['cron', 'mailer']);
    expect(searchParams.getAll('log_level')).to.deep.equal(['300', '400']);
  });

  it('validates strict dates and date ranges', () => {
    expect(isStrictDateString('2026-05-18')).to.equal(true);
    expect(isStrictDateString('2026-5-18')).to.equal(false);
    expect(isStrictDateString('2026-02-30')).to.equal(false);
    expect(getDateRangeError({ from: '2026-05-18', to: '2026-05-17' })).to.be.a(
      'string',
    );
    expect(
      getDateRangeError({ from: '2026-05-17', to: '2026-05-18' }),
    ).to.equal(null);
  });

  it('converts strict date strings and Datepicker values without timezone drift', () => {
    const date = dateFromString('2026-05-18');

    expect(date).to.be.instanceOf(Date);
    expect(formatDateAsYmd(date)).to.equal('2026-05-18');
    expect(formatDateAsYmd(null)).to.equal(undefined);
  });
});
