/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { Block, getBlockSupport, hasBlockSupport } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { justifyLeft, justifyCenter, justifyRight } from '@wordpress/icons';

import {
  Flex,
  FlexItem,
  PanelBody,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  __experimentalToggleGroupControl as ToggleGroupControl,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  __experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const layoutBlockSupportKey = '__experimentalEmailFlexLayout';

function hasLayoutBlockSupport(blockName: string) {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  return hasBlockSupport(blockName, layoutBlockSupportKey);
}

function LayoutPanel({ setAttributes, attributes, name: blockName }) {
  const layoutBlockSupport = getBlockSupport(
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    blockName,
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    layoutBlockSupportKey,
    {},
  );

  if (!layoutBlockSupport) {
    return null;
  }

  const { justifyContent = 'left' } = attributes.layout || {};

  const onJustificationChange = (value) => {
    setAttributes({
      layout: {
        ...attributes.layout,
        justifyContent: value,
      },
    });
  };

  const justificationOptions = [
    {
      value: 'left',
      icon: justifyLeft,
      label: __('Justify items left'),
    },
    {
      value: 'center',
      icon: justifyCenter,
      label: __('Justify items center'),
    },
    {
      value: 'right',
      icon: justifyRight,
      label: __('Justify items right'),
    },
  ];

  return (
    <InspectorControls>
      <PanelBody title={__('Layout')}>
        <Flex>
          <FlexItem>
            <ToggleGroupControl
              __nextHasNoMarginBottom
              label={__('Justification')}
              value={justifyContent}
              onChange={onJustificationChange}
              className="block-editor-hooks__flex-layout-justification-controls"
            >
              {justificationOptions.map(({ value, icon, label }) => (
                <ToggleGroupControlOptionIcon
                  key={value}
                  value={value}
                  icon={icon}
                  label={label}
                />
              ))}
            </ToggleGroupControl>
          </FlexItem>
        </Flex>
      </PanelBody>
    </InspectorControls>
  );
}

/**
 * Filters registered block settings, extending attributes to include `layout`.
 *
 * @param {Object} settings Original block settings.
 *
 * @return {Object} Filtered block settings.
 */
export function addAttribute(settings: Block) {
  if (hasLayoutBlockSupport(settings.name)) {
    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        layout: {
          type: 'object',
        },
      },
    };
  }
  return settings;
}

/**
 * Override the default edit UI to include layout controls
 *
 * @param {Function} BlockEdit Original component.
 *
 * @return {Function} Wrapped component.
 */
export const withLayoutControls = createHigherOrderComponent(
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  (BlockEdit) => (props) => {
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    const supportLayout = hasLayoutBlockSupport(props.name);

    return [
      supportLayout && <LayoutPanel key="layout" {...props} />,
      <BlockEdit key="edit" {...props} />,
    ];
  },
  'withLayoutControls',
);

function BlockWithLayoutStyles({ block: BlockListBlock, props }) {
  const { attributes } = props;
  const { layout } = attributes;

  const layoutClasses = 'is-layout-email-flex is-layout-flex';
  const justify = (layout?.justifyContent as string) || 'left';
  const justificationClass = `is-content-justification-${justify}`;

  const layoutClassNames = classnames(justificationClass, layoutClasses);
  return <BlockListBlock {...props} className={layoutClassNames} />;
}

/**
 * Override the default block element to add the layout classes.
 *
 * @param {Function} BlockListBlock Original component.
 *
 * @return {Function} Wrapped component.
 */
export const withLayoutStyles = createHigherOrderComponent(
  (BlockListBlock) =>
    function maybeWrapWithLayoutStyles(props) {
      const blockSupportsLayout = hasLayoutBlockSupport(props.name as string);
      if (!blockSupportsLayout) {
        return <BlockListBlock {...props} />;
      }

      return <BlockWithLayoutStyles block={BlockListBlock} props={props} />;
    },
  'withLayoutStyles',
);

export function initializeLayout() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/layout/addAttribute',
    addAttribute,
  );
  addFilter(
    'editor.BlockListBlock',
    'mailpoet-email-editor/with-layout-styles',
    withLayoutStyles,
  );
  addFilter(
    'editor.BlockEdit',
    'mailpoet-email-editor/with-inspector-controls',
    withLayoutControls,
  );
}
