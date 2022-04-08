import MailPoet from 'mailpoet';
import { assocPath, compose, __ } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';

type Props = {
  settingsPlacementKey: string;
};

function AnimationSettings({ settingsPlacementKey }: Props): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  return (
    <SelectControl
      label={MailPoet.I18n.t('animationHeader')}
      value={formSettings.formPlacement[settingsPlacementKey].animation}
      options={[
        { label: MailPoet.I18n.t('animationNone'), value: 'none' },
        { label: 'Fade In', value: 'fadein' },
        { label: 'Slide Right', value: 'slideright' },
        { label: 'Slide Left', value: 'slideleft' },
        { label: 'Slide Up', value: 'slideup' },
        { label: 'Slide Down', value: 'slidedown' },
        { label: 'Zoom Out', value: 'zoomout' },
        { label: 'Zoom In', value: 'zoomin' },
        { label: 'Flip', value: 'flip' },
      ]}
      onChange={compose([
        changeFormSettings,
        assocPath(
          `formPlacement.${settingsPlacementKey}.animation`,
          __,
          formSettings,
        ),
      ])}
    />
  );
}

export default AnimationSettings;
