import { t, onChange } from 'common/functions';
import Select from 'common/form/select/select';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Logging() {
  const [level, setLevel] = useSetting('logging');
  return (
    <>
      <Label
        title={t('loggingTitle')}
        description={
          <>
            {t('loggingDescription')}{' '}
            <a href="?page=mailpoet-logs" className="mailpoet-link">
              {t('loggingDescriptionLink')}
            </a>
          </>
        }
        htmlFor="logging-level"
      />
      <Inputs>
        <Select
          id="logging-level"
          value={level}
          onChange={onChange(setLevel)}
          automationId="logging-select-box"
          isMinWidth
          dimension="small"
        >
          <option value="everything" data-automation-id="log-everything">
            {t('everythingLogOption')}
          </option>
          <option value="errors" data-automation-id="log-errors">
            {t('errorsLogOption')}
          </option>
          <option value="nothing" data-automation-id="log-nothing">
            {t('nothingLogOption')}
          </option>
        </Select>
      </Inputs>
    </>
  );
}
