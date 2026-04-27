import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { Label, Inputs } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

const settingName = 'delete_unconfirmed_subscribers_after_days';
const warningId = 'delete-unconfirmed-subscribers-warning';

export function DeleteUnconfirmedSubscribers() {
  const [deleteAfterDays, setDeleteAfterDays] = useSetting(settingName);

  return (
    <>
      <Label title={t('deleteUnconfirmedSubscribersTitle')} htmlFor="" />
      <Inputs>
        <div
          role="radiogroup"
          aria-label={t('deleteUnconfirmedSubscribersTitle')}
          aria-describedby={warningId}
        >
          <p id={warningId} className="description">
            {t('deleteUnconfirmedSubscribersDescription')}
          </p>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="delete-unconfirmed-subscribers-never"
              name={settingName}
              automationId="delete-unconfirmed-subscribers-option-never"
              value=""
              checked={deleteAfterDays === ''}
              onCheck={setDeleteAfterDays}
              aria-describedby={warningId}
            />
            <label htmlFor="delete-unconfirmed-subscribers-never">
              {t('never')}
            </label>
          </div>
          <div className="mailpoet-settings-inputs-row">
            <Radio
              id="delete-unconfirmed-subscribers-after-30-days"
              name={settingName}
              automationId="delete-unconfirmed-subscribers-option-30-days"
              value="30"
              checked={deleteAfterDays === '30'}
              onCheck={setDeleteAfterDays}
              aria-describedby={warningId}
            />
            <label htmlFor="delete-unconfirmed-subscribers-after-30-days">
              {t('deleteUnconfirmedSubscribersAfter30Days')}
            </label>
          </div>
        </div>
      </Inputs>
    </>
  );
}
