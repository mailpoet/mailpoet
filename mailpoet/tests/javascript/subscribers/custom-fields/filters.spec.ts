import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from '../../../../assets/js/src/subscribers/custom-fields/filters';

describe('custom fields filters bridge', () => {
  it('maps the type multi-select filter', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'type', operator: 'isAny', value: ['date', 'select'] },
      ]),
    ).to.deep.equal({ type: ['date', 'select'] });
  });

  it('ignores unsupported filter fields', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'used_in_forms', operator: 'is', value: 1 },
        { field: 'type', operator: 'isAny', value: ['text'] },
      ]),
    ).to.deep.equal({ type: ['text'] });
  });

  it('drops empty type selections', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'type', operator: 'isAny', value: [] },
      ]),
    ).to.deep.equal({});
  });

  it('seeds view filters from a request filter', () => {
    expect(
      requestFilterToViewFilters({
        type: ['text'],
      }),
    ).to.deep.equal([{ field: 'type', operator: 'isAny', value: ['text'] }]);
  });
});
