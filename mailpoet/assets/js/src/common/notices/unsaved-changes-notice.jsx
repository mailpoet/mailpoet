import PropTypes from 'prop-types';
import { useEffect } from 'react';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { withBoundary } from '../error_boundary';

function UnsavedChangesNotice({ storeName }) {
  const hasUnsavedChanges = useSelect(
    (sel) => sel(storeName).hasUnsavedChanges(),
    [],
  );

  function onUnload(event) {
    if (hasUnsavedChanges) {
      // eslint-disable-next-line no-param-reassign
      event.returnValue = __(
        'The changes you made may not be saved',
        'mailpoet',
      );
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

UnsavedChangesNotice.propTypes = {
  storeName: PropTypes.string.isRequired,
};

UnsavedChangesNotice.displayName = 'UnsavedChangesNotice';
const UnsavedChangesNoticeWithBoundary = withBoundary(UnsavedChangesNotice);
export { UnsavedChangesNoticeWithBoundary as UnsavedChangesNotice };
