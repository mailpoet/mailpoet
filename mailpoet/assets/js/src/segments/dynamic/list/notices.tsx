import { NoticeList, SnackbarList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

// See: https://github.com/WordPress/gutenberg/blob/5be0ec4153c3adf9f0f2513239f4f7a358ba7948/packages/editor/src/components/editor-notices/index.js

export function DynamicSegmentsListNotices(): JSX.Element {
  const { notices } = useSelect(
    (select) => ({
      notices: select(noticesStore).getNotices(),
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

  const snackbarNotices = notices.filter(({ type }) => type === 'snackbar');

  return (
    <>
      <NoticeList
        notices={nonDismissibleNotices}
        className="mailpoet-segments-listing-notices__notice-list"
      />
      <NoticeList
        notices={dismissibleNotices}
        className="mailpoet-segments-listing-notices__notice-list"
        onRemove={removeNotice}
      />
      <SnackbarList
        notices={snackbarNotices}
        className="mailpoet-segments-listing-notices__notice-list"
        onRemove={removeNotice}
      />
    </>
  );
}
