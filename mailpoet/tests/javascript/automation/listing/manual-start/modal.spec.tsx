import Module from 'module';
import React, { act, type ReactNode } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { JSDOM } from 'jsdom';
import sinon from 'sinon';
import type { AutomationItem } from '../../../../../assets/js/src/automation/listing/store/types';
import type {
  ManualStartPreview,
  ManualStartResult,
} from '../../../../../assets/js/src/automation/listing/manual-start/types';
import { AutomationStatus } from '../../../../../assets/js/src/automation/listing/automation';

type ModuleLoader = (
  request: string,
  parent: { filename?: string } | undefined,
  isMain: boolean,
) => unknown;

type SelectOption = {
  value: string;
  label: string;
};

const moduleLoadKey = '_load';
const moduleWithLoader = Module as unknown as Record<string, ModuleLoader>;
const originalLoad = moduleWithLoader[moduleLoadKey];
let previewManualStart: sinon.SinonStub;
let startManualStart: sinon.SinonStub;
let ManualStartModal: React.ComponentType<{
  automation: AutomationItem;
  onClose: () => void;
}>;

function translate(text: string): string {
  return text;
}

function plural(singular: string, pluralText: string, count: number): string {
  return count === 1 ? singular : pluralText;
}

function sprintf(template: string, ...args: unknown[]): string {
  return args.reduce<string>((message, arg, index) => {
    const value = String(arg);
    return message
      .replace(`%${index + 1}$s`, value)
      .replace(`%${index + 1}$d`, value)
      .replace('%s', value)
      .replace('%d', value);
  }, template);
}

function Button({
  children,
  isBusy,
  variant,
  ...props
}: {
  children: ReactNode;
  isBusy?: boolean;
  variant?: string;
} & React.ButtonHTMLAttributes<HTMLButtonElement>): JSX.Element {
  void isBusy;
  void variant;
  return (
    <button type="button" {...props}>
      {children}
    </button>
  );
}

function Modal({
  children,
  title,
}: {
  children: ReactNode;
  title: string;
}): JSX.Element {
  return (
    <div role="dialog" aria-label={title}>
      <h1 className="components-modal__header-heading">{title}</h1>
      {children}
    </div>
  );
}

function ReactSelect({
  inputId,
  value,
  options,
  placeholder,
  disabled,
  isDisabled,
  onChange,
  'aria-label': ariaLabel,
}: {
  inputId: string;
  value: SelectOption | null;
  options: SelectOption[];
  placeholder: string;
  disabled?: boolean;
  isDisabled?: boolean;
  onChange: (option: SelectOption | null) => void;
  'aria-label'?: string;
}): JSX.Element {
  return (
    <select
      id={inputId}
      aria-label={ariaLabel ?? inputId}
      value={value?.value ?? ''}
      disabled={disabled || isDisabled}
      onChange={(event) => {
        const nextValue = event.currentTarget.value;
        onChange(options.find((option) => option.value === nextValue) ?? null);
      }}
    >
      <option value="">{placeholder}</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );
}

function Toggle({
  checked,
  disabled,
  onCheck,
  'aria-label': ariaLabel,
}: {
  checked: boolean;
  disabled?: boolean;
  onCheck: (checked: boolean) => void;
  'aria-label': string;
}): JSX.Element {
  return (
    <input
      type="checkbox"
      aria-label={ariaLabel}
      checked={checked}
      disabled={disabled}
      onChange={(event) => onCheck(event.currentTarget.checked)}
    />
  );
}

function Tooltip({ children }: { children: ReactNode }): JSX.Element {
  return <span>{children}</span>;
}

