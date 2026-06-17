import React, { act } from 'react';
import { createRoot, Root } from 'react-dom/client';
import { JSDOM } from 'jsdom';

import { CodemirrorWrap } from '../../../../assets/js/src/form-editor/components/form-settings/codemirror-wrap.jsx';

type CodeMirrorChangeHandler = (codeMirror: { getValue: () => string }) => void;

const setBrowserGlobals = (): void => {
  const dom = new JSDOM(
    '<!doctype html><html><body><div id="root"></div></body></html>',
  );
  const globals = global as typeof globalThis & {
    IS_REACT_ACT_ENVIRONMENT: boolean;
    window: Window & typeof globalThis;
    document: Document;
    navigator: Navigator;
  };

  globals.IS_REACT_ACT_ENVIRONMENT = true;
  globals.window = dom.window as Window & typeof globalThis;
  globals.document = dom.window.document;
  Object.defineProperty(globals, 'navigator', {
    configurable: true,
    value: dom.window.navigator,
  });
};

const renderCodemirrorWrap = async (
  onChange: (value: string) => void,
): Promise<Root> => {
  const container = document.getElementById('root');
  if (!container) throw new Error('Missing test root');

  const root = createRoot(container);
  await act(async () => {
    root.render(
      React.createElement(CodemirrorWrap, {
        value: 'body { color: red; }',
        onChange,
      }),
    );
  });
  return root;
};

describe('CodemirrorWrap', () => {
  beforeEach(() => {
    setBrowserGlobals();
  });

  afterEach(() => {
    delete (global as { window?: Window }).window;
    delete (global as { document?: Document }).document;
  });

  it('initializes the WordPress code editor when it is available', async () => {
    let changeHandler: CodeMirrorChangeHandler | undefined;
    let initializedTextarea: HTMLTextAreaElement | undefined;
    let initializedOptions:
      | { codemirror?: Record<string, unknown> }
      | undefined;
    const changes: string[] = [];
    const codeMirror = {
      getValue: () => 'body { color: blue; }',
      off: () => undefined,
      on: (_event: string, handler: CodeMirrorChangeHandler) => {
        changeHandler = handler;
      },
      setValue: () => undefined,
      toTextArea: () => undefined,
    };

    (
      window as unknown as {
        wp: {
          codeEditor: {
            defaultSettings: { codemirror: Record<string, unknown> };
            initialize: (
              textarea: HTMLTextAreaElement,
              options: { codemirror?: Record<string, unknown> },
            ) => { codemirror: typeof codeMirror };
          };
        };
      }
    ).wp = {
      codeEditor: {
        defaultSettings: {
          codemirror: {
            mode: 'css',
          },
        },
        initialize: (textarea, options) => {
          initializedTextarea = textarea;
          initializedOptions = options;
          return { codemirror: codeMirror };
        },
      },
    };

    const root = await renderCodemirrorWrap((value) => changes.push(value));

    expect(initializedTextarea).to.be.instanceOf(window.HTMLTextAreaElement);
    expect(initializedTextarea?.value).to.equal('body { color: red; }');
    expect(initializedOptions?.codemirror).to.include({
      indentWithTabs: true,
      lineNumbers: true,
      matchBrackets: true,
      mode: 'css',
    });

    changeHandler?.(codeMirror);
    expect(changes).to.deep.equal(['body { color: blue; }']);

    await act(async () => root.unmount());
  });

  it('falls back to a textarea when the WordPress code editor is unavailable', async () => {
    const changes: string[] = [];

    const root = await renderCodemirrorWrap((value) => changes.push(value));
    const textarea = document.querySelector('textarea');
    if (!textarea) throw new Error('Missing fallback textarea');

    await act(async () => {
      textarea.value = 'body { color: green; }';
      textarea.dispatchEvent(new window.Event('input', { bubbles: true }));
    });

    expect(changes).to.deep.equal(['body { color: green; }']);

    await act(async () => root.unmount());
  });
});
