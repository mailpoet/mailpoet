import type { View } from '@wordpress/dataviews';
import {
  buildTagsUrl,
  parseTagsUrlState,
} from '../../../../assets/js/src/subscribers/tags/url-state';

const BASE = 'http://example.test/wp-admin/admin.php?page=mailpoet-tags';

describe('tags URL state', () => {
  it('uses defaults when no params are present', () => {
    const state = parseTagsUrlState(BASE);
    expect(state).to.deep.equal({
      page: 1,
      perPage: undefined,
      search: undefined,
      filter: {},
    });
  });

  it('parses pagination, search and filters', () => {
    const state = parseTagsUrlState(
      `${BASE}&tags_page=3&per_page=50&search=%20vip%20&created_from=2024-06-01&created_to=2024-06-30&subscribers=0,10`,
    );
    expect(state).to.deep.equal({
      page: 3,
      perPage: 50,
      search: 'vip',
      filter: {
        from: '2024-06-01',
        to: '2024-06-30',
        subscribers: ['0', '10'],
      },
    });
  });

  it('ignores invalid date params', () => {
    const state = parseTagsUrlState(`${BASE}&created_from=2024-6-1`);
    expect(state.filter).to.deep.equal({});
  });

  it('builds a URL from the view and filter', () => {
    const view: View = { type: 'table', page: 2, perPage: 25 };
    const url = buildTagsUrl(BASE, view, {
      from: '2024-06-01',
      subscribers: ['0', '10'],
    });
    expect(url).to.contain('tags_page=2');
    expect(url).to.contain('per_page=25');
    expect(url).to.contain('created_from=2024-06-01');
    expect(url).to.contain('subscribers=0%2C10');
    expect(url).to.not.contain('created_to=');
  });
});
