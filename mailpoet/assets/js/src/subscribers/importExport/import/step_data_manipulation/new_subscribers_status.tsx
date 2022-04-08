import { useCallback } from 'react';
import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';

import { ConsentDescription } from './consent_description';

interface Props {
  newSubscribersStatus: string;
  setNewSubscribersStatus: (string) => void;
}

export function NewSubscribersStatus({
  newSubscribersStatus,
  setNewSubscribersStatus,
}: Props): JSX.Element {
  const handleChange = useCallback(
    (event): void => {
      setNewSubscribersStatus(event.target.value);
    },
    [setNewSubscribersStatus],
  );

  return (
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="new_subscribers_status">
          {MailPoet.I18n.t('newSubscribersStatus')}
        </label>
        <ConsentDescription />
      </div>
      <div className="mailpoet-settings-inputs">
        <Select
          id="new_subscribers_status"
          placeholder={MailPoet.I18n.t('select')}
          name="new_subscribers_status"
          onChange={handleChange}
          defaultValue={newSubscribersStatus}
        >
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
