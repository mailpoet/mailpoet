import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';
import {
  RadioControl,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import { __, assocPath, compose } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import { AnimationSettings } from './animation_settings';
import { PlacementSettings } from './placement_settings';
import { CookieSettings } from './cookie_settings';

const delayValues = [0, 2, 5, 10, 15, 30, 45, 60, 120, 180, 240];

function FixedBarSettings(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const isActive = formSettings.formPlacement.fixedBar.enabled;

  return (
    <>
      <p>{MailPoet.I18n.t('placeFixedBarFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([
          changeFormSettings,
          assocPath('formPlacement.fixedBar.enabled', __, formSettings),
        ])}
      />
      {isActive && (
        <>
          <hr />
          <RadioControl
            label={MailPoet.I18n.t('formPlacementPlacementPosition')}
            selected={formSettings.formPlacement.fixedBar.position}
            options={[
              {
                label: MailPoet.I18n.t('formPlacementPlacementPositionTop'),
                value: 'top',
              },
              {
                label: MailPoet.I18n.t('formPlacementPlacementPositionBottom'),
                value: 'bottom',
              },
            ]}
            onChange={compose([
              changeFormSettings,
              assocPath('formPlacement.fixedBar.position', __, formSettings),
            ])}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.fixedBar.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width): void => {
              void changeFormSettings(
                assocPath(
                  'formPlacement.fixedBar.styles.width',
                  width,
                  formSettings,
                ),
              );
            }}
          />
          <PlacementSettings settingsPlacementKey="fixedBar" />
          <AnimationSettings settingsPlacementKey="fixedBar" />
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={`${formSettings.formPlacement.fixedBar.delay}`}
            onChange={compose([
              changeFormSettings,
              assocPath('formPlacement.fixedBar.delay', __, formSettings),
            ])}
            options={delayValues.map((delayValue) => ({
              value: `${delayValue}`,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace(
                '%1s',
                `${delayValue}`,
              ),
            }))}
          />
          <CookieSettings settingsPlacementKey="fixedBar" />
        </>
      )}
    </>
  );
}

FixedBarSettings.displayName = 'FormEditorFixedBarSettings';
export { FixedBarSettings };
