import { __, _x } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import TypographyElementPanel, {
  DEFAULT_CONTROLS,
} from '../panels/typography-element-panel';
import TypographyPreview from '../previews/typography-preview';
import ScreenHeader from './screen-header';

export function ScreenTypographyElement({
  element,
}: {
  element: string;
}): JSX.Element {
  const [headingLevel, setHeadingLevel] = useState('heading');
  const panels = {
    text: {
      title: __('Text', 'mailpoet'),
      description: __(
        'Manage the fonts and typography used on text.',
        'mailpoet',
      ),
      defaultControls: DEFAULT_CONTROLS,
    },
    link: {
      title: __('Links', 'mailpoet'),
      description: __(
        'Manage the fonts and typography used on links.',
        'mailpoet',
      ),
      defaultControls: {
        ...DEFAULT_CONTROLS,
        textDecoration: true,
      },
    },
    heading: {
      title: __('Headings', 'mailpoet'),
      description: __(
        'Manage the fonts and typography used on headings.',
        'mailpoet',
      ),
      defaultControls: {
        ...DEFAULT_CONTROLS,
        textTransform: true,
      },
    },
    button: {
      title: __('Buttons', 'mailpoet'),
      description: __(
        'Manage the fonts and typography used on buttons.',
        'mailpoet',
      ),
      defaultControls: DEFAULT_CONTROLS,
    },
  };
  return (
    <>
      <ScreenHeader
        title={panels[element].title}
        description={panels[element].description}
      />
      <Spacer marginX={4}>
        <TypographyPreview element={element} headingLevel={headingLevel} />
      </Spacer>
      {element === 'heading' && (
        <Spacer marginX={4} marginBottom="1em">
          <ToggleGroupControl
            label={__('Select heading level')}
            hideLabelFromVision
            value={headingLevel}
            onChange={(value) => setHeadingLevel(value.toString())}
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
      <TypographyElementPanel
        element={element}
        headingLevel={headingLevel}
        defaultControls={panels[element].defaultControls}
      />
    </>
  );
}
