import { buildLogsRequestParams } from '../../../assets/js/src/logs/api';

describe('logs API params', () => {
  it('uses a one-shot legacy offset instead of page params', () => {
    const params = buildLogsRequestParams(
      {
        page: 2,
        per_page: 20,
        orderby: 'created_at',
        order: 'desc',
        search: ' 0 ',
      },
      { from: '2026-05-11' },
      35,
    );

    expect(params.page).to.equal(undefined);
    expect(params.offset).to.equal(35);
    expect(params.limit).to.equal(20);
    expect(params.per_page).to.equal(20);
    expect(params.search).to.equal('0');
    expect(params.filter).to.deep.equal({ from: '2026-05-11' });
  });
});
