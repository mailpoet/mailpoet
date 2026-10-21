import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import apiFetch from '@wordpress/api-fetch';
import {
  formatCreatedAt,
  getLogActions,
  getLogFieldDefinitions,
  getLogDisplayMessage,
  getLogFields,
} from '../../../assets/js/src/logs/fields';
import {
  resetLogNamesCache,
  type LogListingItem,
} from '../../../assets/js/src/logs/api';

describe('logs fields', () => {
  afterEach(() => {
    delete (global as { window?: unknown }).window;
    apiFetch.setFetchHandler((options) => Promise.resolve(options));
  });

  it('does not render Invalid date for null created_at values', () => {
    expect(formatCreatedAt(null)).to.equal('—');
    expect(formatCreatedAt(null)).not.to.equal('Invalid date');
  });

  it('enables native filters and sorting on the supported columns', () => {
    const fields = getLogFields(new Set());
    const byId = Object.fromEntries(fields.map((field) => [field.id, field]));

    expect(byId.name.filterBy).to.deep.equal({ operators: ['isAny'] });
    expect(byId.name.enableSorting).to.equal(true);
    // Names load on demand, so the field has no static elements.
    expect(byId.name.elements).to.equal(undefined);
    expect(typeof byId.name.getElements).to.equal('function');

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

  it('keeps the name title column visible but filterable', () => {
    const name = getLogFields(new Set()).find(({ id }) => id === 'name');

    expect(name.enableHiding).to.equal(false);
    expect(name.filterBy).not.to.equal(false);
  });

  it('builds the name filter elements from the loaded names', async () => {
    global.window = {
      mailpoet_logs_api: { root: 'http://localhost/wp-json', nonce: 'n' },
    } as unknown as Window & typeof globalThis;
    resetLogNamesCache();
    apiFetch.setFetchHandler(() =>
      Promise.resolve({ data: { names: ['cron', 'mailer'] } }),
    );
    const name = getLogFieldDefinitions().find(({ id }) => id === 'name');

    expect(await name.getElements()).to.deep.equal([
      { value: 'cron', label: 'Background tasks (cron)' },
      { value: 'mailer', label: 'mailer' },
    ]);
  });

  it('keeps the name getElements reference stable between calls', () => {
    const first = getLogFields(new Set()).find(({ id }) => id === 'name');
    const second = getLogFields(new Set([1])).find(({ id }) => id === 'name');

    expect(first.getElements).to.equal(second.getElements);
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

  it('renders the readable topic label with the raw name below it', () => {
    const name = getLogFieldDefinitions().find(({ id }) => id === 'name');
    const render = (value: string) =>
      renderToStaticMarkup(
        createElement(name.render, {
          item: { name: value } as LogListingItem,
          field: name,
        } as never),
      );

    const known = render('mss');
    expect(known).to.contain('MailPoet Sending Service');
    expect(known).to.contain('title="mss"');
    expect(known).to.contain(
      '<span class="mailpoet-logs-topic-raw">mss</span>',
    );

    const unknown = render('custom');
    expect(unknown).to.contain('custom');
    expect(unknown).not.to.contain('mailpoet-logs-topic-raw');
  });

  it('renders the severity as a tag with a description tooltip', () => {
    const level = getLogFields(new Set()).find((f) => f.id === 'level');
    if (!level?.render) {
      throw new Error('level field has no render');
    }
    const render = (value: number | null) =>
      renderToStaticMarkup(
        createElement(level.render, {
          item: { level: value } as LogListingItem,
          field: level,
        } as never),
      );

    const error = render(400);
    expect(error).to.contain('mailpoet-tag-critical');
    expect(error).to.contain('title="Something failed"');
    expect(error).to.contain('>Error<');
    expect(render(300)).to.contain('mailpoet-tag-average');
    expect(render(null)).to.contain('—');
    expect(render(null)).not.to.contain('mailpoet-tag');
  });
  describe('getLogDisplayMessage', () => {
    const base = {
      id: 1,
      name: 'cron',
      level: 200,
      message: 'cron: [INFO] Sent 3 emails {"count":3}',
      raw_message: 'Sent {count} emails',
      context: { count: 3 },
      created_at: null,
    } as LogListingItem;

    it('prefers the raw message and fills scalar placeholders', () => {
      expect(getLogDisplayMessage(base)).to.equal('Sent 3 emails');
    });

    it('falls back to the formatted message without a raw message', () => {
      expect(
        getLogDisplayMessage({ ...base, raw_message: null, context: {} }),
      ).to.equal(base.message);
    });

    it('falls back to the formatted message for a blank raw message', () => {
      ['', '   '].forEach((raw) => {
        expect(
          getLogDisplayMessage({ ...base, raw_message: raw, context: {} }),
        ).to.equal(base.message);
      });
    });

    it('leaves unknown and non-scalar placeholders untouched', () => {
      const item = {
        ...base,
        raw_message: 'Got {missing} and {list} and {count}',
        context: { list: [1], count: 3 },
      };
      expect(getLogDisplayMessage(item)).to.equal(
        'Got {missing} and {list} and 3',
      );
    });

    it('returns the full line when the search term only matches there', () => {
      expect(getLogDisplayMessage(base, 'INFO')).to.equal(base.message);
      expect(getLogDisplayMessage(base, '')).to.equal('Sent 3 emails');
      expect(getLogDisplayMessage(base, 'sent 3')).to.equal('Sent 3 emails');
    });

    it('keeps the plain message when the term is in the name', () => {
      expect(getLogDisplayMessage(base, 'CRON')).to.equal('Sent 3 emails');
    });
  });

  describe('message cell', () => {
    const item = {
      id: 5,
      name: 'mss',
      level: 400,
      message: 'mss: [ERROR] Failed {"code":7,"trace":{"a":1}}',
      raw_message: 'Failed',
      context: { code: 7, trace: { a: 1 }, Severity: 'custom' },
      created_at: null,
    } as LogListingItem;
    const renderMessage = (
      logItem: LogListingItem,
      expanded: boolean,
      fieldId = 'message',
    ) => {
      const field = getLogFields(
        expanded ? new Set([logItem.id]) : new Set(),
      ).find((f) => f.id === fieldId);
      return renderToStaticMarkup(
        createElement(field.render, { item: logItem, field } as never),
      );
    };

    it('shows only the plain message when collapsed', () => {
      const html = renderMessage(item, false);
      expect(html).to.contain('>Failed<');
      expect(html).not.to.contain('mailpoet-logs-message-full');
      expect(html).not.to.contain('<dl');
    });

    it('shows severity, topic, context and the full line when expanded', () => {
      const html = renderMessage(item, true);
      expect(html).to.contain('mailpoet-logs-message-full');
      expect(html).to.contain('<dl');
      expect(html).to.contain('Error – Something failed');
      expect(html).to.contain('MailPoet Sending Service (mss)');
      expect(html).to.contain('<dt>code</dt>');
      expect(html).to.contain('<dd>7</dd>');
      expect(html).to.contain('&quot;a&quot;: 1');
      expect(html).to.contain('<dt>Severity</dt>');
      expect(html).to.contain('Full log line');
      expect(html).to.contain('mss: [ERROR] Failed');
    });

    it('omits the full line when it equals the raw message or raw is null', () => {
      const same = { ...item, raw_message: item.message, context: {} };
      expect(renderMessage(same, true)).not.to.contain('Full log line');
      const none = { ...item, raw_message: null, context: {} };
      expect(renderMessage(none, true)).not.to.contain('Full log line');
    });
  });

  describe('full_message field', () => {
    it('is hideable, hidden by default and returns the formatted line', () => {
      const field = getLogFields(new Set()).find(
        (f) => f.id === 'full_message',
      );
      expect(field).not.to.equal(undefined);
      expect(field.label).to.equal('Full log line');
      expect(field.enableSorting).to.equal(false);
      expect(field.enableGlobalSearch).to.equal(false);
      expect(field.filterBy).to.equal(false);
      expect(field.enableHiding).to.equal(true);
      const item = { id: 1, message: 'a: [INFO] b' } as LogListingItem;
      expect(field.getValue({ item })).to.equal('a: [INFO] b');
      expect(
        renderToStaticMarkup(
          createElement(field.render, { item, field } as never),
        ),
      ).to.contain('a: [INFO] b');
    });
  });
});
