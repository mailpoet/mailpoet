import { useBlockProps, RichText } from '@wordpress/block-editor';

export const name = 'mailpoet/footer';

export const settings = {
  title: 'Email Footer',
  apiVersion: 2,
  description: 'Email Footer Content',
  category: 'text',
  attributes: {
    content: {
      type: 'array',
      source: 'children',
      selector: 'p',
      default: [
        'Footer content!',
        {
          type: 'a',
          props: {
            children: ['Unsubscribe'],
            href: '[link:unsubscribe]',
          },
        },
      ],
    },
  },
  supports: {
    html: false,
    multiple: true,
  },
  edit: function Edit(props) {
    const {
      attributes: { content },
      setAttributes,
    } = props;
    const blockProps = useBlockProps();
    const onChangeContent = (newContent) => {
      setAttributes({ content: newContent });
    };
    return (
      <RichText
        {...blockProps}
        tagName="p"
        onChange={onChangeContent}
        value={content}
      />
    );
  },
  save: (props) => {
    const blockProps = useBlockProps.save();
    return (
      <RichText.Content
        {...blockProps}
        tagName="p"
        value={props.attributes.content}
      />
    );
  },
};
