import { SnackbarList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

// See: https://github.com/WordPress/gutenberg/blob/2788a9cf8b8149be3ee52dd15ce91fa55815f36a/packages/editor/src/components/editor-snackbars/index.js

export function EditorSnackbars() {
  const { notices } = useSelect(
    (select) => ({
      notices: select(noticesStore).getNotices('email-editor'),
    }),
    [],
  );

  const { removeNotice } = useDispatch(noticesStore);

  const snackbarNotices = notices.filter(({ type }) => type === 'snackbar');

  return (
    <SnackbarList
      notices={snackbarNotices}
      className="components-editor-notices__snackbar"
      onRemove={(id) => removeNotice(id, 'email-editor')}
    />
  );
}
