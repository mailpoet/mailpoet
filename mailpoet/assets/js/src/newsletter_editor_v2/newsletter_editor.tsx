import { render } from '@wordpress/element';
import IsolatedBlockEditor, {
  BlockEditorSettings,
  EditorSettings,
  IsoSettings,
} from '@automattic/isolated-block-editor';
import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { mediaUpload } from '@wordpress/editor';
import { useSelect } from '../wp-data-hooks';

import { registerButton } from './blocks/button';
import { registerColumns } from './blocks/columns';
import { registerColumn } from './blocks/column';

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

// Register hooks for button
registerButton();
registerColumns();
registerColumn();

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
  const canUserUpload = useSelect(
    (sel) => sel('core').canUser('create', 'media'),
    [],
  );
  const editor: EditorSettings = {
    ...SETTINGS_DEFAULTS,
    allowedMimeTypes: 'image/*',
    mediaUpload: canUserUpload ? mediaUpload : null,
  };

  const iso: IsoSettings = {
    blocks: {
      allowBlocks: [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/image',
        'core/gallery',
        'core/media-text',
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
