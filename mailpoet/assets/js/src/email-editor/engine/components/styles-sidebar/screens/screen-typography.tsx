import { __ } from '@wordpress/i18n';
import TypographyPanel from '../panels/typography-panel';
import ScreenHeader from './screen-header';

export function ScreenTypography(): JSX.Element {
  return (
    <>
      <ScreenHeader
        title={__('Typography', 'mailpoet')}
        description={__(
          'Manage the typography settings for different elements.',
          'mailpoet',
        )}
      />
      <TypographyPanel />
    </>
  );
}
