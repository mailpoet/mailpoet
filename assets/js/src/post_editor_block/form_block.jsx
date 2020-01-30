import React from 'react';

const wp = window.wp;
const { registerBlockType } = wp.blocks;

registerBlockType('mailpoet/form-block', {
  title: 'Example: Basic (esnext)',
  icon: 'universal-access-alt',
  category: 'widgets',
  example: {},
  edit() {
    return (
      <div className="mailpoet-block-div">Hello World, step 1 (from the editor).</div>
    );
  },
  save() {
    return null;
  },
});
