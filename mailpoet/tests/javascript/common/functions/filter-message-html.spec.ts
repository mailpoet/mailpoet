import { JSDOM } from 'jsdom';
import { filterMessageHtml } from '../../../../assets/js/src/common/functions/filter-message-html';

let dom: JSDOM;

const parse = (html: string): HTMLElement => {
  const container = document.createElement('div');
  container.innerHTML = html;
  return container;
};

describe('filterMessageHtml', () => {
  before(() => {
    dom = new JSDOM('<!doctype html><html><body></body></html>', {
      url: 'https://example.com/wp-admin/admin.php',
    });
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
  });

  after(() => {
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
  });

  it('keeps plain text unchanged', () => {
    expect(filterMessageHtml('Email "Hello" has been sent.')).to.equal(
      'Email "Hello" has been sent.',
    );
  });

  it('keeps existing entities as they are', () => {
    expect(filterMessageHtml('Hello &amp; welcome &lt;b&gt;')).to.equal(
      'Hello &amp; welcome &lt;b&gt;',
    );
  });

  it('keeps simple formatting tags', () => {
    expect(
      filterMessageHtml(
        '<p>One <strong>two</strong> <b>three</b> <em>four</em> <i>five</i> <code>six</code><br>seven</p>',
      ),
    ).to.equal(
      '<p>One <strong>two</strong> <b>three</b> <em>four</em> <i>five</i> <code>six</code><br>seven</p>',
    );
  });

  it('keeps links with their target, rel, class and data attributes', () => {
    const result = parse(
      filterMessageHtml(
        '<a class="mailpoet-js-button" data-email="a@example.com" data-type="domain" target="_blank" rel="noopener noreferrer" href="https://kb.mailpoet.com/article/258">re-install</a>',
      ),
    );
    const link = result.querySelector('a');

    expect(link.getAttribute('href')).to.equal(
      'https://kb.mailpoet.com/article/258',
    );
    expect(link.getAttribute('target')).to.equal('_blank');
    expect(link.getAttribute('rel')).to.equal('noopener noreferrer');
    expect(link.getAttribute('class')).to.equal('mailpoet-js-button');
    expect(link.getAttribute('data-email')).to.equal('a@example.com');
    expect(link.getAttribute('data-type')).to.equal('domain');
    expect(link.textContent).to.equal('re-install');
  });

  it('keeps buttons with their class, type and value', () => {
    const result = parse(
      filterMessageHtml(
        '<button type="button" class="button mailpoet-js-button-resume-sending" value="a@example.com" onclick="x()" formaction="https://example.com/">Resume sending</button>',
      ),
    );
    const button = result.querySelector('button');

    expect(button.getAttribute('class')).to.equal(
      'button mailpoet-js-button-resume-sending',
    );
    expect(button.getAttribute('type')).to.equal('button');
    expect(button.getAttribute('value')).to.equal('a@example.com');
    expect(button.hasAttribute('onclick')).to.equal(false);
    expect(button.hasAttribute('formaction')).to.equal(false);
  });

  it('keeps relative, anchor and mailto links', () => {
    const result = parse(
      filterMessageHtml(
        '<a href="admin.php?page=mailpoet-settings#/signup">a</a><a href="?page=mailpoet-settings#mta">b</a><a href="#">c</a><a href="mailto:help@example.com">d</a>',
      ),
    );

    expect(
      Array.from(result.querySelectorAll('a')).map((link) =>
        link.getAttribute('href'),
      ),
    ).to.deep.equal([
      'admin.php?page=mailpoet-settings#/signup',
      '?page=mailpoet-settings#mta',
      '#',
      'mailto:help@example.com',
    ]);
  });

  it('removes link targets with other schemes', () => {
    const result = parse(
      filterMessageHtml(
        '<a href="javascript:void(0)">a</a><a href=" JavaScript:void(0)">b</a><a href="data:text/html,x">c</a>',
      ),
    );

    expect(
      Array.from(result.querySelectorAll('a')).map((link) =>
        link.hasAttribute('href'),
      ),
    ).to.deep.equal([false, false, false]);
    expect(result.textContent).to.equal('abc');
  });

  it('removes attributes that are not allowed', () => {
    const result = parse(
      filterMessageHtml(
        '<strong onclick="x()" style="color:red">bold</strong><a href="#" onmouseover="x()">link</a>',
      ),
    );

    expect(result.querySelector('strong').attributes.length).to.equal(0);
    expect(result.querySelector('a').hasAttribute('onmouseover')).to.equal(
      false,
    );
  });

  it('drops elements that are not allowed and keeps their text', () => {
    expect(
      filterMessageHtml('Name <img src="x.png"><div><u>underlined</u></div>'),
    ).to.equal('Name underlined');
  });

  it('drops script, style and similar elements including their content', () => {
    expect(
      filterMessageHtml(
        'a<script>x()</script>b<style>p{}</style>c<iframe src="x"></iframe>d<template><b>t</b></template>e',
      ),
    ).to.equal('abcde');
  });

  it('drops svg and math elements including their content', () => {
    expect(
      filterMessageHtml(
        'a<svg><a href="#"><text>t</text></a></svg>b<math><mi>x</mi></math>c',
      ),
    ).to.equal('abc');
  });

  it('filters nested markup inside allowed elements', () => {
    expect(
      filterMessageHtml('<p><strong>a<img src="x.png">b</strong></p>'),
    ).to.equal('<p><strong>ab</strong></p>');
  });
});
