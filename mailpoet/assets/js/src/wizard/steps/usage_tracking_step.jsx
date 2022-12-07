import PropTypes from 'prop-types';
import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { Button } from 'common/button/button';
import { Heading } from 'common/typography/heading/heading';
import { YesNo } from 'common/form/yesno/yesno';

const isNullOrUndefined = (value) => value === null || value === undefined;

function WelcomeWizardUsageTrackingStep({ loading, submitForm }) {
  const [state, setState] = useState({
    tracking: undefined,
    libs3rdParty: undefined,
  });
  const [submitted, setSubmitted] = useState(false);

  function submit(event) {
    event.preventDefault();
    setSubmitted(true);
    if (
      isNullOrUndefined(state.libs3rdParty) ||
      isNullOrUndefined(state.tracking)
    ) {
      return false;
    }

    submitForm(state.tracking, state.libs3rdParty);
    return false;
  }

  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardUsageTrackingStepTitle')}
      </Heading>

      <div className="mailpoet-gap" />

      <form onSubmit={submit}>
        <div>
          <div className="mailpoet-wizard-woocommerce-option">
            <div className="mailpoet-wizard-woocommerce-toggle">
              <YesNo
                showError={submitted && isNullOrUndefined(state.libs3rdParty)}
                onCheck={(value) => {
                  const newState = {
                    libs3rdParty: value,
                  };
                  setState((prevState) => ({ ...prevState, ...newState }));
                }}
                checked={state.libs3rdParty}
                name="mailpoet_libs_3rdParty"
              />
            </div>
            <div>
              <p>
                {MailPoet.I18n.t(
                  'welcomeWizardUsageTrackingStepLibs3rdPartyLabel',
                )}{' '}
              </p>
              <div className="mailpoet-wizard-note">
                <span>
                  {MailPoet.I18n.t(
                    'welcomeWizardUsageTrackingStepLibs3rdPartyLabelNoteNote',
                  )}
                </span>

                {ReactStringReplace(
                  MailPoet.I18n.t(
                    'welcomeWizardUsageTrackingStepLibs3rdPartyLabelNote',
                  ),
                  /\[link\](.*?)\[\/link\]/g,
                  (match, i) => (
                    <a
                      key={i}
                      href="https://kb.mailpoet.com/article/338-what-3rd-party-libraries-we-use"
                      data-beacon-article="5f7c7dd94cedfd0017dcece8"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {match}
                    </a>
                  ),
                )}
              </div>
            </div>
          </div>

          <div className="mailpoet-gap" />

          <div className="mailpoet-wizard-woocommerce-option">
            <div className="mailpoet-wizard-woocommerce-toggle">
              <YesNo
                showError={submitted && isNullOrUndefined(state.tracking)}
                onCheck={(value) => {
                  const newState = {
                    tracking: value,
                  };
                  setState((prevState) => ({ ...prevState, ...newState }));
                }}
                checked={state.tracking}
                name="mailpoet_tracking"
              />
            </div>
            <div>
              <p>
                {MailPoet.I18n.t('welcomeWizardUsageTrackingStepTrackingLabel')}{' '}
              </p>
              <div className="mailpoet-wizard-note">
                <span>
                  {MailPoet.I18n.t(
                    'welcomeWizardUsageTrackingStepTrackingLabelNoteNote',
                  )}
                </span>

                {ReactStringReplace(
                  MailPoet.I18n.t(
                    'welcomeWizardUsageTrackingStepTrackingLabelNote',
                  ),
                  /\[link\](.*?)\[\/link\]/g,
                  (match, i) => (
                    <a
                      key={i}
                      href="https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
                      data-beacon-article="57ce0aaac6979108399a0454"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {match}
                    </a>
                  ),
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          isFullWidth
          type="submit"
          withSpinner={loading}
          disabled={loading}
        >
          {MailPoet.I18n.t('continue')}
        </Button>
      </form>
    </>
  );
}

WelcomeWizardUsageTrackingStep.propTypes = {
  loading: PropTypes.bool.isRequired,
  submitForm: PropTypes.func.isRequired,
};

export { WelcomeWizardUsageTrackingStep };
