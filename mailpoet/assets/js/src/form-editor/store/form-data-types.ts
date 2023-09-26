import { SizeDefinition } from '../components/size_settings';

type PlacementStyles = {
  width: SizeDefinition;
};

type FormPlacementBase = {
  enabled: boolean;
  styles: PlacementStyles;
  categories: string[] | number[];
  tags: string[] | number[];
  posts: { all: boolean | '' | '1'; selected: string[] };
  pages: { all: boolean | '' | '1'; selected: string[] };
  homepage: boolean | '' | '1';
  tagArchives: { all: boolean | '' | '1'; selected: string[] };
  categoryArchives: { all: boolean | '' | '1'; selected: string[] };
};

export type FormSettingsType = {
  alignment: string;
  backgroundImageDisplay?: string;
  backgroundImageUrl?: string;
  belowPostStyles: PlacementStyles;
  borderColor?: string;
  backgroundColor?: string;
  borderRadius: number;
  borderSize: number;
  closeButton?: string;
  errorValidationColor?: string;
  fixedBarFormDelay: number;
  fixedBarFormCookieExpiration: number;
  fixedBarFormPosition: string;
  fixedBarStyles: PlacementStyles;
  fontColor?: string;
  fontFamily?: string;
  fontSize?: number;
  formPadding: number;
  formPlacement: {
    popup: FormPlacementBase & {
      exitIntentEnabled: boolean;
      delay: number | `${number}`;
      cookieExpiration: number | `${number}`;
      animation: string;
    };
    fixedBar: FormPlacementBase & {
      delay: number;
      cookieExpiration: number;
      animation: string;
      position: 'top' | 'bottom';
    };
    belowPosts: FormPlacementBase;
    slideIn: FormPlacementBase & {
      delay: number;
      cookieExpiration: number;
      animation: string;
      position: 'left' | 'right';
    };
    others: {
      styles: PlacementStyles;
    };
  };
  gradient?: string;
  inputPadding: number;
  otherStyles: PlacementStyles;
  placeFixedBarFormOnAllPages: boolean;
  placeFixedBarFormOnAllPosts: boolean;
  placeFormBellowAllPages: boolean;
  placeFormBellowAllPosts: boolean;
  placePopupFormOnAllPages: boolean;
  placePopupFormOnAllPosts: boolean;
  placeSlideInFormOnAllPages: boolean;
  placeSlideInFormOnAllPosts: boolean;
  popupFormDelay: number;
  popupFormCookieExpiration: number;
  popupStyles: PlacementStyles;
  segments: Array<string>;
  slideInFormDelay: number;
  slideInFormCookieExpiration: number;
  slideInFormPosition: string;
  slideInStyles: PlacementStyles;
  successValidationColor?: string;
  tags: string[];
};

export type FormData = {
  id: number | null;
  name: string;
  body: unknown[] | null;
  settings: FormSettingsType | null;
  styles: string | null;
  status: 'enabled' | 'disabled';
  created_at: { date: string; timezone_type: number; timezone: string };
  updated_at: { date: string; timezone_type: number; timezone: string };
  deleted_at: {
    date: string;
    timezone_type: number;
    timezone: string;
  } | null;
};

export type InputBlockStyles = {
  fullWidth: boolean;
  inheritFromTheme: boolean;
  bold?: boolean;
  backgroundColor?: string;
  gradient?: string;
  borderSize?: number;
  fontSize?: number;
  fontColor?: string;
  borderRadius?: number;
  borderColor?: string;
  padding?: number;
  fontFamily?: string;
};

export type InputBlockStylesServerData = {
  full_width: boolean | string;
  bold?: boolean | string;
  background_color?: string;
  gradient?: string;
  border_size?: string | number;
  font_size?: string | number;
  font_color?: string;
  border_radius?: string | number;
  border_color?: string;
  padding?: string | number;
  font_family?: string;
};

export type CustomField = {
  id: number;
  name: string;
  type: string;
  params: Record<string, unknown>;
  created_at: string;
  updated_at: string;
};

export type ColorDefinition = {
  name: string;
  slug: string;
  color: string;
};

export type GradientDefinition = {
  name: string;
  slug: string;
  gradient: string;
};

export type FontSizeDefinition = {
  name: string;
  slug: string;
  size: string;
};
