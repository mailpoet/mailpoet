import React from 'react';
import { NoticeList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

export default () => {
  const dismissibleNotices = useSelect(
    (select) => select('mailpoet-form-editor').getNotices(true),
    []
  );
  const nonDismissibleNotices = useSelect(
    (select) => select('mailpoet-form-editor').getNotices(),
    []
  );

  const { removeNotice } = useDispatch('mailpoet-form-editor');

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
    </>
  );
};
