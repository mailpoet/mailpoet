import { Edit } from './edit';
import metadata from './block.json';
import { App } from '../App';
import { ConfigComponent } from '../components/config';
// import { Save } from './save';

const { registerBlockType } = window.wp.blocks;
App.trigger('gutenberg:start', App, { config: {} });

App.getConfig = ConfigComponent.getConfig;
App.setConfig = ConfigComponent.setConfig;
App.setConfig(window.config);

registerBlockType(metadata.name, {
  title: 'Sample Ported Block',
  example: {
    attributes: {
      message: 'Sample Ported Block',
    },
  },
  icon: 'flag',
  category: 'design',
  attributes: {
    message: {
      type: 'string',
      source: 'text',
      selector: 'div',
      default: 'This is the text',
    },
  },
  edit: Edit,
  save: () => null,
});
