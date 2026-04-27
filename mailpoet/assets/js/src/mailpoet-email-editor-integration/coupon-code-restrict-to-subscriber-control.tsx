import { InspectorControls } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import type { ComponentType } from 'react';
import {
  addRestrictToSubscriberAttribute,
  CouponCodeAttributes,
  RESTRICT_TO_SUBSCRIBER_ATTRIBUTE,
  shouldShowRestrictToSubscriberControl,
} from './coupon-code-restrict-to-subscriber';

const FILTER_NAMESPACE = 'mailpoet/coupon-code-restrict-to-subscriber';

type CouponCodeBlockEditProps = BlockEditProps<CouponCodeAttributes> & {
  name: string;
};

const withRestrictToSubscriberControl = createHigherOrderComponent(
  (BlockEdit: ComponentType<CouponCodeBlockEditProps>) =>
    function MailPoetCouponCodeRestrictToSubscriberControl(
      props: CouponCodeBlockEditProps,
    ): JSX.Element {
      const restrictToSubscriberCopy = __(
        "Limit this coupon to the recipient's email address.",
        'mailpoet',
      );

      if (
        !shouldShowRestrictToSubscriberControl({
          attributes: props.attributes,
          blockName: props.name,
        })
      ) {
        return <BlockEdit {...props} />;
      }

      return (
        <Fragment>
          <BlockEdit {...props} />
          <InspectorControls>
            <PanelBody title={__('Usage restriction', 'mailpoet')}>
              <ToggleControl
                checked={Boolean(
                  props.attributes[RESTRICT_TO_SUBSCRIBER_ATTRIBUTE],
                )}
                label={restrictToSubscriberCopy}
                help={restrictToSubscriberCopy}
                onChange={(restrictToSubscriber) => {
                  props.setAttributes({
                    [RESTRICT_TO_SUBSCRIBER_ATTRIBUTE]: restrictToSubscriber,
                  });
                }}
              />
            </PanelBody>
          </InspectorControls>
        </Fragment>
      );
    },
  'withRestrictToSubscriberControl',
);

export const registerCouponCodeRestrictToSubscriberExtension = (): void => {
  addFilter(
    'blocks.registerBlockType',
    FILTER_NAMESPACE,
    addRestrictToSubscriberAttribute,
  );

  addFilter(
    'editor.BlockEdit',
    FILTER_NAMESPACE,
    withRestrictToSubscriberControl,
  );
};
