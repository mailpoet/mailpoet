import {
  ColorDefinition,
  FontSizeDefinition,
} from '../../../../assets/js/src/form_editor/store/form_data_types';

export const colorDefinitions: ColorDefinition[] = [{
  name: 'Black',
  slug: 'black',
  color: '#000000',
}, {
  name: 'White',
  slug: 'white',
  color: '#ffffff',
}];

export const fontSizeDefinitions: FontSizeDefinition[] = [
  { name: 'Small', size: 13, slug: 'small' },
  { name: 'Normal', size: 16, slug: 'normal' },
];
