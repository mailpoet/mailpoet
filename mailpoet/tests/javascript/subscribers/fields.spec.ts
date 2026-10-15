import NodeModule from 'module';
import {
  SUBSCRIBER_SOURCES,
  SUBSCRIBER_STATUSES,
} from '../../../assets/js/src/subscribers/filters';

type FieldsModule = typeof import('../../../assets/js/src/subscribers/fields');
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
const moduleLoadProperty = '_load';
type ModuleWithLoader = Record<string, ModuleLoader>;

// The field definitions only need the module's pure helpers, so the browser
// globals pulled in by `mailpoet` and the React components are stubbed out.
const stubs: Record<string, unknown> = {
  mailpoet: {
    MailPoet: { trackingConfig: { emailTrackingEnabled: true }, Date: {} },
  },
  common: { SegmentTags: () => null, SubscriberTags: () => null },
  'common/listings/newsletter-stats/badge': { Badge: () => null },
  'react-router-dom': { Link: () => null },
};
const stubbedModules = Object.keys(stubs);

const testRequire = (
  NodeModule as unknown as {
    createRequire: (path: string) => (id: string) => unknown;
  }
).createRequire(`${process.cwd()}/package.json`);

let originalLoad: ModuleLoader;
let fieldsModule: FieldsModule;

describe('subscribers fields', () => {
  before(() => {
    const loader = NodeModule as unknown as ModuleWithLoader;
    originalLoad = loader[moduleLoadProperty];
    loader[moduleLoadProperty] = function loadModule(request, parent, isMain) {
      if (stubbedModules.includes(request)) {
        return stubs[request];
      }
      if (request.endsWith('listings-engagement-score')) {
        return { ListingsEngagementScore: () => null };
      }
      return originalLoad.apply(this, [request, parent, isMain]);
    };
    (globalThis as { window?: unknown }).window = { mailpoet_segments: [] };
    fieldsModule = testRequire(
      './assets/js/src/subscribers/fields.tsx',
    ) as FieldsModule;
  });

  after(() => {
    delete (globalThis as { window?: unknown }).window;
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalLoad;
  });

  const options = {
    segments: [
      { value: '3', label: 'Newsletter' },
      { value: 'without-list', label: 'Subscribers without a list' },
    ],
    tags: [{ value: '7', label: 'VIP' }],
  };

  function byId(trackingEnabled: boolean, extra = {}) {
    const fields = fieldsModule.getSubscriberFields(() => '', 'all', {
      ...options,
      trackingEnabled,
      ...extra,
    });
    return Object.fromEntries(fields.map((field) => [field.id, field]));
  }

  it('keeps the email column sortable but not filterable', () => {
    const { email } = byId(true);
    expect(email.enableSorting).to.equal(true);
    expect(email.filterBy).to.equal(false);
  });

  it('offers is any / is none on status, lists, tags and source', () => {
    const fields = byId(true);
    ['status', 'segments', 'tags', 'source'].forEach((id) => {
      expect(fields[id].filterBy, id).to.deep.equal({
        operators: ['isAny', 'isNone'],
      });
    });
    expect(fields.status.enableSorting).to.equal(true);
  });

  it('builds the five status elements and prefers labels with counts', () => {
    expect(byId(true).status.elements).to.deep.equal([
      { value: 'subscribed', label: 'Subscribed' },
      { value: 'unconfirmed', label: 'Unconfirmed' },
      { value: 'unsubscribed', label: 'Unsubscribed' },
      { value: 'inactive', label: 'Inactive' },
      { value: 'bounced', label: 'Bounced' },
    ]);
    const withCounts = byId(true, {
      statusLabels: { unconfirmed: 'Unconfirmed (12)' },
    });
    expect(withCounts.status.elements?.[1]).to.deep.equal({
      value: 'unconfirmed',
      label: 'Unconfirmed (12)',
    });
  });

  it('builds list and tag elements from the options argument', () => {
    const fields = byId(true);
    expect(fields.segments.type).to.equal('array');
    expect(fields.segments.elements).to.deep.equal(options.segments);
    expect(fields.tags.type).to.equal('array');
    expect(fields.tags.elements).to.deep.equal(options.tags);
  });

  it('builds an element with a real label for every shared status and source', () => {
    const { status, source } = byId(true);
    expect(
      status.elements?.map((element) => String(element.value)),
    ).to.deep.equal(SUBSCRIBER_STATUSES);
    expect(
      source.elements?.map((element) => String(element.value)),
    ).to.deep.equal(SUBSCRIBER_SOURCES);
    [...(status.elements ?? []), ...(source.elements ?? [])].forEach(
      (element) => {
        expect(element.label, String(element.value)).to.not.equal('Invalid');
        expect(element.label).to.not.equal('');
      },
    );
    expect(
      (source.elements ?? []).filter((element) => element.label === 'Unknown'),
    ).to.have.length(1);
  });

  it('lists the nine sources including Unknown', () => {
    const { source } = byId(true);
    expect(
      source.elements?.map((element) => String(element.value)),
    ).to.deep.equal([
      'form',
      'imported',
      'administrator',
      'api',
      'wordpress_user',
      'wordpress_user_deleted',
      'woocommerce_user',
      'woocommerce_checkout',
      'unknown',
    ]);
    expect(source.elements?.[8].label).to.equal('Unknown');
  });

  it('uses date fields with on / before / after / between', () => {
    const fields = byId(true);
    ['last_subscribed_at', 'created_at'].forEach((id) => {
      expect(fields[id].type, id).to.equal('date');
      expect(fields[id].enableSorting, id).to.equal(true);
      expect(fields[id].filterBy, id).to.deep.equal({
        operators: ['on', 'before', 'after', 'between'],
      });
    });
  });

  it('registers the score field only when tracking is enabled', () => {
    const on = byId(true);
    expect(on.statistics.type).to.equal('integer');
    expect(on.statistics.enableSorting).to.equal(true);
    expect(on.statistics.filterBy).to.deep.equal({
      operators: ['greaterThan', 'lessThan', 'between'],
    });
    expect(byId(false).statistics).to.equal(undefined);
  });

  it('exposes sortable field ids without score when tracking is off', () => {
    expect(fieldsModule.getSubscriberSortableFieldIds(true)).to.deep.equal([
      'email',
      'status',
      'statistics',
      'last_subscribed_at',
      'created_at',
    ]);
    expect(fieldsModule.getSubscriberSortableFieldIds(false)).to.not.include(
      'statistics',
    );
  });
});
