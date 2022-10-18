import { StoreDescriptor, useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Notice } from '../../../../notices/notice';

export function Notices(): JSX.Element {
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

  return (
    <>
      {nonDismissibleNotices
        .reverse()
        .map(({ id, status, content, __unstableHTML }) => (
          <Notice key={id} renderInPlace type={status} timeout={false}>
            {__unstableHTML ?? <p>{content}</p>}
          </Notice>
        ))}

      {dismissibleNotices
        .reverse()
        .map(({ id, status, content, __unstableHTML }) => (
          <Notice
            key={id}
            type={status}
            renderInPlace
            timeout={false}
            closable
            onClose={removeNotice}
          >
            {__unstableHTML ?? <p>{content}</p>}
          </Notice>
        ))}
    </>
  );
}
