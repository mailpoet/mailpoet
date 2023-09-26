import { useState } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Radio } from 'common/form/radio/radio';
import { PreviousNextStepButtons } from '../previous_next_step_buttons.jsx';

function InitialQuestion({ onSubmit, history }) {
  const [importSource, setImportSource] = useState(undefined);

  function isFormValid() {
    return importSource !== undefined;
  }

  return (
    <div className="mailpoet-settings-grid">
      <div className="mailpoet-settings-label">
        {MailPoet.I18n.t('validationStepHeading')}
      </div>
      <div className="mailpoet-settings-inputs">
        <div className="mailpoet-settings-inputs-row">
          <Radio
            automationId="mailpoet_import_validation_step_option1"
            id="existing-list"
            checked={importSource === 'existing-list'}
            value="existing-list"
            onCheck={setImportSource}
          />
          <label htmlFor="existing-list">
            {MailPoet.I18n.t('validationStepRadio1')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            automationId="mailpoet_import_validation_step_option2"
            id="address-book"
            checked={importSource === 'address-book'}
            value="address-book"
            onCheck={setImportSource}
          />
          <label htmlFor="address-book">
            {MailPoet.I18n.t('validationStepRadio2')}
          </label>
        </div>
      </div>
      <PreviousNextStepButtons
        canGoNext={isFormValid()}
        onPreviousAction={() => history.push('step_method_selection')}
        onNextAction={() => onSubmit(importSource)}
      />
    </div>
  );
}

InitialQuestion.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  onSubmit: PropTypes.func.isRequired,
};
InitialQuestion.displayName = 'InitialQuestion';
export { InitialQuestion };