moduleWithLoader[moduleLoadKey] = function loadModule(request, parent, isMain) {
  if (request === '@wordpress/components') {
    return { Button, Modal, Spinner: () => <span>spinner</span> };
  }
  if (request === '@wordpress/i18n') {
    return { __: translate, _n: plural, sprintf };
  }
  if (request === '@wordpress/icons') {
    return { Icon: () => null, help: {} };
  }
  if (request === 'common/form/react-select/react-select') {
    return { ReactSelect };
  }
  if (request === 'common/form/toggle/toggle') {
    return { Toggle };
  }
  if (request === 'common/tooltip/tooltip') {
    return { Tooltip };
  }
  if (
    request === './api' &&
    parent?.filename?.endsWith('manual-start/modal.tsx')
  ) {
    return {
      previewManualStart: (...args: unknown[]) =>
        previewManualStart(...args) as Promise<ManualStartPreview>,
      startManualStart: (...args: unknown[]) =>
        startManualStart(...args) as Promise<ManualStartResult>,
    };
  }
  return originalLoad(request, parent, isMain);
};

const preview = (
  overrides: Partial<ManualStartPreview> = {},
): ManualStartPreview => ({
  preview_signature: 'signature',
  automation_id: 7,
  segment_id: 1,
  filter_segment_id: null,
  selected_count: 5,
  eligible_count: 2,
  skipped_by_reason: {},
  deferred_reason_keys: [],
  duplicate_in_progress: false,
  ...overrides,
});

const result: ManualStartResult = {
  task_id: 10,
  automation_id: 7,
  segment_id: 1,
  filter_segment_id: null,
  selected_count: 5,
  eligible_count: 3,
  queued_count: 3,
  skipped_by_reason: {},
};

const automation = {
  id: 7,
  name: 'Automation',
  status: AutomationStatus.ACTIVE,
  stats: {
    totals: {
      entered: 0,
      in_progress: 0,
      exited: 0,
    },
  },
  isLegacy: false,
  manual_start: {
    supported: true,
    trigger_key: 'mailpoet:someone-subscribes',
    segment_ids: null,
  },
} as AutomationItem;

function getButton(container: HTMLElement, text: string): HTMLButtonElement {
  const button = Array.from(container.querySelectorAll('button')).find(
    (candidate) => candidate.textContent === text,
  );
  if (!button) {
    throw new Error(`Button not found: ${text}`);
  }
  return button;
}

function getSelect(
  container: HTMLElement,
  selector: string,
): HTMLSelectElement {
  const select = container.querySelector<HTMLSelectElement>(selector);
  if (!select) {
    throw new Error(`Select not found: ${selector}`);
  }
  return select;
}

function getInput(container: HTMLElement, selector: string): HTMLInputElement {
  const input = container.querySelector<HTMLInputElement>(selector);
  if (!input) {
    throw new Error(`Input not found: ${selector}`);
  }
  return input;
}

function wait(milliseconds: number): Promise<void> {
  return new Promise((resolve) => {
    setTimeout(resolve, milliseconds);
  });
}

