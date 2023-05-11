// Modules that are exposed for usage in the premium plugin.
// The exports below are available via "window.MailPoetLib".

// libs
export * as ClassNames from 'classnames';
export * as React from 'react';
export * as ReactDom from 'react-dom';
export * as ReactJsxRuntime from 'react/jsx-runtime';
export * as ReactRouter from 'react-router-dom';
export * as ReactTooltip from 'react-tooltip';
export * as ReactStringReplace from 'react-string-replace';
export * as Slugify from 'slugify';
export { Button as WordpressComponentsButton } from '@wordpress/components';
export { CheckboxControl as WordpressComponentsCheckboxControl } from '@wordpress/components';
export { DatePicker as WordpressComponentsDatePicker } from '@wordpress/components';
export { DateTimePicker as WordpressComponentsDateTimePicker } from '@wordpress/components';
export { TimePicker as WordpressComponentsTimePicker } from '@wordpress/components';
export { Dropdown as WordpressComponentsDropdown } from '@wordpress/components';
export { ExternalLink as WordpressComponentsExternalLink } from '@wordpress/components';
export { FormTokenField as WordpressComponentsFormTokenField } from '@wordpress/components';
export { RadioControl as WordpressComponentsRadioControl } from '@wordpress/components';
export { SelectControl as WordpressComponentsSelectControl } from '@wordpress/components';
export { TextControl as WordpressComponentsTextControl } from '@wordpress/components';
export { TextareaControl as WordpressComponentsTextareaControl } from '@wordpress/components';
export { __experimentalNumberControl as WordpressComponentsNumberControl } from '@wordpress/components';
export { __experimentalConfirmDialog as WordpressComponentsConfirmDialog } from '@wordpress/components';
export { MenuItem as WordpressComponentsMenuItem } from '@wordpress/components';
export { PanelBody as WordpressComponentsPanelBody } from '@wordpress/components';
export { Spinner as WordpressComponentsSpinner } from '@wordpress/components';
export * as WordPressData from '@wordpress/data';
export * as WordPressDate from '@wordpress/date';
export * as WordPressUrl from '@wordpress/url';
export * as WordPressI18n from '@wordpress/i18n';
export * as WordPressIcons from '@wordpress/icons';
export * as WordPressDataControls from '@wordpress/data-controls';
export * as WooCommerceDate from '@woocommerce/date';
import { DateRangeFilterPicker } from '@woocommerce/components/build';

export const WooCommerceComponents = {
  DateRangeFilterPicker,
};
// assets
export * as Automation from 'automation';
export * as Common from 'common';
export * as CommonFormReactSelect from 'common/form/react_select/react_select';
export * as CommonFormSelect from 'common/form/select/select';
export * as CommonGrid from 'common/grid';
export * as HelpTooltip from 'help-tooltip';
export * as Hooks from 'hooks.js';
export * as Listing from 'listing';
export * as DynamicSegments from 'segments/dynamic';
export * as AutomationEditorComponents from 'automation/editor/components';
export * as AutomationAnalyticsStore from 'automation/integrations/mailpoet/analytics/store';
