import { MailPoet } from 'mailpoet';
import {
  RadioControl,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, assocPath, compose } from 'lodash/fp';
import { SizeSettings } from 'form-editor/components/size-settings';
import { AnimationSettings } from './animation-settings';
import { PlacementSettings } from './placement-settings';
import { CookieSettings } from './cookie-settings';
import { storeName } from '../../../../store';

const delayValues = [0, 2, 5, 10, 15, 30, 45, 60, 120, 180, 240];

export function PopUpSettings(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );

  const { changeFormSettings } = useDispatch(storeName);

  const isActive = formSettings.formPlacement.popup.enabled;
  const isOnClickMode =
    formSettings.formPlacement.popup.triggerMode === 'on_click';

  return (
    <>
      <p>{MailPoet.I18n.t('placePopupFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([
          changeFormSettings,
          assocPath('formPlacement.popup.enabled', __, formSettings),
        ])}
      />
      {isActive && (
        <>
          <hr />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.popup.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width): void => {
              void changeFormSettings(
                assocPath(
                  'formPlacement.popup.styles.width',
                  width,
                  formSettings,
                ),
              );
            }}
          />
          <PlacementSettings settingsPlacementKey="popup" />
          <AnimationSettings settingsPlacementKey="popup" />
          <RadioControl
            className="mailpoet-form-inline-radios__control"
            label={MailPoet.I18n.t('popupTriggerModeLabel')}
            selected={formSettings.formPlacement.popup.triggerMode}
            options={[
              {
                label: MailPoet.I18n.t('popupTriggerModeAuto'),
                value: 'auto',
              },
              {
                label: MailPoet.I18n.t('popupTriggerModeOnClick'),
                value: 'on_click',
              },
            ]}
            onChange={compose([
              changeFormSettings,
              assocPath('formPlacement.popup.triggerMode', __, formSettings),
            ])}
          />
          {isOnClickMode && (
            <TextControl
              label={MailPoet.I18n.t('popupClickTriggerSelectorLabel')}
              help={MailPoet.I18n.t('popupClickTriggerSelectorHelp')}
              value={formSettings.formPlacement.popup.clickTriggerSelector}
              onChange={compose([
                changeFormSettings,
                assocPath(
                  'formPlacement.popup.clickTriggerSelector',
                  __,
                  formSettings,
                ),
              ])}
            />
          )}
          {isOnClickMode && (
            <p>{MailPoet.I18n.t('popupOnClickDisablesOtherTriggersNotice')}</p>
          )}
          {!isOnClickMode && (
            <>
              <SelectControl
                label={MailPoet.I18n.t('formPlacementDelay')}
                value={`${formSettings.formPlacement.popup.delay}`}
                onChange={compose([
                  changeFormSettings,
                  assocPath('formPlacement.popup.delay', __, formSettings),
                ])}
                options={delayValues.map((delayValue) => ({
                  value: `${delayValue}`,
                  label: MailPoet.I18n.t('formPlacementDelaySeconds').replace(
                    '%1s',
                    `${delayValue}`,
                  ),
                }))}
              />
              <CookieSettings settingsPlacementKey="popup" />
              <div>
                <p>
                  <b>{MailPoet.I18n.t('exitIntentTitle')}</b>
                </p>
                <p>{MailPoet.I18n.t('exitIntentDescription')}</p>
                <ToggleControl
                  label={MailPoet.I18n.t('exitIntentSwitch')}
                  checked={formSettings.formPlacement.popup.exitIntentEnabled}
                  onChange={compose([
                    changeFormSettings,
                    assocPath(
                      'formPlacement.popup.exitIntentEnabled',
                      __,
                      formSettings,
                    ),
                  ])}
                />
              </div>
            </>
          )}
        </>
      )}
    </>
  );
}
