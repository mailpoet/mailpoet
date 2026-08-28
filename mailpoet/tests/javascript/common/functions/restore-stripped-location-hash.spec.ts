import { restoreStrippedLocationHash } from '../../../../assets/js/src/common/functions/restore-stripped-location-hash';

type ReplaceStateCall = {
  state: unknown;
  url: string;
};

type StubOptions = {
  hash?: string;
  navigationUrl?: string | null;
  performance?: { getEntriesByType: (type: string) => unknown } | 'missing';
};

const globals = global as unknown as {
  window?: unknown;
};

const pathname = '/wp-admin/admin.php';
const search = '?page=mailpoet-newsletters';

function setupWindow({
  hash = '',
  navigationUrl = null,
  performance,
}: StubOptions = {}): ReplaceStateCall[] {
  const replaceStateCalls: ReplaceStateCall[] = [];
  globals.window = {
    location: { hash, pathname, search },
    performance:
      performance === 'missing'
        ? undefined
        : performance ?? {
            getEntriesByType: () =>
              navigationUrl === null ? [] : [{ name: navigationUrl }],
          },
    history: {
      state: { idx: 0 },
      replaceState: (state: unknown, _title: string, url: string) => {
        replaceStateCalls.push({ state, url });
      },
    },
  };
  return replaceStateCalls;
}

describe('restoreStrippedLocationHash', () => {
  afterEach(() => {
    delete globals.window;
  });

  it('restores a stripped "#/" hash from the navigation entry', () => {
    const calls = setupWindow({
      navigationUrl: `https://example.com${pathname}${search}#/send/1`,
    });
    restoreStrippedLocationHash();
    expect(calls).to.have.lengthOf(1);
    expect(calls[0].url).to.equal(`${pathname}${search}#/send/1`);
    expect(calls[0].state).to.deep.equal({ idx: 0 });
  });

  it('does nothing when a hash is already present', () => {
    const calls = setupWindow({
      hash: '#/standard',
      navigationUrl: `https://example.com${pathname}${search}#/send/1`,
    });
    restoreStrippedLocationHash();
    expect(calls).to.have.lengthOf(0);
  });

  it('ignores fragments that are not router paths', () => {
    const calls = setupWindow({
      navigationUrl: `https://example.com${pathname}${search}#state=abc`,
    });
    restoreStrippedLocationHash();
    expect(calls).to.have.lengthOf(0);
  });

  it('does nothing when the navigation entry has no fragment', () => {
    const calls = setupWindow({
      navigationUrl: `https://example.com${pathname}${search}`,
    });
    restoreStrippedLocationHash();
    expect(calls).to.have.lengthOf(0);
  });

  it('does nothing when there is no navigation entry', () => {
    const calls = setupWindow();
    restoreStrippedLocationHash();
    expect(calls).to.have.lengthOf(0);
  });

  it('does not throw when the performance API is unavailable', () => {
    const calls = setupWindow({ performance: 'missing' });
    expect(() => restoreStrippedLocationHash()).to.not.throw();
    expect(calls).to.have.lengthOf(0);
  });

  it('does not throw when reading navigation entries fails', () => {
    const calls = setupWindow({
      performance: {
        getEntriesByType: () => {
          throw new Error('not supported');
        },
      },
    });
    expect(() => restoreStrippedLocationHash()).to.not.throw();
    expect(calls).to.have.lengthOf(0);
  });
});
