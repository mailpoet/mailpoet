import {
  ColorDefinition,
  FontSizeDefinition,
  GradientDefinition,
} from '../../../../assets/js/src/form_editor/store/form_data_types';

export const colorDefinitions: ColorDefinition[] = [
  {
    name: 'Black',
    slug: 'black',
    color: '#000000',
  },
  {
    name: 'White',
    slug: 'white',
    color: '#ffffff',
  },
];

export const gradientDefinitions: GradientDefinition[] = [
  {
    name: 'Black White',
    slug: 'black-white',
    gradient:
      'linear-gradient(90deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
  },
  {
    name: 'White Black',
    slug: 'white-black',
    gradient:
      'linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(0,0,0,1) 100%)',
  },
];

export const fontSizeDefinitions: FontSizeDefinition[] = [
  { name: 'Small', size: 13, slug: 'small' },
  { name: 'Normal', size: 16, slug: 'normal' },
];
