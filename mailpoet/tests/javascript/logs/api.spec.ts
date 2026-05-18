import { buildLogsRequestParams } from '../../../assets/js/src/logs/api';

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
