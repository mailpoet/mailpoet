import { __ } from '@wordpress/i18n';
import { DimensionsPanel } from '../panels/dimensions-panel';
import { ScreenHeader } from './screen-header';

export function ScreenLayout(): JSX.Element {
  return (
    <>
      <ScreenHeader title={__('Layout', 'mailpoet')} />
      <DimensionsPanel />
    </>
  );
}
