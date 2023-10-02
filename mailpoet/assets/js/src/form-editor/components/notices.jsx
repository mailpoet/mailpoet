import { memoize } from 'lodash';
import { NoticeList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from '../store';

function Notices() {
  const dismissibleNotices = useSelect(
    (select) => select(storeName).getDismissibleNotices(),
    [],
  );
  const nonDismissibleNotices = useSelect(
    (select) => select(storeName).getNonDismissibleNotices(),
    [],
  );

  const { removeNotice } = useDispatch(storeName);
  const timedRemove = memoize((noticeId) => {
    setTimeout(() => removeNotice(noticeId), 5000);
  });

  dismissibleNotices.forEach((notice) => timedRemove(notice.id));

  return (
    <>
      <NoticeList
        notices={nonDismissibleNotices}
        className="components-editor-notices__pinned"
      />
      <NoticeList
        notices={dismissibleNotices}
        className="components-editor-notices__dismissible automation-dismissible-notices"
        onRemove={removeNotice}
      />
    </>
  );
}

Notices.displayName = 'FormEditorNotices';

export { Notices };
