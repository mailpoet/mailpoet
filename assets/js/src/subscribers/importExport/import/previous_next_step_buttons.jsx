import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';

const PreviousNextStepButtons = ({
  hidePrevious,
  canGoNext,
  onPreviousAction,
  onNextAction,
}) => (
  <div className="mailpoet-settings-save">
    {!hidePrevious && (
      <Button type="button" onClick={onPreviousAction}>
        {MailPoet.I18n.t('previousStep')}
      </Button>
    )}
    <Button
      type="button"
      automationId="import-next-step"
      isDisabled={!canGoNext}
      onClick={() => {
        if (canGoNext) {
          onNextAction();
        }
      }}
    >
      {MailPoet.I18n.t('nextStep')}
    </Button>
  </div>
);

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
