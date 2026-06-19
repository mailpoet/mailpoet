import { viewFiltersToRequestFilter } from '../../../../assets/js/src/segments/dynamic/filters';

describe('dynamic segments filters translation', () => {
  it('maps a between created-date filter to created_from/created_to', () => {
    const filter = viewFiltersToRequestFilter([
      {
        field: 'created_at',
        operator: 'between',
        value: ['2026-05-01', '2026-05-18'],
      },
    ]);

    expect(filter).to.deep.equal({
      created_from: '2026-05-01',
      created_to: '2026-05-18',
    });
  });

  it('maps the modified-date filter to its own updated_from/updated_to keys', () => {
    const filter = viewFiltersToRequestFilter([
      {
        field: 'updated_at',
        operator: 'between',
        value: ['2026-04-01', '2026-04-30'],
      },
    ]);

    expect(filter).to.deep.equal({
      updated_from: '2026-04-01',
      updated_to: '2026-04-30',
    });
  });

  it('keeps created and modified date ranges independent', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'created_at', operator: 'afterInc', value: '2026-05-01' },
      { field: 'updated_at', operator: 'beforeInc', value: '2026-06-01' },
    ]);

    expect(filter).to.deep.equal({
      created_from: '2026-05-01',
      updated_to: '2026-06-01',
    });
  });

  it('ignores invalid dates', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'created_at', operator: 'after', value: 'not-a-date' },
      { field: 'updated_at', operator: 'after', value: 'nope' },
    ]);

    expect(filter).to.deep.equal({});
  });

  it('returns an empty object when no filters are active', () => {
    expect(viewFiltersToRequestFilter(undefined)).to.deep.equal({});
    expect(viewFiltersToRequestFilter([])).to.deep.equal({});
  });
});
