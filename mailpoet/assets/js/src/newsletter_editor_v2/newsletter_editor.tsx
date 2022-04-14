import { render } from '@wordpress/element';
import IsolatedBlockEditor from '@automattic/isolated-block-editor';

import { registerBlockType } from '@wordpress/blocks';

import {
  name as todoBlockName,
  settings as todoBlockSettings,
} from './blocks/todo';

import {
  name as footerBlockName,
  settings as footerBlockSettings,
} from './blocks/footer';

import {
  name as columnName,
  settings as columnSettings,
} from './blocks/column';

import {
  name as columnsName,
  settings as columnsSettings,
} from './blocks/columns';

// Add Custom Block Type
registerBlockType(footerBlockName, footerBlockSettings);
registerBlockType(todoBlockName, todoBlockSettings);
registerBlockType(columnName, columnSettings);
registerBlockType(columnsName, columnsSettings);

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
        'mailpoet/todo-block',
        footerBlockName,
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
  const html = window.mailpoet_email_content;
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
