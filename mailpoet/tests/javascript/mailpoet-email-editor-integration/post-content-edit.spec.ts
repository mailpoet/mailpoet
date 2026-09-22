import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Renders the block itself, rather than the sanitiser alone, so that wiring the
 * post content straight into the canvas again is caught here.
 */

type EditModule = {
  Edit: React.ComponentType<{
    context: { postId?: number; postType?: string };
  }>;
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let editModule: EditModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let renderedContent = '';

const setupWindow = () => {
  dom = new JSDOM('<!doctype html><html><body></body></html>', {
    url: 'https://example.com/wp-admin/post.php',
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
};

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text, sprintf: (text: string) => text };
    }
    if (request === '@wordpress/block-editor') {
      return { useBlockProps: () => ({ className: 'preview' }) };
    }
    if (request === '@wordpress/components') {
      return { Spinner: () => React.createElement('span', null, 'loading') };
    }
    if (request === '@wordpress/core-data') {
      return { store: 'core' };
    }
    if (request === '@wordpress/data') {
      return {
        useSelect: () => ({
          record: { content: { rendered: renderedContent } },
          hasResolved: true,
        }),
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const renderBlock = async (content: string): Promise<string> => {
  renderedContent = content;
  const rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(editModule.Edit, {
        context: { postId: 1, postType: 'post' },
      }),
    );
  });
  const html = rootElement.innerHTML;
  await React.act(async () => root.unmount());
  rootElement.remove();
  return html;
};

describe('Latest Posts post content block', () => {
  before(() => {
    setupWindow();
    installModuleMocks();
    // Loaded after the mocks are in place, so the block picks them up.
    editModule = testRequire(
      './assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/post-content/edit',
    ) as EditModule;
  });

  after(() => {
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
  });

  it('does not place an event handler from the post content into the canvas', async () => {
    const html = await renderBlock(
      '<p>before</p><img src="a.png" onerror="broken()"><p>after</p>',
    );
    expect(html).to.not.contain('onerror');
    expect(html).to.contain('before');
    expect(html).to.contain('after');
  });

  it('does not place a disallowed element from the post content into the canvas', async () => {
    const html = await renderBlock(
      '<iframe src="https://x.test"></iframe><p>kept</p>',
    );
    expect(html).to.not.contain('<iframe');
    expect(html).to.contain('kept');
  });

  it('does not place a url the browser would treat as a script into the canvas', async () => {
    // eslint-disable-next-line no-script-url -- the test exists to prove this never reaches the canvas.
    const scriptUrl = 'javascript:broken()';
    const html = await renderBlock(`<a href="${scriptUrl}">text</a>`);
    expect(html).to.not.contain(scriptUrl);
    expect(html).to.not.contain('href=');
    expect(html).to.contain('text');
  });

  it('keeps the formatting the email carries', async () => {
    const html = await renderBlock(
      '<p>keep <strong>bold</strong> <a href="https://x.test">link</a></p>',
    );
    expect(html).to.contain('<strong>bold</strong>');
    expect(html).to.contain('href="https://x.test"');
  });
});
