import { memoize } from 'lodash';
import { NoticeList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '../store';

function Notices() {
  const dismissibleNotices = useSelect(
    (select) => select(store).getDismissibleNotices(),
    [],
  );
  const nonDismissibleNotices = useSelect(
    (select) => select(store).getNonDismissibleNotices(),
    [],
  );

  const { removeNotice } = useDispatch(store);
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
