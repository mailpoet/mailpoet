import { __ } from '@wordpress/i18n';
import { Experiment, Variant, emitter } from '@marvelapp/react-ab-test';
import { Button } from 'common';
import { redirectToWelcomeWizard } from './util';

const EXPERIMENT_NAME = 'landing_page_cta_display';
const VARIANT_BEGIN_SETUP = 'landing_page_cta_display_variant_begin_setup';
const VARIANT_GET_STARTED_FOR_FREE =
  'landing_page_cta_display_variant_get_started_for_free';

// Define variants in advance.
emitter.defineVariants(
  EXPERIMENT_NAME,
  [VARIANT_BEGIN_SETUP, VARIANT_GET_STARTED_FOR_FREE],
  [50, 50],
);

function AbTestButton() {
  return (
    <Experiment name={EXPERIMENT_NAME}>
      <Variant name={VARIANT_BEGIN_SETUP}>
        <Button
          onClick={() => {
            emitter.emitWin(EXPERIMENT_NAME);
            redirectToWelcomeWizard();
          }}
        >
          {__('Begin setup', 'mailpoet')}
        </Button>
      </Variant>
      <Variant name={VARIANT_GET_STARTED_FOR_FREE}>
        <Button
          onClick={() => {
            emitter.emitWin(EXPERIMENT_NAME);
            redirectToWelcomeWizard();
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
