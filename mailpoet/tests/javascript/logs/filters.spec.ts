import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from '../../../assets/js/src/logs/filters';

describe('logs filters translation', () => {
  it('maps a between date filter to from/to', () => {
    const filter = viewFiltersToRequestFilter([
      {
        field: 'created_at',
        operator: 'between',
        value: ['2026-05-01', '2026-05-18'],
      },
    ]);

    expect(filter).to.deep.equal({ from: '2026-05-01', to: '2026-05-18' });
  });

  it('maps single-bound date operators', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'after', value: '2026-05-01' },
      ]),
    ).to.deep.equal({ from: '2026-05-01' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'before', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ to: '2026-05-18' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'on', value: '2026-05-10' },
      ]),
    ).to.deep.equal({ from: '2026-05-10', to: '2026-05-10' });
  });

  it('maps name and level multi-select filters', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'name', operator: 'isAny', value: ['cron', 'mailer'] },
      { field: 'level', operator: 'isAny', value: [300, 400] },
    ]);

    expect(filter).to.deep.equal({
      name: ['cron', 'mailer'],
      level: [300, 400],
    });
  });

  it('ignores invalid date and empty multi-select values', () => {
    const filter = viewFiltersToRequestFilter([
      { field: 'created_at', operator: 'after', value: 'not-a-date' },
      { field: 'name', operator: 'isAny', value: [] },
      { field: 'level', operator: 'isAny', value: [] },
    ]);

    expect(filter).to.deep.equal({});
  });

  it('seeds a between filter from a from/to range', () => {
    expect(
      requestFilterToViewFilters({ from: '2026-05-01', to: '2026-05-18' }),
    ).to.deep.equal([
      {
        field: 'created_at',
        operator: 'between',
        value: ['2026-05-01', '2026-05-18'],
      },
    ]);
  });

  it('seeds an "on" filter when from equals to', () => {
    expect(
      requestFilterToViewFilters({ from: '2026-05-10', to: '2026-05-10' }),
    ).to.deep.equal([
      { field: 'created_at', operator: 'on', value: '2026-05-10' },
    ]);
  });

  it('seeds single-bound and multi-select filters', () => {
    expect(
      requestFilterToViewFilters({
        from: '2026-05-01',
        name: ['cron'],
        level: [400],
      }),
    ).to.deep.equal([
      { field: 'created_at', operator: 'after', value: '2026-05-01' },
      { field: 'name', operator: 'isAny', value: ['cron'] },
      { field: 'level', operator: 'isAny', value: [400] },
    ]);
  });
});
