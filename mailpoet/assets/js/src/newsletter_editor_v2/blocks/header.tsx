import { useBlockProps, RichText } from '@wordpress/block-editor';

export const name = 'mailpoet/header';

export const settings = {
  title: 'Email Header',
  description: 'Email Header Content',
  category: 'text',
  attributes: {
    content: {
      type: 'array',
      source: 'children',
      selector: 'p',
      default: [
        'Header: ',
        {
          type: 'a',
          props: {
            children: ['View in Browser'],
            href: '[link:view_in_browser]',
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
