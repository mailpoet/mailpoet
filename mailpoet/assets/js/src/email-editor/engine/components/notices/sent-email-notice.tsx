import { dispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';

import { storeName } from '../../store';

export function SentEmailNotice() {
  const { isEmailSent } = useSelect(
    (select) => ({
      isEmailSent: select(storeName).isEmailSent(),
    }),
    [],
  );

  useEffect(() => {
    if (isEmailSent) {
      dispatch(noticesStore).createNotice(
        'warning',
        __(
          'This email has already been sent. It can be edited, but not sent again. Duplicate this email if you want to send it again.',
          'mailpoet',
        ),
        {
          isDismissible: false,
        },
      );
    }
  }, [isEmailSent]);

  return null;
}
