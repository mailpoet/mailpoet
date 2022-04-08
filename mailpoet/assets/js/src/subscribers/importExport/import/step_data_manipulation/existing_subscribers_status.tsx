import { useCallback } from 'react';
import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';

import { ConsentDescription } from './consent_description';

interface Props {
  existingSubscribersStatus: string;
  setExistingSubscribersStatus: (string) => void;
}

export function ExistingSubscribersStatus({
  existingSubscribersStatus,
  setExistingSubscribersStatus,
}: Props): JSX.Element {
  const handleChange = useCallback(
    (event): void => {
      setExistingSubscribersStatus(event.target.value);
    },
    [setExistingSubscribersStatus],
  );

  return (
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="existing_subscribers_status">
          {MailPoet.I18n.t('existingSubscribersStatus')}
        </label>
        <ConsentDescription />
      </div>
      <div className="mailpoet-settings-inputs">
        <Select
          id="existing_subscribers_status"
          placeholder={MailPoet.I18n.t('select')}
          name="existing_subscribers_status"
          onChange={handleChange}
          defaultValue={existingSubscribersStatus}
        >
          <option value="dont_update">{MailPoet.I18n.t('dontUpdate')}</option>
          <option value="subscribed">{MailPoet.I18n.t('subscribed')}</option>
          <option value="inactive">{MailPoet.I18n.t('inactive')}</option>
          <option value="unsubscribed">
            {MailPoet.I18n.t('unsubscribed')}
          </option>
        </Select>
      </div>
    </>
  );
}
