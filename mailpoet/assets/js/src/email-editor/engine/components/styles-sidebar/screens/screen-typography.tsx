import { __ } from '@wordpress/i18n';
import { __experimentalNavigatorScreen as NavigatorScreen } from '@wordpress/components';
import { TypographyElements } from '../panels/typography-elements';
import { TypographyPanel } from '../panels/typography-panel';
import { ScreenHeader } from './screen-header';

export function ScreenTypography(): JSX.Element {
  const elements = {
    text: __('Text', 'mailpoet'),
    link: __('Links', 'mailpoet'),
    heading: __('Headings', 'mailpoet'),
    button: __('Buttons', 'mailpoet'),
  };
  return (
    <>
      <NavigatorScreen path="/typography">
        <ScreenHeader
          title={__('Typography', 'mailpoet')}
          description={__(
            'Manage the typography settings for different elements',
            'mailpoet',
          )}
        />
        <TypographyElements elements={elements} />
      </NavigatorScreen>
      {Object.values(elements).map((element, key) => (
        <NavigatorScreen path={`/typography/${key}`} key={element}>
          <ScreenHeader title={element} />
          <TypographyPanel element={key} />
        </NavigatorScreen>
      ))}
    </>
  );
}
