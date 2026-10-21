import apiFetch from '@wordpress/api-fetch';
import {
  buildLogsRequestParams,
  getLogNames,
  resetLogNamesCache,
} from '../../../assets/js/src/logs/api';

describe('logs API params', () => {
  it('builds DataViews request params with trimmed search and date filters', () => {
    const params = buildLogsRequestParams(
      {
        page: 2,
        per_page: 20,
        orderby: 'created_at',
        order: 'desc',
        search: ' 0 ',
      },
      { from: '2026-05-11' },
    );

    expect(params.page).to.equal(2);
    expect(params.per_page).to.equal(20);
    expect(params.search).to.equal('0');
    expect(params.filter).to.deep.equal({ from: '2026-05-11' });
  });
});

describe('logs names API', () => {
  let requests: string[];

  beforeEach(() => {
    requests = [];
    resetLogNamesCache();
    global.window = {
      mailpoet_logs_api: { root: 'http://localhost/wp-json', nonce: 'n' },
    } as unknown as Window & typeof globalThis;
  });

  afterEach(() => {
    delete (global as { window?: unknown }).window;
    apiFetch.setFetchHandler((options) => Promise.resolve(options));
  });

  it('resolves to an empty list when the request fails', async () => {
    apiFetch.setFetchHandler(() => Promise.reject(new Error('boom')));

    expect(await getLogNames()).to.deep.equal([]);
  });

  it('loads the names once and reuses the request', async () => {
    apiFetch.setFetchHandler((options) => {
      requests.push(String(options.url));
      return Promise.resolve({ data: { names: ['cron', 'mailer'] } });
    });

    const first = getLogNames();
    const second = getLogNames();

    expect(await first).to.deep.equal(['cron', 'mailer']);
    expect(await second).to.deep.equal(['cron', 'mailer']);
    expect(await getLogNames()).to.deep.equal(['cron', 'mailer']);
    expect(requests).to.have.length(1);
    expect(requests[0]).to.contain('mailpoet/v1/logs/names');
  });
});
