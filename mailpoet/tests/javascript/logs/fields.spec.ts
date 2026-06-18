import {
  formatCreatedAt,
  getLogActions,
  getLogFields,
} from '../../../assets/js/src/logs/fields';
import type { LogListingItem } from '../../../assets/js/src/logs/api';

describe('logs fields', () => {
  it('does not render Invalid date for null created_at values', () => {
    expect(formatCreatedAt(null)).to.equal('—');
    expect(formatCreatedAt(null)).not.to.equal('Invalid date');
  });

  it('enables native filters and sorting on the supported columns', () => {
    const fields = getLogFields(new Set(), {
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
      operators: ['on', 'beforeInc', 'afterInc', 'between'],
    });
    expect(byId.created_at.enableSorting).to.equal(true);

    expect(byId.message.filterBy).to.equal(false);
    // The message expand toggle is a native row action, not a column.
    expect(byId.action).to.equal(undefined);
  });

  it('exposes a primary toggle action whose label tracks expanded state', () => {
    const item = { id: 7 } as LogListingItem;
    const toggled: number[] = [];

    const collapsed = getLogActions(new Set(), (id) => toggled.push(id))[0];
    expect(collapsed.isPrimary).to.equal(true);
    expect(
      typeof collapsed.label === 'function'
        ? collapsed.label([item])
        : collapsed.label,
    ).to.equal('Show more');

    const expanded = getLogActions(new Set([7]), () => {})[0];
    expect(
      typeof expanded.label === 'function'
        ? expanded.label([item])
        : expanded.label,
    ).to.equal('Show less');

    if ('callback' in collapsed) {
      collapsed.callback([item], {} as never);
    }
    expect(toggled).to.deep.equal([7]);
  });
});
