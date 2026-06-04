import type { Field, View } from '@wordpress/dataviews';

import {
  getDataViewsPreference,
  getDataViewsPreferenceKey,
} from '../../../../assets/js/src/common/dataviews/preferences';

type TestItem = {
  id: number;
};

const fields: Field<TestItem>[] = [
  {
    id: 'name',
    label: 'Name',
    enableSorting: true,
  },
  {
    id: 'status',
    label: 'Status',
    enableSorting: true,
  },
  {
    id: 'created_at',
    label: 'Created',
    enableSorting: true,
  },
];

const defaultView: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'name', direction: 'asc' },
  fields: ['status'],
  titleField: 'name',
  showTitle: true,
};

function setPreferences(preference?: unknown): void {
  const globals = global as typeof globalThis & {
    window?: Window & typeof globalThis;
  };
  globals.window = {
    mailpoet_preferences_data: {
      currentUserId: 1,
      preloadedData: preference
        ? {
            'core/views': {
              [getDataViewsPreferenceKey('subscribers')]: preference,
            },
          }
        : {},
    },
  } as unknown as Window & typeof globalThis;
}

describe('DataViews preferences', () => {
  afterEach(() => {
    const globals = global as typeof globalThis & {
      window?: Window & typeof globalThis;
    };
    delete globals.window;
  });

  it('generates Gutenberg-compatible DataViews preference keys', () => {
    expect(getDataViewsPreferenceKey('subscribers')).to.equal(
      'dataviews-mailpoet-subscribers-default',
    );
  });

  it('returns the default view when no preference is saved', () => {
    setPreferences();

    expect(getDataViewsPreference('subscribers', defaultView, fields)).to.equal(
      defaultView,
    );
  });

  it('loads sanitized view preferences', () => {
    setPreferences({
      type: 'table',
      perPage: 250,
      page: 9,
      search: 'saved search',
      sort: { field: 'created_at', direction: 'desc' },
      fields: ['created_at', 'missing'],
      titleField: 'name',
      descriptionField: 'missing',
      showTitle: false,
      showDescription: true,
      layout: { density: 'compact' },
    });

    expect(
      getDataViewsPreference('subscribers', defaultView, fields),
    ).to.deep.equal({
      ...defaultView,
      perPage: 100,
      sort: { field: 'created_at', direction: 'desc' },
      fields: ['created_at'],
      titleField: 'name',
      showTitle: false,
      showDescription: true,
      layout: { density: 'compact' },
    });
  });

  it('ignores invalid stale fields and sort preferences', () => {
    setPreferences({
      type: 'grid',
      perPage: 0,
      sort: { field: 'missing', direction: 'desc' },
      fields: ['missing'],
    });

    expect(
      getDataViewsPreference('subscribers', defaultView, fields),
    ).to.deep.equal(defaultView);
  });

  it('allows hiding every non-title field', () => {
    setPreferences({ fields: [] });

    expect(
      getDataViewsPreference('subscribers', defaultView, fields).fields,
    ).to.deep.equal([]);
  });
});
