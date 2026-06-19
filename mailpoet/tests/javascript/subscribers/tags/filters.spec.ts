import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from '../../../../assets/js/src/subscribers/tags/filters';

describe('tags filters bridge', () => {
  it('maps inclusive date operators to a from/to range', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'afterInc', value: '2024-06-01' },
      ]),
    ).to.deep.equal({ from: '2024-06-01' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'beforeInc', value: '2024-06-30' },
      ]),
    ).to.deep.equal({ to: '2024-06-30' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'on', value: '2024-06-15' },
      ]),
    ).to.deep.equal({ from: '2024-06-15', to: '2024-06-15' });

    expect(
      viewFiltersToRequestFilter([
        {
          field: 'created_at',
          operator: 'between',
          value: ['2024-06-01', '2024-06-30'],
        },
      ]),
    ).to.deep.equal({ from: '2024-06-01', to: '2024-06-30' });
  });

  it('shifts the exclusive before/after operators by a day', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'after', value: '2024-06-01' },
      ]),
    ).to.deep.equal({ from: '2024-06-02' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'before', value: '2024-06-30' },
      ]),
    ).to.deep.equal({ to: '2024-06-29' });
  });

  it('ignores invalid date values', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'afterInc', value: 'nope' },
      ]),
    ).to.deep.equal({});
  });

  it('maps the subscribers bucket filter', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'subscribers', operator: 'isAny', value: ['0', '10'] },
      ]),
    ).to.deep.equal({ subscribers: ['0', '10'] });
  });

  it('drops an empty subscribers selection', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'subscribers', operator: 'isAny', value: [] },
      ]),
    ).to.deep.equal({});
  });

  it('seeds view filters from a request filter', () => {
    expect(
      requestFilterToViewFilters({
        from: '2024-06-01',
        to: '2024-06-30',
        subscribers: ['0', '10'],
      }),
    ).to.deep.equal([
      {
        field: 'created_at',
        operator: 'between',
        value: ['2024-06-01', '2024-06-30'],
      },
      { field: 'subscribers', operator: 'isAny', value: ['0', '10'] },
    ]);
  });

  it('uses the on operator when from equals to', () => {
    expect(
      requestFilterToViewFilters({ from: '2024-06-15', to: '2024-06-15' }),
    ).to.deep.equal([
      { field: 'created_at', operator: 'on', value: '2024-06-15' },
    ]);
  });

  it('seeds inclusive operators for a single stored bound', () => {
    expect(requestFilterToViewFilters({ from: '2024-06-01' })).to.deep.equal([
      { field: 'created_at', operator: 'afterInc', value: '2024-06-01' },
    ]);

    expect(requestFilterToViewFilters({ to: '2024-06-30' })).to.deep.equal([
      { field: 'created_at', operator: 'beforeInc', value: '2024-06-30' },
    ]);
  });
});
