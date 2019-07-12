import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const PreviousNextStepButtons = ({
  hidePrevious,
  canGoNext,
  onPreviousAction,
  onNextAction,
}) => {
  const nextStepClasses = classNames(
    'button-primary',
    'wysija',
    { 'button-disabled': !canGoNext },
  );
  return (
    <div className="mailpoet_import_step_buttons">
      {!hidePrevious && (
        <>
          <button
            className="button-primary wysija button"
            type="button"
            onClick={onPreviousAction}
          >
            {MailPoet.I18n.t('previousStep')}
          </button>
        &nbsp;&nbsp;
        </>
      )}
      <button
        type="button"
        data-automation-id="import-next-step"
        className={nextStepClasses}
        onClick={() => {
          if (canGoNext) {
            onNextAction();
          }
        }}
      >
        {MailPoet.I18n.t('nextStep')}
      </button>
    </div>
  );
};

PreviousNextStepButtons.propTypes = {
  canGoNext: PropTypes.bool,
  hidePrevious: PropTypes.bool,
  onPreviousAction: PropTypes.func,
  onNextAction: PropTypes.func,
};

PreviousNextStepButtons.defaultProps = {
  hidePrevious: false,
  canGoNext: true,
  onPreviousAction: () => {},
  onNextAction: () => {},
};

export default PreviousNextStepButtons;
