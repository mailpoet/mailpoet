import { JSDOM } from 'jsdom';
import NodeModule from 'module';

type TestRequire = ((path: string) => unknown) & {
  cache: Record<string, unknown>;
  resolve: (path: string) => string;
};

type NoticeModule = {
  MailPoetNotice: {
    error: (
      message: string | string[],
      options?: Record<string, unknown>,
    ) => void;
    showApiErrorNotice: (
      response: { errors: { error: string; message: string }[] },
      options?: Record<string, unknown>,
    ) => void;
  };
};

const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

const modulePaths = ['jquery', './assets/js/src/notice.js'];

let dom: JSDOM;
let noticeModule: NoticeModule;

const clearModuleCache = () => {
  modulePaths.forEach((path) => {
    delete testRequire.cache[testRequire.resolve(path)];
  });
};

const getNotice = (): HTMLElement =>
  document.querySelector('.mailpoet_notice:not([id])');

describe('MailPoetNotice', function noticeSuite() {
  this.timeout(20000);

  before(() => {
    dom = new JSDOM(
      '<!doctype html><html><body><div class="wrap"><h2 class="title">Heading</h2>' +
        '<div id="mailpoet_notice_error" class="mailpoet_notice" style="display:none"></div></div></body></html>',
      { url: 'https://example.com/wp-admin/admin.php' },
    );
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    // jsdom has no layout, notices with the scroll option call scrollIntoView.
    dom.window.HTMLElement.prototype.scrollIntoView = () => {};
    // jQuery binds to the window it is first loaded with, so load fresh copies for this window.
    clearModuleCache();
    noticeModule = testRequire('./assets/js/src/notice.js') as NoticeModule;
  });

  after(() => {
    clearModuleCache();
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
  });

  afterEach(() => {
    document
      .querySelectorAll('.mailpoet_notice:not([id])')
      .forEach((notice) => notice.remove());
  });

  it('shows names inside a message without turning them into elements', () => {
    noticeModule.MailPoetNotice.error(
      'List "Name <img src="x.png">" was updated.',
      { static: true },
    );

    const notice = getNotice();
    expect(notice.querySelector('img')).to.equal(null);
    expect(notice.textContent).to.contain('List "Name ');
  });

  it('keeps links and bold text in a message', () => {
    noticeModule.MailPoetNotice.error(
      'Check your <a href="?page=mailpoet-settings#mta" target="_blank">sending method</a> <strong>now</strong>.',
      { static: true },
    );

    const link = getNotice().querySelector('a');
    expect(link.getAttribute('href')).to.equal('?page=mailpoet-settings#mta');
    expect(link.getAttribute('target')).to.equal('_blank');
    expect(getNotice().querySelector('strong').textContent).to.equal('now');
  });

  it('keeps the action buttons of the sender authorization notice', () => {
    noticeModule.MailPoetNotice.error(
      '<p><b>Sending all of your emails has been paused</b> because your email address <b>a@example.com</b> hasn’t been authorized yet.</p>' +
        '<p><a target="_blank" href="https://account.mailpoet.com/authorization?email=a@example.com" class="button button-primary mailpoet-js-button-authorize-email-and-sender-domain" data-type="email" data-email="a@example.com">Authorize this email address</a> &nbsp; ' +
        '<button class="button button-secondary mailpoet-js-button-fix-this">Use a different email address</button> &nbsp; ' +
        '<button class="button button-secondary mailpoet-js-button-resume-sending" value="a@example.com">Resume sending</button></p>',
      { static: true },
    );

    const notice = getNotice();
    expect(
      notice
        .querySelector('.mailpoet-js-button-authorize-email-and-sender-domain')
        .getAttribute('data-email'),
    ).to.equal('a@example.com');
    expect(notice.querySelector('.mailpoet-js-button-fix-this')).to.not.equal(
      null,
    );
    expect(
      notice
        .querySelector('.mailpoet-js-button-resume-sending')
        .getAttribute('value'),
    ).to.equal('a@example.com');
  });

  it('removes link targets with other schemes', () => {
    noticeModule.MailPoetNotice.error('<a href="javascript:void(0)">Open</a>', {
      static: true,
    });

    expect(getNotice().querySelector('a').hasAttribute('href')).to.equal(false);
  });

  it('shows API error messages on separate lines and keeps their links', () => {
    noticeModule.MailPoetNotice.showApiErrorNotice({
      errors: [
        {
          error: 'bad_request',
          message: 'Name <img src="x.png">',
        },
        {
          error: 'reinstall_plugin',
          message:
            'Please reload. <a target="_blank" href="https://kb.mailpoet.com/article/258">Re-install</a>',
        },
      ],
    });

    const notice = getNotice();
    expect(notice.querySelector('img')).to.equal(null);
    expect(notice.querySelectorAll('br').length).to.equal(1);
    expect(notice.querySelector('a').getAttribute('href')).to.equal(
      'https://kb.mailpoet.com/article/258',
    );
  });
});
