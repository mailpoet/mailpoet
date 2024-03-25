import { __ } from '@wordpress/i18n';
import { DimensionsPanel } from '../panels/dimensions-panel';
import { ScreenHeader } from './screen-header';

export function ScreenTypography(): JSX.Element {
  return (
    <>
      <ScreenHeader title={__('Typography', 'mailpoet')} />
      <DimensionsPanel />
    </>
  );
}
