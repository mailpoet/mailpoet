import { viewFiltersToRequestFilter } from '../../../assets/js/src/forms/filters';

describe('forms filters translation', () => {
  it('maps a status multi-select filter', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'status', operator: 'isAny', value: ['enabled', 'disabled'] },
    ]);

    expect(filter).to.deep.equal({ status: ['enabled', 'disabled'] });
  });

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

  it('maps inclusive single-bound created-date operators', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'afterInc', value: '2026-05-01' },
      ]),
    ).to.deep.equal({ created_from: '2026-05-01' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'beforeInc', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ created_to: '2026-05-18' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'on', value: '2026-05-10' },
      ]),
    ).to.deep.equal({ created_from: '2026-05-10', created_to: '2026-05-10' });
  });

  it('shifts exclusive single-bound created-date operators by a day', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'after', value: '2026-05-01' },
      ]),
    ).to.deep.equal({ created_from: '2026-05-02' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'before', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ created_to: '2026-05-17' });
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

  it('ignores invalid dates and unknown status values', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'created_at', operator: 'after', value: 'not-a-date' },
      { field: 'updated_at', operator: 'after', value: 'nope' },
      { field: 'status', operator: 'isAny', value: ['bogus'] },
    ]);

    expect(filter).to.deep.equal({});
  });

  it('returns an empty object when no filters are active', () => {
    expect(viewFiltersToRequestFilter(undefined)).to.deep.equal({});
    expect(viewFiltersToRequestFilter([])).to.deep.equal({});
  });
});
