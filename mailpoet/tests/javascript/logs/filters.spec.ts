import {
  getLogSeverityDescription,
  getLogSeverityVariant,
  getLogTopicElements,
  getLogTopicLabel,
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

  it('maps inclusive single-bound date operators', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'afterInc', value: '2026-05-01' },
      ]),
    ).to.deep.equal({ from: '2026-05-01' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'beforeInc', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ to: '2026-05-18' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'on', value: '2026-05-10' },
      ]),
    ).to.deep.equal({ from: '2026-05-10', to: '2026-05-10' });
  });

  it('shifts exclusive single-bound date operators by a day', () => {
    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'after', value: '2026-05-01' },
      ]),
    ).to.deep.equal({ from: '2026-05-02' });

    expect(
      viewFiltersToRequestFilter([
        { field: 'created_at', operator: 'before', value: '2026-05-18' },
      ]),
    ).to.deep.equal({ to: '2026-05-17' });
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
      { field: 'created_at', operator: 'afterInc', value: '2026-05-01' },
      { field: 'name', operator: 'isAny', value: ['cron'] },
      { field: 'level', operator: 'isAny', value: [400] },
    ]);
  });

  it('returns a readable label for known topics and the name for unknown ones', () => {
    expect(getLogTopicLabel('mss')).to.equal('MailPoet Sending Service');
    expect(getLogTopicLabel('custom')).to.equal('custom');
    expect(getLogTopicLabel('__proto__')).to.equal('__proto__');
    expect(getLogTopicLabel('constructor')).to.equal('constructor');
  });

  it('keeps raw values in topic filter elements', () => {
    expect(getLogTopicElements(['mss', 'custom'])).to.deep.equal([
      { value: 'mss', label: 'MailPoet Sending Service (mss)' },
      { value: 'custom', label: 'custom' },
    ]);
  });

  it('maps severity levels to tag variants', () => {
    expect(getLogSeverityVariant(600)).to.equal('critical');
    expect(getLogSeverityVariant(400)).to.equal('critical');
    expect(getLogSeverityVariant(300)).to.equal('average');
    expect(getLogSeverityVariant(250)).to.equal('unknown');
    expect(getLogSeverityVariant(100)).to.equal('unknown');
  });

  it('describes severity levels in plain English', () => {
    expect(getLogSeverityDescription(100)).to.equal('Details for developers');
    expect(getLogSeverityDescription(300)).to.equal(
      'Something unexpected that MailPoet handled',
    );
    expect(getLogSeverityDescription(400)).to.equal('Something failed');
    expect(getLogSeverityDescription(550)).to.equal('A serious problem');
    expect(getLogSeverityDescription(999)).to.equal('');
  });
});
