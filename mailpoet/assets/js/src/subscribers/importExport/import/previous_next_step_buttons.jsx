import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';

function PreviousNextStepButtons({
  hidePrevious,
  isLastStep,
  canGoNext,
  onPreviousAction,
  onNextAction,
}) {
  return (
    <div className="mailpoet-settings-save">
      {!hidePrevious && (
        <Button type="button" variant="secondary" onClick={onPreviousAction}>
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
        {MailPoet.I18n.t(isLastStep ? 'import' : 'nextStep')}
      </Button>
    </div>
  );
}

PreviousNextStepButtons.propTypes = {
  canGoNext: PropTypes.bool,
  hidePrevious: PropTypes.bool,
  isLastStep: PropTypes.bool,
  onPreviousAction: PropTypes.func,
  onNextAction: PropTypes.func,
};

PreviousNextStepButtons.defaultProps = {
  hidePrevious: false,
  isLastStep: false,
  canGoNext: true,
  onPreviousAction: () => {},
  onNextAction: () => {},
};
PreviousNextStepButtons.displayName = 'PreviousNextStepButtons';
export { PreviousNextStepButtons };
