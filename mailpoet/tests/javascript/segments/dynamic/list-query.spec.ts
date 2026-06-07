import {
  getSegmentsQuery,
  updateSegmentsQuery,
} from '../../../../assets/js/src/segments/dynamic/list/query';

describe('Dynamic segments list query', () => {
  afterEach(() => {
    const globals = global as typeof globalThis & {
      window?: Window & typeof globalThis;
    };
    delete globals.window;
  });

  it('uses provided defaults when the hash has no explicit values', () => {
    expect(
      getSegmentsQuery('', { limit: 50, sort_by: 'name', sort_order: 'asc' }),
    ).to.include({
      offset: 0,
      limit: 50,
      search: '',
      sort_by: 'name',
      sort_order: 'asc',
      group: 'all',
    });
  });

  it('prefers explicit hash values over provided defaults', () => {
    expect(
      getSegmentsQuery('/segments/limit[25]/sort_by[updated_at]', {
        limit: 50,
        sort_by: 'name',
      }),
    ).to.include({ limit: 25, sort_by: 'updated_at' });
  });

  it('omits hash entries equal to provided defaults', () => {
    const globals = global as typeof globalThis & {
      window?: Window & typeof globalThis;
    };
    globals.window = {
      location: { hash: '' },
    } as unknown as Window & typeof globalThis;

    updateSegmentsQuery(
      { limit: 50, sort_by: 'name', sort_order: 'asc', offset: 0, search: '' },
      { limit: 50, sort_by: 'name', sort_order: 'asc' },
    );

    expect(globals.window.location.hash).to.equal('/segments');
  });

  it('writes hash entries that differ from provided defaults', () => {
    const globals = global as typeof globalThis & {
      window?: Window & typeof globalThis;
    };
    globals.window = {
      location: { hash: '' },
    } as unknown as Window & typeof globalThis;

    updateSegmentsQuery(
      { limit: 25, sort_by: 'updated_at', offset: 0, search: '' },
      { limit: 50, sort_by: 'name' },
    );

    expect(globals.window.location.hash).to.equal(
      '/segments/limit[25]/sort_by[updated_at]',
    );
  });
});
