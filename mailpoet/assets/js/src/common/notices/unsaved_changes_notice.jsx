import { useEffect } from 'react';
import { useSelect } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import { withBoundary } from '../error_boundary';

function UnsavedChangesNotice({ storeName }) {
  const hasUnsavedChanges = useSelect(
    (sel) => sel(storeName).hasUnsavedChanges(),
    [],
  );

  function onUnload(event) {
    if (hasUnsavedChanges) {
      event.returnValue = MailPoet.I18n.t('changesNotSaved'); // eslint-disable-line no-param-reassign
      return event.returnValue;
    }
    return '';
  }

  useEffect(() => {
    window.addEventListener('beforeunload', onUnload);

    return () => window.removeEventListener('beforeunload', onUnload);
  });

  return null;
}

UnsavedChangesNotice.displayName = 'UnsavedChangesNotice';
const UnsavedChangesNoticeWithBoundary = withBoundary(UnsavedChangesNotice);
export { UnsavedChangesNoticeWithBoundary as UnsavedChangesNotice };
