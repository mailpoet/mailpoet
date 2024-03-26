import { __, _x } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import TypographyElementPanel from '../panels/typography-element-panel';
import ScreenHeader from './screen-header';

export function ScreenTypographyElement({
  element,
}: {
  element: string;
}): JSX.Element {
  const [headingLevel, setHeadingLevel] = useState('heading');
  const titles = {
    text: __('Text', 'mailpoet'),
    link: __('Links', 'mailpoet'),
    heading: __('Headings', 'mailpoet'),
    button: __('Buttons', 'mailpoet'),
  };
  return (
    <>
      <ScreenHeader title={titles[element]} />
      {element === 'heading' && (
        <Spacer marginX={4} marginBottom="1em">
          <ToggleGroupControl
            label={__('Select heading level')}
            hideLabelFromVision
            value={headingLevel}
            onChange={setHeadingLevel}
            isBlock
            size="__unstable-large"
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="heading"
              label={_x('All', 'heading levels')}
            />
            <ToggleGroupControlOption value="h1" label={__('H1')} />
            <ToggleGroupControlOption value="h2" label={__('H2')} />
            <ToggleGroupControlOption value="h3" label={__('H3')} />
            <ToggleGroupControlOption value="h4" label={__('H4')} />
          </ToggleGroupControl>
        </Spacer>
      )}
      <TypographyElementPanel element={element} headingLevel={headingLevel} />
    </>
  );
}
