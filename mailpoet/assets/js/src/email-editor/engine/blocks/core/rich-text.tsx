import { BlockControls } from '@wordpress/block-editor';
import {
  ToolbarButton,
  ToolbarDropdownMenu,
  ToolbarGroup,
} from '@wordpress/components';
import { html, Icon, textHorizontal } from '@wordpress/icons';
import {
  create,
  insert,
  registerFormatType,
  unregisterFormatType,
} from '@wordpress/rich-text';

// eslint-disable-next-line @typescript-eslint/no-unused-vars
function ShortcodeButton({ onChange, value }) {
  const items = [
    {
      title: 'First Name',
      onClick: () => {
        const textValue = create({
          text: '[subscriber:firstname]', // Placeholder for first name
        });
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        onChange(insert(value, textValue));
      },
    },
    {
      title: 'Last Name',
      onClick: () => {
        const textValue = create({
          text: '[subscriber:lastname]', // Placeholder for last name
        });
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        onChange(insert(value, textValue));
      },
    },
  ];

  return (
    <BlockControls>
      <ToolbarGroup>
        <ToolbarDropdownMenu
          icon="shortcode"
          label="Insert Shortcode"
          controls={items} // Pass the dropdown items
        />
      </ToolbarGroup>
    </BlockControls>
  );
}

function BitsButtonText({ onChange, value }) {
  const items = [
    {
      title: 'First Name',
      onClick: () => {
        const createdFragment = create({
          text: '<//wp-bit:mailpoet/firstname>',
        });
        onChange(
          // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
          insert(value, createdFragment),
        );
      },
    },
    {
      title: 'Last Name',
      onClick: () => {
        const createdFragment = create({
          text: '<//wp-bit:mailpoet/lastname>',
        });
        onChange(
          // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
          insert(value, createdFragment),
        );
      },
    },
  ];
  return (
    <BlockControls>
      <ToolbarGroup>
        <ToolbarDropdownMenu
          icon={<Icon icon={textHorizontal} />}
          label="Insert Bit as text"
          controls={items} // Pass the dropdown items
        />
      </ToolbarGroup>
    </BlockControls>
  );
}

function BitsButtonHtml({ isActive, onChange, value }) {
  return (
    <BlockControls>
      <ToolbarGroup>
        <ToolbarButton
          icon={<Icon icon={html} />}
          title="Insert Bit as HTML"
          onClick={() => {
            // Create a new RichText fragment with <mark> and custom content
            const createdFragment = create({
              html: '<//wp-bit:mailpoet/firstname><mark style="background-color: yellow;">We tried to insert the HTML Bit</mark>',
            });

            // Insert [subscriber:lastName] wrapped in <mark> tag with yellow background
            onChange(
              // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
              insert(value, createdFragment),
            );
          }}
          isActive={isActive}
        />
      </ToolbarGroup>
    </BlockControls>
  );
}

/**
 * Disable Rich text formats we currently cannot support
 * Note: This will remove its support for all blocks in the email editor e.g., p, h1,h2, etc
 */
function disableCertainRichTextFormats() {
  // remove support for inline image - We can't use it
  unregisterFormatType('core/image');

  // remove support for Inline code - Not well formatted
  unregisterFormatType('core/code');

  // remove support for Language - Not supported for now
  unregisterFormatType('core/language');
}

function extendRichTextFormats() {
  registerFormatType('mailpoet-email-editor/shortcode', {
    title: 'Shortcode',
    tagName: 'mark', // Use <span> for inline format
    className: 'subscriber-shortcode', // Optional class for CSS styling
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    attributes: {
      style: 'style', // Inline style
    },
    edit: ShortcodeButton,
  });

  registerFormatType('mailpoet-email-editor/bits-text', {
    title: 'Bits',
    tagName: 'mark', // Use <span> for inline format
    className: 'subscriber-bits-text', // Optional class for CSS styling
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    attributes: {
      style: 'style', // Inline style
    },
    edit: BitsButtonText,
  });

  registerFormatType('mailpoet-email-editor/bits-html', {
    title: 'Bits',
    tagName: 'span', // Use <span> for inline format
    className: 'subscriber-bits-html', // Optional class for CSS styling
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    attributes: {
      style: 'style', // Inline style
    },
    edit: BitsButtonHtml,
  });
}

export { disableCertainRichTextFormats, extendRichTextFormats };
