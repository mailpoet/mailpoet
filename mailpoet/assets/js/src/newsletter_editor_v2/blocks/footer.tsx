import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export const name = 'mailpoet/footer';

const footerTemplate = [
  [
    'core/paragraph',
    {
      content:
        '<a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage Subscription</a>',
    },
  ],
  ['core/paragraph', { content: 'Add your address' }],
];

export const settings = {
  title: 'Email Footer',
  apiVersion: 2,
  description: 'Email Footer Content',
  category: 'text',
  supports: {
    html: false,
    multiple: true,
  },
  edit: function Edit() {
    const blockProps = useBlockProps();
    return (
      <div {...blockProps}>
        <InnerBlocks
          allowedBlocks={['core/paragraph']}
          template={footerTemplate}
          templateLock={false}
        />
      </div>
    );
  },
  save: function Save() {
    return <InnerBlocks.Content />;
  },
};
