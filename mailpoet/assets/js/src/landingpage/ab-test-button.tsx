import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import {
  Experiment,
  Variant,
  emitter,
  experimentDebugger,
} from '@marvelapp/react-ab-test';
import { Button } from 'common';
import {
  MailPoetTrackEvent,
  CacheEventOptionSaveInStorage,
} from '../analytics_event';
import { redirectToWelcomeWizard } from './util';

const EXPERIMENT_NAME = 'landing_page_cta_display';
const VARIANT_BEGIN_SETUP = 'landing_page_cta_display_variant_begin_setup';
const VARIANT_GET_STARTED_FOR_FREE =
  'landing_page_cta_display_variant_get_started_for_free';

// analytics permission is currently unavailable at this point
// we will save the event data and send them to mixpanel later
// details in MAILPOET-4972

// Called when the experiment is displayed to the user.
emitter.addPlayListener((experimentName, variantName) => {
  MailPoetTrackEvent(
    'Experiment Display',
    {
      experiment: experimentName,
      variant: variantName,
    },
    CacheEventOptionSaveInStorage, // persist in local storage
  );
});

// Called when a 'win' is emitted
emitter.addWinListener((experimentName, variantName) => {
  MailPoetTrackEvent(
    'Experiment Win',
    {
      experiment: experimentName,
      variant: variantName,
    },
    CacheEventOptionSaveInStorage, // persist in local storage
    redirectToWelcomeWizard, // callback
  );
});

// Define variants in advance.
emitter.defineVariants(
  EXPERIMENT_NAME,
  [VARIANT_BEGIN_SETUP, VARIANT_GET_STARTED_FOR_FREE],
  [50, 50],
);

experimentDebugger.setDebuggerAvailable(
  MailPoet.FeaturesController.isSupported('landingpage_ab_test_debugger'),
);
experimentDebugger.enable();

function AbTestButton() {
  return (
    <Experiment name={EXPERIMENT_NAME}>
      <Variant name={VARIANT_BEGIN_SETUP}>
        <Button
          onClick={() => {
            emitter.emitWin(EXPERIMENT_NAME);
          }}
        >
          {__('Begin setup', 'mailpoet')}
        </Button>
      </Variant>
      <Variant name={VARIANT_GET_STARTED_FOR_FREE}>
        <Button
          onClick={() => {
            emitter.emitWin(EXPERIMENT_NAME);
          }}
        >
          {__('Get started for free', 'mailpoet')}
        </Button>
      </Variant>
    </Experiment>
  );
}
AbTestButton.displayName = 'Landingpage Ab Test';

export { AbTestButton };
