import { JSDOM } from 'jsdom';
import React from 'react';
import { createRoot } from 'react-dom/client';
import * as sinon from 'sinon';
import { useGuardedSend } from '../../../assets/js/src/mailpoet-email-editor-integration/send-panel/use-guarded-send';

type GuardedSend = ReturnType<typeof useGuardedSend>;

let dom: JSDOM;
let root: ReturnType<typeof createRoot>;
let clock: sinon.SinonFakeTimers;
let guarded: GuardedSend;
let sendCalls: number;
let finishSend: () => void;

function Panel({ isOpen }: { isOpen: boolean }): null {
  guarded = useGuardedSend(isOpen, async () => {
    sendCalls += 1;
    await new Promise<void>((resolve) => {
      finishSend = resolve;
    });
  });
  return null;
}

const render = async (isOpen: boolean) => {
  await React.act(async () => {
    root.render(React.createElement(Panel, { isOpen }));
  });
};

const click = async (detail = 1) => {
  await React.act(async () => {
    void guarded.send({ detail });
  });
};

const finish = async () => {
  await React.act(async () => {
    finishSend();
  });
};

describe('send panel guarded send', () => {
  before(() => {
    dom = new JSDOM('<!doctype html><html><body></body></html>');
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    global.IS_REACT_ACT_ENVIRONMENT = true;
  });

  beforeEach(async () => {
    clock = sinon.useFakeTimers({ toFake: ['performance'] });
    // Start well past 0, so a missing open time can’t pass for a fresh one.
    clock.tick(10000);
    sendCalls = 0;
    root = createRoot(document.createElement('div'));
    await render(true);
  });

  afterEach(async () => {
    await React.act(async () => {
      root.unmount();
    });
    clock.restore();
  });

  after(() => {
    dom.window.close();
    ['document', 'IS_REACT_ACT_ENVIRONMENT', 'window'].forEach((property) => {
      delete (global as unknown as Record<string, unknown>)[property];
    });
  });

  it('ignores a separate click right after the panel opens', async () => {
    clock.tick(150);
    await click();
    expect(sendCalls).to.equal(0);
  });

  it('sends on a click once half a second has passed', async () => {
    clock.tick(500);
    await click();
    expect(sendCalls).to.equal(1);
  });

  it('starts the wait again when the panel is closed and opened again', async () => {
    clock.tick(5000);
    await render(false);
    await render(true);
    clock.tick(150);
    await click();
    expect(sendCalls).to.equal(0);
  });

  it('ignores a second click while the first send is still running', async () => {
    clock.tick(500);
    await click();
    await click();
    expect(sendCalls).to.equal(1);
  });

  it('ignores a second click that arrives before the panel re-renders', async () => {
    clock.tick(500);
    await React.act(async () => {
      void guarded.send({ detail: 1 });
      void guarded.send({ detail: 1 });
    });
    expect(sendCalls).to.equal(1);
  });

  it('ignores the second click of a slow double-click', async () => {
    clock.tick(800);
    await click(2);
    expect(sendCalls).to.equal(0);
  });

  it('accepts a click from the keyboard', async () => {
    clock.tick(500);
    await click(0);
    expect(sendCalls).to.equal(1);
  });

  it('shows the send as running until it finishes', async () => {
    clock.tick(500);
    await click();
    expect(guarded.isSending).to.equal(true);
    await finish();
    expect(guarded.isSending).to.equal(false);
  });

  it('allows sending again after a send has finished', async () => {
    clock.tick(500);
    await click();
    await finish();
    await click();
    expect(sendCalls).to.equal(2);
  });
});
