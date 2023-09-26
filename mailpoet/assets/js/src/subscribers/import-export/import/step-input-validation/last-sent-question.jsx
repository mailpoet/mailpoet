import { useCallback, useState } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { Select } from 'common/form/select/select';

function LastSentQuestion({ onSubmit }) {
  const [value, setValue] = useState('over2years');

  const handleChange = useCallback(
    (event) => {
      setValue(event.target.value);
    },
    [setValue],
  );

  const handleSubmit = useCallback(() => {
    if (value === 'over2years' || value === '1to2years') {
      onSubmit('notRecently');
    } else {
      onSubmit('recently');
    }
  }, [onSubmit, value]);

  return (
    <div className="mailpoet-settings-grid">
      <div className="mailpoet-settings-label">
        {MailPoet.I18n.t('validationStepLastSentHeading')}
      </div>
      <div className="mailpoet-settings-inputs">
        <Select
          defaultValue={value}
          onChange={handleChange}
          automationId="last_sent_to_list"
        >
          <option value="over2years">
            {MailPoet.I18n.t('validationStepLastSentOption1')}
          </option>
          <option value="1to2years">
            {MailPoet.I18n.t('validationStepLastSentOption2')}
          </option>
          <option value="less1year">
            {MailPoet.I18n.t('validationStepLastSentOption3')}
          </option>
          <option value="less3months">
            {MailPoet.I18n.t('validationStepLastSentOption4')}
          </option>
        </Select>
      </div>
      <div className="mailpoet-settings-save">
        <Button
          type="button"
          automationId="last_sent_to_list_next"
          onClick={handleSubmit}
        >
          {MailPoet.I18n.t('validationStepLastSentNext')}
        </Button>
      </div>
    </div>
  );
}

LastSentQuestion.propTypes = {
  onSubmit: PropTypes.func.isRequired,
};
LastSentQuestion.displayName = 'LastSentQuestion';
export { LastSentQuestion };
