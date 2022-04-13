export const name = 'mailpoet/todo';

export const settings = {
  title: 'Todo block',
  description: 'This block needs to be implemented',
  category: 'text',
  attributes: {
    originalBlock: {
      type: 'string',
      default: 'Not set',
    },
  },
  supports: {
    html: false,
    multiple: false,
  },
  edit: function Edit({ attributes }): JSX.Element {
    return <p>Todo {attributes.originalBlock}</p>;
  },
  save(): string {
    return '';
  },
};
