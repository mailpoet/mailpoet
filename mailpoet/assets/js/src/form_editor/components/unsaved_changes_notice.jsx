import { useEffect } from 'react';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

function UnsavedChangesNotice() {
  const hasUnsavedChanges = useSelect(
    (sel) => sel('mailpoet-form-editor').hasUnsavedChanges(),
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

export default UnsavedChangesNotice;
