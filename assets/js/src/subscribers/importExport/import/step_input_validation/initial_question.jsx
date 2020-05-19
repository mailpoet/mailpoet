import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import PreviousNextStepButtons from '../previous_next_step_buttons.jsx';

function InitialQuestion({
  onSubmit,
  history,
}) {
  const [importSource, setImportSource] = useState(undefined);

  function isFormValid() {
    return importSource !== undefined;
  }

  return (
    <>
      <h4>{MailPoet.I18n.t('validationStepHeading')}</h4>
      <label htmlFor="existing-list">
        <input
          data-automation-id="mailpoet_import_validation_step_option1"
          type="radio"
          id="existing-list"
          checked={importSource === 'existing-list'}
          onChange={() => setImportSource('existing-list')}
        />
        {MailPoet.I18n.t('validationStepRadio1')}
      </label>
      <label htmlFor="address-book">
        <input
          data-automation-id="mailpoet_import_validation_step_option2"
          type="radio"
          id="address-book"
          checked={importSource === 'address-book'}
          onChange={() => setImportSource('address-book')}
        />
        {MailPoet.I18n.t('validationStepRadio2')}
      </label>
      <PreviousNextStepButtons
        canGoNext={isFormValid()}
        onPreviousAction={() => history.push('step_method_selection')}
        onNextAction={() => onSubmit(importSource)}
      />
    </>
  );
}

InitialQuestion.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  onSubmit: PropTypes.func.isRequired,
};

export default InitialQuestion;
