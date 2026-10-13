import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type DomainKeyInfoModule = {
  DomainValueInfo: React.ComponentType<{
    dnsRecord: Record<string, unknown>;
  }>;
};
type TestRequire = {
  (path: string): unknown;
  cache: Record<string, unknown>;
  resolve: (path: string) => string;
};

const domainKeyInfoPath =
  './assets/js/src/common/manage-sender-domain/domain-key-info.tsx';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let domainKeyInfoModule: DomainKeyInfoModule;
let root: ReturnType<typeof createRoot>;

describe('DomainValueInfo', function domainValueInfoSuite() {
  this.timeout(20000);

  before(() => {
    dom = new JSDOM('<!doctype html><html><body></body></html>', {
      url: 'https://example.com/wp-admin/admin.php',
    });
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    Object.defineProperty(global, 'navigator', {
      configurable: true,
      value: dom.window.navigator,
    });
    global.HTMLElement = dom.window.HTMLElement;
    global.Element = dom.window.Element;
    global.Node = dom.window.Node;
    global.IS_REACT_ACT_ENVIRONMENT = true;
    domainKeyInfoModule = testRequire(domainKeyInfoPath) as DomainKeyInfoModule;
  });

  after(() => {
    delete testRequire.cache[testRequire.resolve(domainKeyInfoPath)];
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
  });

  it('keeps the highlighted value difference and leaves out other markup', async () => {
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    root = createRoot(rootElement);
    await React.act(async () => {
      root.render(
        React.createElement(domainKeyInfoModule.DomainValueInfo, {
          dnsRecord: {
            host: 'mailpoet1._domainkey',
            value: 'v=DKIM1',
            current_value: 'v=DKIM2 <img src="x.png">',
            type: 'TXT',
            status: 'invalid',
            message: '',
            issues: {
              has_errors: true,
              needs_additions: true,
              needs_deletions: true,
              diff: 'v=DKIM<del>2</del><ins>1</ins> <img src="x.png">',
            },
          },
        }),
      );
    });

    const diff = rootElement.querySelector('strong');
    expect(diff.querySelector('del').textContent).to.equal('2');
    expect(diff.querySelector('ins').textContent).to.equal('1');
    expect(diff.querySelector('img')).to.equal(null);

    await React.act(async () => {
      root.unmount();
    });
  });
});
