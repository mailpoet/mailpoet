import { JSDOM } from 'jsdom';
import type * as Blocks from '@wordpress/blocks';
import type * as Hooks from '@wordpress/hooks';
import {
  addRestrictToSubscriberAttribute,
  COUPON_CODE_BLOCK_NAME,
  ensureRestrictToSubscriberAttributeRegistered,
  RESTRICT_TO_SUBSCRIBER_ATTRIBUTE,
  shouldShowRestrictToSubscriberControl,
} from '../../../assets/js/src/mailpoet-email-editor-integration/coupon-code-restrict-to-subscriber';

// eslint-disable-next-line global-require, @typescript-eslint/no-var-requires -- Static ESM imports from @wordpress/blocks fail in this Mocha setup because the package imports JSON without import attributes.
const blockUtilities = require('@wordpress/blocks') as typeof Blocks;
// eslint-disable-next-line global-require, @typescript-eslint/no-var-requires -- Keep @wordpress/hooks loading consistent with @wordpress/blocks in this CommonJS test runner.
const hookUtilities = require('@wordpress/hooks') as typeof Hooks;

const {
  createBlock,
  getBlockType,
  parse,
  registerBlockType,
  serialize,
  unregisterBlockType,
} = blockUtilities;
const { addFilter, removeAllFilters } = hookUtilities;

const FILTER_NAMESPACE = 'mailpoet/test-coupon-code-restrict-to-subscriber';

const setBrowserGlobals = (): void => {
  const dom = new JSDOM('<!doctype html><html><body></body></html>');
  const globals = global as typeof globalThis & {
    MutationObserver: typeof MutationObserver;
  };

  globals.window = dom.window as unknown as Window & typeof globalThis;
  globals.document = dom.window.document;
  globals.MutationObserver = dom.window.MutationObserver;
  Object.defineProperty(globals, 'navigator', {
    configurable: true,
    value: dom.window.navigator,
  });
};

describe('coupon code restrictToSubscriber extension', () => {
  afterEach(() => {
    if (getBlockType(COUPON_CODE_BLOCK_NAME)) {
      unregisterBlockType(COUPON_CODE_BLOCK_NAME);
    }
    removeAllFilters('blocks.registerBlockType', FILTER_NAMESPACE);
  });

  it('adds the attribute to the coupon code block when the feature is available', () => {
    const settings = addRestrictToSubscriberAttribute(
      {
        attributes: {
          source: {
            default: 'createNew',
            type: 'string',
          },
        },
        category: 'text',
        title: 'Coupon Code',
      },
      COUPON_CODE_BLOCK_NAME,
      true,
    );

    expect(
      settings.attributes?.[RESTRICT_TO_SUBSCRIBER_ATTRIBUTE],
    ).to.deep.equal({
      default: false,
      type: 'boolean',
    });
  });

  it('does not add the attribute outside the MailPoet feature gate', () => {
    const settings = {
      attributes: {},
      category: 'text',
      title: 'Coupon Code',
    };

    expect(
      addRestrictToSubscriberAttribute(settings, COUPON_CODE_BLOCK_NAME, false),
    ).to.equal(settings);
  });

  it('adds the attribute when the coupon block was already registered', () => {
    setBrowserGlobals();

    registerBlockType(COUPON_CODE_BLOCK_NAME, {
      apiVersion: 3,
      attributes: {
        source: {
          default: 'createNew',
          type: 'string',
        },
      },
      category: 'text',
      save: () => null,
      title: 'Coupon Code',
    });

    ensureRestrictToSubscriberAttributeRegistered(true);

    const block = createBlock(COUPON_CODE_BLOCK_NAME, {
      restrictToSubscriber: true,
      source: 'createNew',
    });
    const serialized = serialize(block);

    expect(
      getBlockType(COUPON_CODE_BLOCK_NAME)?.attributes?.[
        RESTRICT_TO_SUBSCRIBER_ATTRIBUTE
      ],
    ).to.deep.equal({
      default: false,
      type: 'boolean',
    });
    expect(serialized).to.contain('"restrictToSubscriber":true');
  });

  it('shows the control only for automation create-new coupon blocks', () => {
    expect(
      shouldShowRestrictToSubscriberControl({
        attributes: { source: 'createNew' },
        blockName: COUPON_CODE_BLOCK_NAME,
        isAutomationNewsletter: true,
        isFeatureAvailable: true,
      }),
    ).to.equal(true);

    expect(
      shouldShowRestrictToSubscriberControl({
        attributes: { source: 'existing' },
        blockName: COUPON_CODE_BLOCK_NAME,
        isAutomationNewsletter: true,
        isFeatureAvailable: true,
      }),
    ).to.equal(false);

    expect(
      shouldShowRestrictToSubscriberControl({
        attributes: { source: 'createNew' },
        blockName: COUPON_CODE_BLOCK_NAME,
        isAutomationNewsletter: false,
        isFeatureAvailable: true,
      }),
    ).to.equal(false);
  });

  it('persists restrictToSubscriber through block serialization and parsing', () => {
    setBrowserGlobals();

    addFilter(
      'blocks.registerBlockType',
      FILTER_NAMESPACE,
      (
        settings: Blocks.BlockConfiguration<Record<string, unknown>>,
        blockName: string,
      ) => addRestrictToSubscriberAttribute(settings, blockName, true),
    );

    registerBlockType(COUPON_CODE_BLOCK_NAME, {
      apiVersion: 3,
      attributes: {
        source: {
          default: 'createNew',
          type: 'string',
        },
      },
      category: 'text',
      save: () => null,
      title: 'Coupon Code',
    });

    const block = createBlock(COUPON_CODE_BLOCK_NAME, {
      restrictToSubscriber: true,
      source: 'createNew',
    });
    const serialized = serialize(block);
    const parsed = parse(serialized);

    expect(serialized).to.contain('"restrictToSubscriber":true');
    expect(parsed[0].attributes.restrictToSubscriber).to.equal(true);
  });
});
