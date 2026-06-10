import { __ } from '@wordpress/i18n';

/**
 * Order-aware product collections for automation emails.
 *
 * The collection slugs must match
 * MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\OrderProductCollectionProcessor,
 * which fills the marked blocks with products derived from the order that
 * triggered the email at send time. Registering the collections here gives the
 * blocks a visible name, description, and preview notice in the editor, so
 * merchants can see the products are picked per order.
 */

type ProductCollectionConfig = {
  name: string;
  title: string;
  description: string;
  keywords: string[];
  scope: string[];
  preview: {
    initialPreviewState: {
      isPreview: boolean;
      previewMessage: string;
    };
  };
  attributes: {
    query: Record<string, unknown>;
  };
};

type WcBlocksRegistry = {
  __experimentalRegisterProductCollection?: (
    config: ProductCollectionConfig,
  ) => void;
};

const getRegisterProductCollection = ():
  | ((config: ProductCollectionConfig) => void)
  | undefined => {
  const wc = (
    window as unknown as { wc?: { wcBlocksRegistry?: WcBlocksRegistry } }
  ).wc;
  // eslint-disable-next-line no-underscore-dangle -- the WooCommerce API is named with the experimental prefix.
  return wc?.wcBlocksRegistry?.__experimentalRegisterProductCollection;
};

const collectionQueryDefaults = {
  perPage: 4,
  pages: 1,
  offset: 0,
  postType: 'product',
  order: 'desc',
  orderBy: 'popularity',
  search: '',
  exclude: [],
  inherit: false,
  taxQuery: [],
  isProductCollectionBlock: true,
  featured: false,
  woocommerceOnSale: false,
  woocommerceStockStatus: ['instock', 'onbackorder'],
  woocommerceAttributes: [],
  woocommerceHandPickedProducts: [],
  filterable: false,
};

const getOrderProductCollections = (): ProductCollectionConfig[] => [
  {
    name: 'mailpoet/product-collection/order-cross-sells',
    title: __('Goes well with the order', 'mailpoet'),
    description: __(
      'Shows cross-sells of the products from the order that triggered the automation. When the purchased products have no cross-sells, their related products are shown instead.',
      'mailpoet',
    ),
    keywords: ['cross-sells', 'order', 'automation'],
    scope: ['inserter', 'block'],
    preview: {
      initialPreviewState: {
        isPreview: true,
        previewMessage: __(
          'Sample products are shown in the editor. Each customer will see products that go well with their own order.',
          'mailpoet',
        ),
      },
    },
    attributes: { query: { ...collectionQueryDefaults } },
  },
  {
    name: 'mailpoet/product-collection/order-same-tag',
    title: __('More with the same tag', 'mailpoet'),
    description: __(
      'Shows other products sharing tags with the products from the order that triggered the automation.',
      'mailpoet',
    ),
    keywords: ['tag', 'order', 'automation'],
    scope: ['inserter', 'block'],
    preview: {
      initialPreviewState: {
        isPreview: true,
        previewMessage: __(
          'Sample products are shown in the editor. Each customer will see products with the same tags as their own order.',
          'mailpoet',
        ),
      },
    },
    attributes: { query: { ...collectionQueryDefaults } },
  },
  {
    name: 'mailpoet/product-collection/order-same-category',
    title: __('More from the same category', 'mailpoet'),
    description: __(
      'Shows other products from the categories of the products from the order that triggered the automation.',
      'mailpoet',
    ),
    keywords: ['category', 'order', 'automation'],
    scope: ['inserter', 'block'],
    preview: {
      initialPreviewState: {
        isPreview: true,
        previewMessage: __(
          'Sample products are shown in the editor. Each customer will see products from the same categories as their own order.',
          'mailpoet',
        ),
      },
    },
    attributes: { query: { ...collectionQueryDefaults } },
  },
];

export const registerOrderProductCollections = (): boolean => {
  const registerProductCollection = getRegisterProductCollection();
  if (!registerProductCollection) {
    return false;
  }

  getOrderProductCollections().forEach((collection) => {
    try {
      registerProductCollection(collection);
    } catch (error) {
      // An already registered collection (e.g., after a hot reload) must not break the editor.
      // eslint-disable-next-line no-console -- surface the failure to developers without crashing.
      console.warn('MailPoet: could not register product collection', error);
    }
  });
  return true;
};

export const registerOrderProductCollectionsWhenAvailable = (
  attempts = 20,
): void => {
  if (attempts <= 0 || registerOrderProductCollections()) {
    return;
  }

  // The wc-blocks-registry script may load after this bundle; WooCommerce may also be inactive.
  setTimeout(
    () => registerOrderProductCollectionsWhenAvailable(attempts - 1),
    250,
  );
};
