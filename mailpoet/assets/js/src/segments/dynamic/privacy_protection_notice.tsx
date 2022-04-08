import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import { EmailActionTypes, Segment } from './types';

function PrivacyProtectionNotice(): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    [],
  );

  const opensActions: string[] = [
    EmailActionTypes.OPENED,
    EmailActionTypes.OPENS_ABSOLUTE_COUNT,
    EmailActionTypes.MACHINE_OPENED,
    EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
  ];

  let containsOpensFilter = false;
  segment.filters.forEach((formItem) => {
    if (opensActions.includes(formItem.action)) {
      containsOpensFilter = true;
    }
  });

  if (!containsOpensFilter) {
    return <span />;
  }

  return (
    <div className="mailpoet-form-field">
      <span className="mailpoet-form-notice-message">
        {MailPoet.I18n.t('privacyProtectionNotice')}
      </span>
    </div>
  );
}

export { PrivacyProtectionNotice };
