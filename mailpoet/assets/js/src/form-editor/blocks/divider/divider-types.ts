export enum Types {
  Divider = 'divider',
  Spacer = 'spacer',
}

export enum Style {
  Solid = 'solid',
  Dashed = 'dashed',
  Dotted = 'dotted',
}

export interface Attributes {
  className: string;
  height: number;
  type: Types;
  style: Style;
  dividerHeight: number;
  dividerWidth: number;
  color: string;
}

export const defaultAttributes = {
  className: undefined,
  height: 1,
  type: Types.Divider,
  style: Style.Solid,
  dividerHeight: 1,
  dividerWidth: 100,
  color: 'black',
};
