import { BlockInstance } from '@wordpress/blocks';
import { FormSettingsType, CustomFields } from './form_data_types';

export type BlockInsertionPoint = {
  rootClientId: string | undefined;
  insertionIndex: number | undefined;
};

export type HistoryRecord = {
  blocks: BlockInstance[];
  data: unknown;
  time: number;
};

export interface FormEditorWindow extends Window {
  mailpoet_custom_fields: CustomFields[];
  mailpoet_form_data: {
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
  mailpoet_date_types: {
    label: string;
    value: string;
  }[];
  mailpoet_date_formats: {
    year_month_day: string[];
    year_month: string[];
    year: string[];
    month: string[];
  };
  mailpoet_month_names: string[];
  mailpoet_form_edit_url: string;
  mailpoet_form_exports: {
    php: string;
    iframe: string;
    shortcode: string;
  };
  mailpoet_form_preview_page: string;
  mailpoet_form_segments: {
    id: string;
    name: string;
    type: string;
    subscribers: number;
  }[];
  mailpoet_close_icons_url: string;
  mailpoet_custom_fonts: string[];
  mailpoet_all_wp_posts: { id: string; name: string }[];
  mailpoet_all_wp_pages: { id: string; name: string }[];
  mailpoet_all_wp_categories: { id: string; name: string }[];
  mailpoet_all_wp_tags: { id: string; name: string }[];
  mailpoet_woocommerce_products: { id: string; name: string }[];
  mailpoet_woocommerce_categories: { id: string; name: string }[];
  mailpoet_woocommerce_tags: { id: string; name: string }[];
  mailpoet_tutorial_seen: '0' | '1';
  mailpoet_tutorial_url: string;
  mailpoet_is_administrator: boolean;
}

declare let window: FormEditorWindow;

export type State = {
  editorHistory: HistoryRecord[];
  editorHistoryOffset: number;
  formBlocks: BlockInstance[];
  formData: typeof window.mailpoet_form_data;
  dateSettingData: {
    dateTypes: typeof window.mailpoet_date_types;
    dateFormats: typeof window.mailpoet_date_formats;
    months: typeof window.mailpoet_month_names;
  };
  sidebarOpened: boolean;
  formExports: typeof window.mailpoet_form_exports;
  formErrors: string[];
  segments: typeof window.mailpoet_form_segments;
  customFields: typeof window.mailpoet_custom_fields;
  isFormSaving: boolean;
  isCustomFieldSaving: boolean;
  isCustomFieldCreating: boolean;
  isPreviewShown: boolean;
  isPreviewReady: boolean;
  isCustomFieldDeleting: boolean;
  inserterPanel: BlockInsertionPoint;
  notices: {
    id: string;
    content: string;
    isDismissible: boolean;
    status: string;
  }[];
  hasUnsavedChanges: boolean;
  sidebar: {
    activeSidebar: string;
    activeTab: string;
    openedPanels: string[];
  };
  previewSettings: {
    displayType: 'desktop' | 'mobile';
    formType: 'below_posts' | 'fixed_bar' | 'popup' | 'slide_in' | 'others';
  };
  fullscreenStatus: boolean;
  editorUrl: string;
  formEditorUrl: string;
  previewPageUrl: string;
  closeIconsUrl: string;
  customFonts: string[];
  allWpPosts: typeof window.mailpoet_all_wp_posts;
  allWpPages: typeof window.mailpoet_all_wp_pages;
  allWpCategories: typeof window.mailpoet_all_wp_categories;
  allWpTags: typeof window.mailpoet_all_wp_tags;
  allWooCommerceProducts: typeof window.mailpoet_woocommerce_products;
  allWooCommerceCategories: typeof window.mailpoet_woocommerce_categories;
  allWooCommerceTags: typeof window.mailpoet_woocommerce_tags;
  tutorialSeen: boolean;
  tutorialUrl: string;
  user: {
    isAdministrator: boolean;
  };
};
