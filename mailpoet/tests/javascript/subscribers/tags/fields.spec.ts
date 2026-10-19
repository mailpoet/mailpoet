import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { buildListFields } from '../../../../assets/js/src/subscribers/tags/fields';
import type { Tag } from '../../../../assets/js/src/subscribers/tags/types';

function renderCreatedAt(createdAt: string | null): string {
  const field = buildListFields([]).find(({ id }) => id === 'created_at');
  const item = { created_at: createdAt } as Tag;
  return renderToStaticMarkup(
    createElement(field.render, { item, field } as never),
  );
}

describe('tags created column', () => {
  const globals = global as unknown as { window?: unknown };
  const originalWindow = globals.window;

  beforeEach(() => {
    globals.window = {};
  });

  afterEach(() => {
    globals.window = originalWindow;
  });

  function setOffset(offset: string): void {
    (globals.window as Window).mailpoet_date_offset = offset;
  }

  it('shows the UTC date in site time', () => {
    setOffset('2');
    expect(renderCreatedAt('2024-06-15 23:30:00')).to.equal(
      '<span>Jun 16, 2024</span>',
    );
    expect(renderCreatedAt('2024-06-15 21:59:59')).to.equal(
      '<span>Jun 15, 2024</span>',
    );
  });

  it('handles a negative site offset', () => {
    setOffset('-5');
    expect(renderCreatedAt('2024-06-15 04:59:59')).to.equal(
      '<span>Jun 14, 2024</span>',
    );
  });

  it('renders nothing without a date', () => {
    expect(renderCreatedAt(null)).to.equal('<span></span>');
  });
});

describe('tags fields', () => {
  it('does not offer hiding the name title column', () => {
    const name = buildListFields([]).find(({ id }) => id === 'name');

    expect(name.enableHiding).to.equal(false);
  });
});
