import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

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
// The code under test is loaded with require, so the test uses the same copy of the hooks.
const { applyFilters, removeAllFilters } = testRequire('@wordpress/hooks') as {
  applyFilters: (name: string, ...args: unknown[]) => unknown;
  removeAllFilters: (name: string) => void;
};

const LINK_FILTER =
  'woocommerce_email_editor_check_sending_method_configuration_link';
const HELP_ITEMS_FILTER = 'woocommerce_email_editor_send_preview_help_items';

let dom: JSDOM;
let originalModuleLoad: ModuleLoader;
let trackedEvents: string[];

describe('Send preview help', () => {
  before(() => {
    dom = new JSDOM('<!doctype html><html><body></body></html>');
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    global.IS_REACT_ACT_ENVIRONMENT = true;

    const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
    originalModuleLoad = moduleWithLoader[moduleLoadProperty];
    moduleWithLoader[moduleLoadProperty] = function loadModule(
      request: string,
      parent: unknown,
      isMain: boolean,
    ) {
      if (request === 'mailpoet') {
        return {
          MailPoet: {
            trackEvent: (name: string) => trackedEvents.push(name),
          },
        };
      }
      return originalModuleLoad.apply(this, [request, parent, isMain]);
    };

    (
      testRequire('mailpoet-email-editor-integration/send-preview-help') as {
        registerSendPreviewHelp: () => void;
      }
    ).registerSendPreviewHelp();
  });

  after(() => {
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
    removeAllFilters(LINK_FILTER);
    removeAllFilters(HELP_ITEMS_FILTER);
    dom.window.close();
  });

  beforeEach(() => {
    trackedEvents = [];
  });

  it('links the sending method check to the MailPoet SMTP article', () => {
    expect(applyFilters(LINK_FILTER, '', 'mailpoet_email')).to.equal(
      'https://www.mailpoet.com/blog/mailpoet-smtp-plugin/?utm_source=woocommerce_email_editor&utm_medium=plugin&utm_source_platform=mailpoet_email',
    );
  });

  it('keeps a link another plugin already set', () => {
    expect(
      applyFilters(LINK_FILTER, 'https://example.com/smtp', 'mailpoet_email'),
    ).to.equal('https://example.com/smtp');
  });

  it('adds the Sending Service note and tracks clicks on its link', async () => {
    const items = applyFilters(
      HELP_ITEMS_FILTER,
      ['Existing item'],
      'mailpoet_email',
    ) as React.ReactNode[];
    expect(items).to.have.length(2);
    expect(items[0]).to.equal('Existing item');

    const container = document.createElement('div');
    document.body.appendChild(container);
    const root = createRoot(container);
    await React.act(async () => {
      root.render(React.createElement(React.Fragment, null, items[1]));
    });

    expect(container.textContent).to.equal(
      'Or, sign up for MailPoet Sending Service to easily send emails. Sign up for free',
    );
    const link = container.querySelector('a');
    expect(link.getAttribute('href')).to.equal(
      'https://account.mailpoet.com/?s=1&g=1&utm_source=woocommerce_email_editor&utm_medium=plugin&utm_source_platform=mailpoet_email',
    );
    expect(link.getAttribute('target')).to.equal('_blank');

    await React.act(async () => {
      link.click();
    });
    expect(trackedEvents).to.deep.equal([
      'email_editor_events_send_preview_email_modal_sign_up_for_mailpoet_sending_service_link_clicked',
    ]);

    await React.act(async () => root.unmount());
    container.remove();
  });
});
