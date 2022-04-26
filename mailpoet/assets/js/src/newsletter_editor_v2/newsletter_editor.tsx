import '@wordpress/core-data';
import { render } from '@wordpress/element';
import IsolatedBlockEditor, {
  BlockEditorSettings,
  EditorSettings,
  IsoSettings,
} from '@automattic/isolated-block-editor';
import { registerBlockType } from '@wordpress/blocks';

import { registerButton } from './blocks/button';
import { registerColumns } from './blocks/columns';
import { registerColumn } from './blocks/column';
import { registerSpacer } from './blocks/spacer';

import {
  name as todoBlockName,
  settings as todoBlockSettings,
} from './blocks/todo';

import {
  name as footerBlockName,
  settings as footerBlockSettings,
} from './blocks/footer';

import {
  name as headerBlockName,
  settings as headerBlockSettings,
} from './blocks/header';

import { getEditorSettings } from './settings';
// Register hooks for button
registerButton();
registerColumns();
registerColumn();
registerSpacer();

// Add Custom Block Type
registerBlockType(headerBlockName, headerBlockSettings);
registerBlockType(footerBlockName, footerBlockSettings);
registerBlockType(todoBlockName, todoBlockSettings);

const saveContent = (html) => console.log(html); // eslint-disable-line no-console
const loadInitialContent = (parse) => {
  const html = window.mailpoet_email_content;
  return parse(html);
};

function EmailEditor() {
  const editor: EditorSettings = getEditorSettings();
  const iso: IsoSettings = {
    blocks: {
      allowBlocks: [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/image',
        'core/spacer',
        'core/separator',
        'core/column',
        'core/social-link',
        'core/social-links',
        'core/columns',
        'mailpoet/todo-block',
        'core/button',
        'core/buttons',
        'core/blockquote',
        footerBlockName,
        headerBlockName,
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
  };

  const settings: BlockEditorSettings = {
    iso,
    editor,
  };

  return (
    <IsolatedBlockEditor
      settings={settings}
      onSaveContent={(html) => saveContent(html)}
      onLoad={loadInitialContent}
      onError={() => document.location.reload()}
    />
  );
}

render(<EmailEditor />, document.querySelector('#mailpoet-email-editor'));
