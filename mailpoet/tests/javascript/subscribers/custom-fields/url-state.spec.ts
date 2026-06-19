import type { View } from '@wordpress/dataviews';
import {
  buildCustomFieldsUrl,
  parseCustomFieldsUrlState,
} from '../../../../assets/js/src/subscribers/custom-fields/url-state';

const BASE =
  'http://example.test/wp-admin/admin.php?page=mailpoet-custom-fields';

describe('custom fields URL state', () => {
  it('uses defaults when no params are present', () => {
    const state = parseCustomFieldsUrlState(BASE);
    expect(state).to.deep.equal({
      page: 1,
      perPage: undefined,
      search: undefined,
      group: 'all',
      filter: {},
    });
  });

  it('parses pagination, group, search and filters', () => {
    const state = parseCustomFieldsUrlState(
      `${BASE}&cf_page=2&per_page=30&group=trash&search=%20color%20&type=text,date`,
    );
    expect(state).to.deep.equal({
      page: 2,
      perPage: 30,
      search: 'color',
      group: 'trash',
      filter: {
        type: ['text', 'date'],
      },
    });
  });

  it('ignores unknown type values', () => {
    const state = parseCustomFieldsUrlState(`${BASE}&type=text,bogus`);
    expect(state.filter).to.deep.equal({ type: ['text'] });
  });

  it('builds a URL from the view, group and filter', () => {
    const view: View = { type: 'table', page: 2, perPage: 25 };
    const url = buildCustomFieldsUrl(BASE, view, 'trash', {
      type: ['text', 'date'],
    });
    expect(url).to.contain('cf_page=2');
    expect(url).to.contain('per_page=25');
    expect(url).to.contain('group=trash');
    expect(url).to.contain('type=text%2Cdate');
  });
});
