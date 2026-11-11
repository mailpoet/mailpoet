import NodeModule from 'module';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

type HeaderModule = { Header: React.ComponentType };
type FooterModule = { Footer: React.ComponentType };
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

let headerModule: HeaderModule;
let footerModule: FooterModule;
let originalModuleLoad: ModuleLoader;

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '../common') {
      return {
        Button: ({ children }: { children: React.ReactNode }) =>
          React.createElement('button', { type: 'button' }, children),
      };
    }
    if (request === 'common/typography/heading/heading') {
      return {
        Heading: ({ children }: { children: React.ReactNode }) =>
          React.createElement('h2', null, children),
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const adminNote =
  'MailPoet isn’t set up yet, and only a site administrator can do the setup. Please ask one to finish it.';

describe('landing page setup call to action', () => {
  before(() => {
    installModuleMocks();
    try {
      headerModule = testRequire(
        './assets/js/src/landingpage/header.tsx',
      ) as HeaderModule;
      footerModule = testRequire(
        './assets/js/src/landingpage/footer.tsx',
      ) as FooterModule;
    } finally {
      restoreModuleMocks();
    }
  });

  const renderHeader = () =>
    renderToStaticMarkup(React.createElement(headerModule.Header));
  const renderFooter = () =>
    renderToStaticMarkup(React.createElement(footerModule.Footer));

  const globalWithWindow = global as unknown as { window?: unknown };
  let originalWindow: unknown;

  beforeEach(() => {
    originalWindow = globalWithWindow.window;
  });

  afterEach(() => {
    globalWithWindow.window = originalWindow;
  });

  describe('for a user who can manage settings', () => {
    beforeEach(() => {
      globalWithWindow.window = {
        mailpoet_can_manage_settings: true,
        mailpoet_welcome_wizard_current_step: '',
      };
    });

    it('shows the setup button in the header and the footer', () => {
      const header = renderHeader();
      expect(header).to.include('<button type="button">Begin setup</button>');
      expect(header).not.to.include(adminNote);
      const footer = renderFooter();
      expect(footer).to.include('Ready to start using MailPoet?');
      expect(footer).to.include('<button type="button">Begin setup</button>');
    });

    it('offers to continue a started setup', () => {
      globalWithWindow.window = {
        mailpoet_can_manage_settings: true,
        mailpoet_welcome_wizard_current_step: '/steps/2',
      };
      expect(renderHeader()).to.include(
        '<button type="button">Continue setup</button>',
      );
    });
  });

  describe('for a user who cannot manage settings', () => {
    beforeEach(() => {
      globalWithWindow.window = {
        mailpoet_can_manage_settings: false,
        mailpoet_welcome_wizard_current_step: '/steps/2',
      };
    });

    it('asks for an administrator instead of showing the setup button', () => {
      const header = renderHeader();
      expect(header).to.include(adminNote);
      expect(header).not.to.include('<button');
    });

    it('does not render the footer', () => {
      expect(renderFooter()).to.equal('');
    });
  });
});
