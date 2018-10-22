/**
 * BLOCK: mailpoetblock
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */
const wp = window.wp;
const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const { InspectorControls } = wp.editor;
const { PanelBody, SelectControl } = wp.components;
/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType('mailpoet/mp-form-block', {
  title: __('Subscriber - Mailpoet Simple'), // Block title.
  icon: 'email', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  keywords: [
    __('Mailpoet Form'),
  ],
  attributes: {
    form: {
      type: 'string',
    },
  },

  /**
   * The edit function describes the structure of your block in the context of the editor.
   * This represents what the editor will render when the block is used.
   *
   * The "edit" property must be a valid function.
   *
   * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
   */
  edit: function edit(props) {
    let form = props.attributes.form;

    const forms = window.mailpoet_forms;
    const options = [];
    for (let i = 0; i < forms.length; i++) {
      options.push({
        label: forms[i].name,
        value: forms[i].id
      })
    }

    if(!form && options.length) {
      form = options[0];
      props.setAttributes({
        form: options[0]
      });
    }

    return (
      <div>
        <InspectorControls>
          <PanelBody title={__('Pick a form', '')}>
            <SelectControl label={__('Form')}
                           value={form}
                           onChange={(value) => props.setAttributes({
                             form: value
                           })}
                           options={options}
            />
          </PanelBody>
        </InspectorControls>
        <div>
          <p>
            This is just placeholder for form.<br />
            We may try to re-render it similarly as it is defined in DB.
          </p>
          <div>
            <label>{__('Email')}</label>
            <input type={'text'} name={'email'}/>
          </div>
          <div>
            <label>{__('Firstname')}</label>
            <input type={'text'} name={'firstname'}/>
          </div>
          <div>
            <label>{__('Lastname')}</label>
            <input type={'text'} name={'firstname'}/>
          </div>
        </div>
      </div>
    );
  },

  /**
   * The save function defines the way in which the different attributes should be combined
   * into the final markup, which is then serialized by Gutenberg into post_content.
   *
   * The "save" property must be specified and must be a valid function.
   *
   * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
   */
  save: function save() {
    return null;
  },
});
