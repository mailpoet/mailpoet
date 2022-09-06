import { NoticeList, SnackbarList } from '@wordpress/components';
import { StoreDescriptor, useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

// See: https://github.com/WordPress/gutenberg/blob/5be0ec4153c3adf9f0f2513239f4f7a358ba7948/packages/editor/src/components/editor-notices/index.js

export function EditorNotices(): JSX.Element {
  const { notices } = useSelect(
    (select) => ({
      notices: select(noticesStore as StoreDescriptor).getNotices(),
    }),
    [],
  );

  const { removeNotice } = useDispatch(noticesStore as StoreDescriptor);

  const dismissibleNotices = notices.filter(
    ({ isDismissible, type }) => isDismissible && type === 'default',
  );

  const nonDismissibleNotices = notices.filter(
    ({ isDismissible, type }) => !isDismissible && type === 'default',
  );

  const snackbarNotices = notices.filter(({ type }) => type === 'snackbar');

  return (
    <>
      <NoticeList
        notices={nonDismissibleNotices}
        className="mailpoet-automation-editor-notices__notice-list"
      />
      <NoticeList
        notices={dismissibleNotices}
        className="mailpoet-automation-editor-notices__notice-list"
        onRemove={removeNotice}
      />
      <SnackbarList
        notices={snackbarNotices}
        className="mailpoet-automation-editor-notices__snackbar-list"
        onRemove={removeNotice}
      />
    </>
  );
}
