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
            label={__('Select heading level', 'mailpoet')}
            hideLabelFromVision
            value={headingLevel}
            onChange={(value) => setHeadingLevel(value.toString())}
            isBlock
            size="__unstable-large"
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="heading"
              label={_x('All', 'heading levels', 'mailpoet')}
            />
            <ToggleGroupControlOption
              value="h1"
              label={_x('H1', 'Heading Level', 'mailpoet')}
            />
            <ToggleGroupControlOption
              value="h2"
              label={_x('H2', 'Heading Level', 'mailpoet')}
            />
            <ToggleGroupControlOption
              value="h3"
              label={_x('H3', 'Heading Level', 'mailpoet')}
            />
            <ToggleGroupControlOption
              value="h4"
              label={_x('H4', 'Heading Level', 'mailpoet')}
            />
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