describe('ManualStartModal', () => {
  let dom: JSDOM;
  let root: Root;
  let container: HTMLElement;

  before(async () => {
    ({ ManualStartModal } = await import(
      '../../../../../assets/js/src/automation/listing/manual-start/modal'
    ));
  });

  after(() => {
    moduleWithLoader[moduleLoadKey] = originalLoad;
  });

  beforeEach(() => {
    dom = new JSDOM(
      '<!doctype html><html><body><div id="root"></div></body></html>',
    );
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    global.HTMLElement = dom.window.HTMLElement;
    global.Event = dom.window.Event;
    global.MouseEvent = dom.window.MouseEvent;
    global.IS_REACT_ACT_ENVIRONMENT = true;
    window.mailpoet_segments = [
      { id: '1', name: 'List', subscribers: '5', type: 'default' },
    ];
    previewManualStart = sinon.stub();
    startManualStart = sinon.stub();
    const rootElement = document.getElementById('root');
    if (!rootElement) {
      throw new Error('Root element not found');
    }
    container = rootElement;
    root = createRoot(container);
  });

  afterEach(() => {
    act(() => {
      root.unmount();
    });
    dom.window.close();
  });

  it('clears stale-preview errors after refreshing preview', async () => {
    previewManualStart.onFirstCall().resolves(preview());
    previewManualStart.onSecondCall().resolves(preview({ eligible_count: 3 }));
    startManualStart.onFirstCall().rejects({
      code: 'manual_start_stale_preview',
      message: 'Refresh the preview.',
      data: {
        status: 409,
        preview: preview({
          preview_signature: 'next-signature',
          eligible_count: 3,
        }),
      },
    });
    startManualStart.onSecondCall().resolves(result);

    await act(async () => {
      root.render(
        <ManualStartModal automation={automation} onClose={() => undefined} />,
      );
    });

    const list = getSelect(container, '#mailpoet-automation-manual-start-list');
    await act(async () => {
      list.value = '1';
      list.dispatchEvent(new window.Event('change', { bubbles: true }));
      await wait(500);
    });

    const queueButton = getButton(container, 'Queue subscribers');
    expect(queueButton.disabled).to.equal(false);

    await act(async () => {
      queueButton.dispatchEvent(
        new window.MouseEvent('click', { bubbles: true }),
      );
      await wait(50);
    });
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(true);

    await act(async () => {
      expect(getButton(container, 'Refresh preview').disabled).to.equal(false);
      getButton(container, 'Refresh preview').click();
    });
    await act(async () => {
      await wait(500);
    });
    expect(previewManualStart.callCount).to.equal(2);
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(false);

    await act(async () => {
      getButton(container, 'Queue subscribers').dispatchEvent(
        new window.MouseEvent('click', { bubbles: true }),
      );
      await wait(0);
    });
    expect(container.textContent).to.contain('MailPoet queued 3 subscribers');
  });

  it('disables queueing when no subscribers are eligible', async () => {
    previewManualStart.resolves(preview({ eligible_count: 0 }));

    await act(async () => {
      root.render(
        <ManualStartModal automation={automation} onClose={() => undefined} />,
      );
    });

    const list = getSelect(container, '#mailpoet-automation-manual-start-list');
    await act(async () => {
      list.value = '1';
      list.dispatchEvent(new window.Event('change', { bubbles: true }));
      await wait(250);
    });

    expect(container.textContent).to.contain(
      'No subscribers are eligible to start this automation',
    );
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(true);
  });

  it('keeps queueing disabled while another manual start is in progress', async () => {
    previewManualStart.onFirstCall().resolves(
      preview({
        duplicate_in_progress: true,
      }),
    );
    previewManualStart.onSecondCall().resolves(preview());

    await act(async () => {
      root.render(
        <ManualStartModal automation={automation} onClose={() => undefined} />,
      );
    });

    const list = getSelect(container, '#mailpoet-automation-manual-start-list');
    await act(async () => {
      list.value = '1';
      list.dispatchEvent(new window.Event('change', { bubbles: true }));
      await wait(250);
    });

    expect(container.textContent).to.contain(
      'Subscribers are already queued for this automation',
    );
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(true);
    expect(getButton(container, 'Refresh preview').disabled).to.equal(false);

    await act(async () => {
      getButton(container, 'Refresh preview').click();
    });
    await act(async () => {
      await wait(500);
    });

    expect(previewManualStart.callCount).to.equal(2);
    expect(container.textContent).not.to.contain(
      'Subscribers are already queued for this automation',
    );
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(false);
  });

  it('requires a segment when the segment filter is enabled', async () => {
    window.mailpoet_segments = [
      { id: '1', name: 'List', subscribers: '5', type: 'default' },
      { id: '2', name: 'Engaged', subscribers: '2', type: 'dynamic' },
    ];
    previewManualStart.resolves(preview());

    await act(async () => {
      root.render(
        <ManualStartModal automation={automation} onClose={() => undefined} />,
      );
    });

    const list = getSelect(container, '#mailpoet-automation-manual-start-list');
    await act(async () => {
      list.value = '1';
      list.dispatchEvent(new window.Event('change', { bubbles: true }));
      await wait(250);
    });

    const filterToggle = getInput(
      container,
      'input[aria-label="Filter by segment"]',
    );
    await act(async () => {
      filterToggle.click();
    });

    expect(container.textContent).to.contain(
      'Choose a segment filter to preview eligible subscribers',
    );
    expect(getButton(container, 'Queue subscribers').disabled).to.equal(true);
  });
});
