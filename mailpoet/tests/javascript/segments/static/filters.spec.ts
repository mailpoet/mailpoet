import { viewFiltersToRequestFilter } from '../../../../assets/js/src/segments/static/filters';

describe('segments (lists) filters translation', () => {
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

  it('maps inclusive and exclusive single-bound created-date operators', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'on', value: '2026-05-10' },
      ]),
    ).to.deep.equal({ created_from: '2026-05-10', created_to: '2026-05-10' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'before', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ created_to: '2026-05-17' });
  });

  it('maps a between engagement-score filter to score_min/score_max', () => {
    const filter = viewFiltersToRequestFilter([
      {
        field: 'average_engagement_score',
        operator: 'between',
        value: [10, 80],
      },
    ]);

    expect(filter).to.deep.equal({ score_min: 10, score_max: 80 });
  });

  it('maps single-bound engagement-score operators', () => {
    expect(
      viewFiltersToRequestFilter([
        {
          field: 'average_engagement_score',
          operator: 'greaterThan',
          value: 25,
        },
      ]),
    ).to.deep.equal({ score_min: 25 });

    expect(
      viewFiltersToRequestFilter([
        { field: 'average_engagement_score', operator: 'lessThan', value: 50 },
      ]),
    ).to.deep.equal({ score_max: 50 });
  });

  it('ignores non-numeric score values and invalid dates', () => {
    const filter = viewFiltersToRequestFilter([
      {
        field: 'average_engagement_score',
        operator: 'greaterThan',
        value: 'nope',
      },
      { field: 'created_at', operator: 'after', value: 'not-a-date' },
    ]);

    expect(filter).to.deep.equal({});
  });

  it('drops empty score bounds instead of coercing them to 0', () => {
    // A cleared `between` bound must not emit score_min/score_max: 0.
    expect(
      viewFiltersToRequestFilter([
        {
          field: 'average_engagement_score',
          operator: 'between',
          value: ['10', ''],
        },
      ]),
    ).to.deep.equal({ score_min: 10 });

    expect(
      viewFiltersToRequestFilter([
        {
          field: 'average_engagement_score',
          operator: 'greaterThan',
          value: '',
        },
      ]),
    ).to.deep.equal({});
  });

  it('returns an empty object when no filters are active', () => {
    expect(viewFiltersToRequestFilter(undefined)).to.deep.equal({});
    expect(viewFiltersToRequestFilter([])).to.deep.equal({});
  });
});
