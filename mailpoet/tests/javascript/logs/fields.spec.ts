import {
  formatCreatedAt,
  getLogFields,
} from '../../../assets/js/src/logs/fields';

describe('logs fields', () => {
  it('does not render Invalid date for null created_at values', () => {
    expect(formatCreatedAt(null)).to.equal('—');
    expect(formatCreatedAt(null)).not.to.equal('Invalid date');
  });

  it('enables native filters and sorting on the supported columns', () => {
    const fields = getLogFields(new Set(), () => {}, {
      names: ['cron', 'mailer'],
    });
    const byId = Object.fromEntries(fields.map((field) => [field.id, field]));

    expect(byId.name.filterBy).to.deep.equal({ operators: ['isAny'] });
    expect(byId.name.enableSorting).to.equal(true);
    expect(byId.name.elements).to.deep.equal([
      { value: 'cron', label: 'cron' },
      { value: 'mailer', label: 'mailer' },
    ]);

    expect(byId.level.filterBy).to.deep.equal({ operators: ['isAny'] });
    // Severity offers the full Monolog enum, not only logged levels.
    expect(byId.level.elements).to.deep.equal([
      { value: 100, label: 'Debug' },
      { value: 200, label: 'Info' },
      { value: 250, label: 'Notice' },
      { value: 300, label: 'Warning' },
      { value: 400, label: 'Error' },
      { value: 500, label: 'Critical' },
      { value: 550, label: 'Alert' },
      { value: 600, label: 'Emergency' },
    ]);

    expect(byId.created_at.filterBy).to.deep.equal({
      operators: ['on', 'before', 'after', 'between'],
    });
    expect(byId.created_at.enableSorting).to.equal(true);

    expect(byId.message.filterBy).to.equal(false);
    expect(byId.action.filterBy).to.equal(false);
  });
});
