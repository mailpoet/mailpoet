import { PanelBody, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { store } from '../../../../../editor/store';

export function GoogleAnalyticsPanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({ selectedStep: select(store).getSelectedStep() }),
    [],
  );

  const enabled = typeof selectedStep.args?.ga_campaign !== 'undefined';
  const panelBody = Hooks.applyFilters(
    'mailpoet.automation.send_email.google_analytics_panel',
    <div>Premium required!</div>, // TODO: replace with premium upgrade modal
  );

  return (
    <PanelBody title="Google analytics" initialOpen={false}>
      <ToggleControl
        label="Enable custom GA tracking"
        checked={enabled}
        onChange={(value) =>
          dispatch(store).updateStepArgs(
            selectedStep.id,
            'ga_campaign',
            value ? '' : undefined,
          )
        }
      />

      {enabled && panelBody}
    </PanelBody>
  );
}
