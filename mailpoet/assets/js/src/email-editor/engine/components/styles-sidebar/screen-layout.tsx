import { __ } from '@wordpress/i18n';
import { ScreenHeader } from './screen-header';

export function ScreenLayout(): JSX.Element {
  return <ScreenHeader title={__('Layout', 'mailpoet')} />;
}
