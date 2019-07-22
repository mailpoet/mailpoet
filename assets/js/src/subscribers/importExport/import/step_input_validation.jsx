import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';

import InitialQuestion from './step_input_validation/initial_question.jsx';

function StepInputValidation({ stepMethodSelectionData, history }) {
  const [importSource, setImportSource] = useState(undefined);
  const [questionAnswered, setQuestionAnswered] = useState(false);

  useEffect(
    () => {
      if (stepMethodSelectionData === 'undefined') {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelectionData, history],
  );

  return (
    <div className="mailpoet_import_validation_step">
      {!questionAnswered && (
        <InitialQuestion
          importSource={importSource}
          setImportSource={setImportSource}
          history={history}
          onNextStep={() => setQuestionAnswered(true)}
        />
      )}
    </div>
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
