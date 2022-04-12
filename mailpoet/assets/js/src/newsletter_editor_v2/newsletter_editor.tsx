import { render } from '@wordpress/element';
import IsolatedBlockEditor from '@automattic/isolated-block-editor';

const settings = {
  iso: {
    blocks: {
      allowBlocks: [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/image',
        'core/spacer',
        'core/column',
        'core/columns',
      ],
      disallowBlocks: [],
    },
    toolbar: {
      inserter: true,
      inspector: true,
      navigation: true,
      toc: true,
      documentInspector: true,
    },
    sidebar: {
      inserter: true,
      inspector: true,
    },
    moreMenu: {
      editor: true,
      fullscreen: true,
      preview: true,
      topToolbar: true,
    },
    allowApi: true,
  },
};

const saveContent = (html) => console.log(html); // eslint-disable-line no-console
const loadInitialContent = (parse) => {
  const html =
    '<!-- wp:paragraph -->\n' +
    '<p>Hello reader!</p>\n' +
    '<!-- /wp:paragraph -->';
  return parse(html);
};

render(
  <IsolatedBlockEditor
    settings={settings}
    onSaveContent={(html) => saveContent(html)}
    onLoad={loadInitialContent}
    onError={() => document.location.reload()}
  />,
  document.querySelector('#mailpoet-email-editor'),
);
