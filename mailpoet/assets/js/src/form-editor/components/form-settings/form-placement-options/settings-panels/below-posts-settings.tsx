import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { __, assocPath, compose } from 'lodash/fp';
import { SizeSettings } from 'form-editor/components/size-settings';
import { PlacementSettings } from './placement-settings';
import { storeName } from '../../../../store';

export function BelowPostsSettings(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );
  const { changeFormSettings } = useDispatch(storeName);

  const isActive = formSettings.formPlacement.belowPosts.enabled;

  return (
    <>
      <p>{MailPoet.I18n.t('placeFormBellowPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([
          changeFormSettings,
          assocPath('formPlacement.belowPosts.enabled', __, formSettings),
        ])}
      />
      {isActive && (
        <>
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.belowPosts.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width): void => {
              void changeFormSettings(
                assocPath(
                  'formPlacement.belowPosts.styles.width',
                  width,
                  formSettings,
                ),
              );
            }}
          />
          <PlacementSettings settingsPlacementKey="belowPosts" />
        </>
      )}
    </>
  );
}
