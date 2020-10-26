import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';

import OfferClearout from 'subscribers/importExport/import/offer_clearout';
import InitialQuestion from './step_input_validation/initial_question.jsx';
import WrongSourceBlock from './step_input_validation/wrong_source_block.jsx';
import LastSentQuestion from './step_input_validation/last_sent_question.jsx';

function StepInputValidation({ stepMethodSelectionData, history }) {
  const [importSource, setImportSource] = useState(undefined);
  const [lastSent, setLastSent] = useState(undefined);

  useEffect(
    () => {
      if (stepMethodSelectionData === undefined) {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelectionData, history],
  );

  function lastSentSubmit(when) {
    setLastSent(when);
    if (when === 'recently') {
      history.push('step_data_manipulation');
    }
  }

  return (
    <>
      {importSource === undefined && (
        <InitialQuestion
          onSubmit={setImportSource}
          history={history}
        />
      )}

      {importSource === 'address-book' && (
        <WrongSourceBlock />
      )}

      {importSource === 'existing-list' && lastSent === undefined && (
        <LastSentQuestion
          onSubmit={lastSentSubmit}
        />
      )}

      {importSource === 'existing-list' && lastSent === 'notRecently' && (
        <OfferClearout />
      )}
    </>
  );
}

StepInputValidation.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
    replace: PropTypes.func.isRequired,
  }).isRequired,
  stepMethodSelectionData: PropTypes.shape({
    duplicate: PropTypes.arrayOf(PropTypes.string),
    header: PropTypes.arrayOf(PropTypes.string),
    invalid: PropTypes.arrayOf(PropTypes.string),
    role: PropTypes.arrayOf(PropTypes.string),
    subscribersCount: PropTypes.number,
    subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)),
  }),
};

StepInputValidation.defaultProps = {
  stepMethodSelectionData: undefined,
};

export default withRouter(StepInputValidation);
