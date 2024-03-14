import { NoticeList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { ValidationNotices } from './validation-notices';

// See: https://github.com/WordPress/gutenberg/blob/5be0ec4153c3adf9f0f2513239f4f7a358ba7948/packages/editor/src/components/editor-notices/index.js

export function EditorNotices() {
  const { notices } = useSelect(
    (select) => ({
      notices: select(noticesStore).getNotices('email-editor'),
    }),
    [],
  );

  const { removeNotice } = useDispatch(noticesStore);

  const dismissibleNotices = notices.filter(
    ({ isDismissible, type }) => isDismissible && type === 'default',
  );

  const nonDismissibleNotices = notices.filter(
    ({ isDismissible, type }) => !isDismissible && type === 'default',
  );

  return (
    <>
      <NoticeList
        notices={nonDismissibleNotices}
        className="components-editor-notices__pinned"
      />
      <NoticeList
        notices={dismissibleNotices}
        className="components-editor-notices__dismissible"
        onRemove={removeNotice}
      />
      <ValidationNotices />
    </>
  );
}
