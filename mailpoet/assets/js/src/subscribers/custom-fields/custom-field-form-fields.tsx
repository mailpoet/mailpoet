import { useMemo } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
  Button,
  CheckboxControl,
  Dashicon,
  SelectControl,
  TextControl,
  ToggleControl,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import {
  DragDropContext,
  Draggable,
  type DropResult,
} from 'react-beautiful-dnd';
import { StrictModeDroppable as Droppable } from 'form-editor/utils/strict-mode-droppable';
import type {
  CustomField,
  CustomFieldDateSettings,
  CustomFieldPayload,
  CustomFieldType,
} from './types';

export type CustomFieldFormOption = {
  id: string;
  value: string;
  isChecked: boolean;
};

export type CustomFieldFormData = {
  name: string;
  type: CustomFieldType;
  label: string;
  required: boolean;
  validate: string;
  checkboxValue: string;
  checkboxChecked: boolean;
  options: CustomFieldFormOption[];
  dateType: string;
  dateFormat: string;
  defaultToday: boolean;
};

type Props = {
  data: CustomFieldFormData;
  dateSettings: CustomFieldDateSettings;
  disabled?: boolean;
  onChange: (data: CustomFieldFormData) => void;
};

const fieldTypeOptions: Array<{ label: string; value: CustomFieldType }> = [
  { label: __('Text', 'mailpoet'), value: 'text' },
  { label: __('Textarea', 'mailpoet'), value: 'textarea' },
  { label: __('Radio buttons', 'mailpoet'), value: 'radio' },
  { label: __('Checkbox', 'mailpoet'), value: 'checkbox' },
  { label: __('Select', 'mailpoet'), value: 'select' },
  { label: __('Date', 'mailpoet'), value: 'date' },
];

const validationOptions = [
  { label: __('None', 'mailpoet'), value: '' },
  { label: __('Numbers only', 'mailpoet'), value: 'number' },
  { label: __('Alphanumeric', 'mailpoet'), value: 'alphanum' },
  { label: __('Phone number', 'mailpoet'), value: 'phone' },
];

const customFieldTypes = fieldTypeOptions.map((option) => option.value);

function createOption(value = ''): CustomFieldFormOption {
  return {
    id: `${Date.now()}-${Math.random()}`,
    value,
    isChecked: false,
  };
}

function isCustomFieldType(type: string): type is CustomFieldType {
  return customFieldTypes.includes(type as CustomFieldType);
}

function getStringParam(
  params: Record<string, unknown>,
  key: string,
  fallback = '',
): string {
  const value = params[key];
  return typeof value === 'string' ? value : fallback;
}

function isTruthyParam(value: unknown): boolean {
  return value === true || value === '1' || value === 1;
}

function getOptionValue(value: unknown, index: number): CustomFieldFormOption {
  if (!value || typeof value !== 'object') {
    return createOption(sprintf(__('Option %d', 'mailpoet'), index + 1));
  }

  const option = value as Record<string, unknown>;
  return {
    id: `${Date.now()}-${index}-${Math.random()}`,
    value: getStringParam(option, 'value'),
    isChecked: isTruthyParam(option.is_checked),
  };
}

export function getInitialCustomFieldFormData(
  dateSettings: CustomFieldDateSettings,
): CustomFieldFormData {
  const dateType = dateSettings.dateTypes[0]?.value ?? 'year_month_day';
  const dateFormat = dateSettings.dateFormats[dateType]?.[0] ?? 'MM/DD/YYYY';
  return {
    name: '',
    type: 'text',
    label: '',
    required: false,
    validate: '',
    checkboxValue: '',
    checkboxChecked: false,
    options: [createOption(__('Option 1', 'mailpoet'))],
    dateType,
    dateFormat,
    defaultToday: false,
  };
}

export function getCustomFieldFormDataFromCustomField(
  customField: CustomField,
  dateSettings: CustomFieldDateSettings,
): CustomFieldFormData {
  const initialData = getInitialCustomFieldFormData(dateSettings);
  const type = isCustomFieldType(customField.type) ? customField.type : 'text';
  const params = customField.params;
  const values = Array.isArray(params.values)
    ? params.values.map(getOptionValue).filter((option) => option.value !== '')
    : [];
  const dateType = getStringParam(params, 'date_type', initialData.dateType);

  return {
    ...initialData,
    name: customField.name,
    type,
    label: getStringParam(params, 'label', customField.label),
    required: isTruthyParam(params.required),
    validate: getStringParam(params, 'validate'),
    checkboxValue: values[0]?.value ?? '',
    checkboxChecked: values[0]?.isChecked ?? false,
    options: values.length > 0 ? values : initialData.options,
    dateType,
    dateFormat: getStringParam(
      params,
      'date_format',
      dateSettings.dateFormats[dateType]?.[0] ?? initialData.dateFormat,
    ),
    defaultToday: isTruthyParam(params.is_default_today),
  };
}

export function buildCustomFieldPayload(
  data: CustomFieldFormData,
): CustomFieldPayload {
  const params: Record<string, unknown> = {
    label: data.label.trim() || data.name.trim(),
    required: data.required ? '1' : '',
  };

  if (data.type === 'text' || data.type === 'textarea') {
    params.validate = data.validate;
    if (data.type === 'textarea') {
      params.lines = '1';
    }
  }

  if (data.type === 'checkbox') {
    params.values = [
      {
        value: data.checkboxValue.trim(),
        is_checked: data.checkboxChecked ? '1' : '',
      },
    ];
  }

  if (data.type === 'radio' || data.type === 'select') {
    params.values = data.options.map((option) => ({
      value: option.value.trim(),
      is_checked: option.isChecked ? '1' : '',
    }));
  }

  if (data.type === 'date') {
    params.date_type = data.dateType;
    params.date_format = data.dateFormat;
    params.is_default_today = data.defaultToday ? '1' : '';
  }

  return {
    name: data.name.trim(),
    type: data.type,
    params,
  };
}

export function validateCustomFieldFormData(
  data: CustomFieldFormData,
): string | null {
  if (!data.name.trim()) {
    return __('Field name is required.', 'mailpoet');
  }
  if (data.type === 'checkbox' && !data.checkboxValue.trim()) {
    return __('Checkbox value is required.', 'mailpoet');
  }
  if (
    (data.type === 'radio' || data.type === 'select') &&
    data.options.some((option) => !option.value.trim())
  ) {
    return __('Every option needs a value.', 'mailpoet');
  }
  return null;
}

export function CustomFieldFormFields({
  data,
  dateSettings,
  disabled = false,
  onChange,
}: Props): JSX.Element {
  const dateFormatOptions = useMemo(
    () =>
      (dateSettings.dateFormats[data.dateType] ?? []).map((format) => ({
        label: format,
        value: format,
      })),
    [data.dateType, dateSettings.dateFormats],
  );

  const updateData = (updates: Partial<CustomFieldFormData>): void => {
    onChange({
      ...data,
      ...updates,
    });
  };

  const setType = (type: CustomFieldType): void => {
    const dateType = dateSettings.dateTypes[0]?.value ?? 'year_month_day';
    onChange({
      ...data,
      type,
      required: false,
      validate: '',
      checkboxValue: '',
      checkboxChecked: false,
      options: [createOption(__('Option 1', 'mailpoet'))],
      dateType,
      dateFormat: dateSettings.dateFormats[dateType]?.[0] ?? 'MM/DD/YYYY',
      defaultToday: false,
    });
  };

  const setOption = (
    id: string,
    updates: Partial<Pick<CustomFieldFormOption, 'value' | 'isChecked'>>,
  ): void => {
    updateData({
      options: data.options.map((option) => {
        if (option.id !== id) {
          return updates.isChecked ? { ...option, isChecked: false } : option;
        }
        return { ...option, ...updates };
      }),
    });
  };

  const removeOption = (id: string): void => {
    if (data.options.length === 1) {
      return;
    }
    updateData({
      options: data.options.filter((option) => option.id !== id),
    });
  };

  const onDragEnd = (result: DropResult): void => {
    if (!result.destination) {
      return;
    }
    const from = Number(result.source.index);
    const to = Number(result.destination.index);
    if (from === to) {
      return;
    }
    const options = [...data.options];
    const [movedOption] = options.splice(from, 1);
    options.splice(to, 0, movedOption);
    updateData({ options });
  };

  return (
    <>
      <SelectControl
        label={__('Field type', 'mailpoet')}
        value={data.type}
        options={fieldTypeOptions}
        onChange={(value) => setType(value as CustomFieldType)}
        disabled={disabled}
        __nextHasNoMarginBottom
      />
      <Spacer marginTop={4} />
      <TextControl
        label={__('Field name', 'mailpoet')}
        value={data.name}
        onChange={(name) => {
          onChange({
            ...data,
            name,
            label: data.label === data.name ? name : data.label,
          });
        }}
        disabled={disabled}
        __nextHasNoMarginBottom
      />
      <Spacer marginTop={4} />
      <TextControl
        label={__('Label', 'mailpoet')}
        value={data.label}
        placeholder={data.name}
        onChange={(label) => updateData({ label })}
        disabled={disabled}
        __nextHasNoMarginBottom
      />
      <Spacer marginTop={4} />
      <ToggleControl
        label={__('Mandatory field', 'mailpoet')}
        checked={data.required}
        onChange={(required) => updateData({ required })}
        disabled={disabled}
        __nextHasNoMarginBottom
      />

      {(data.type === 'text' || data.type === 'textarea') && (
        <>
          <Spacer marginTop={4} />
          <SelectControl
            label={__('Validate for', 'mailpoet')}
            value={data.validate}
            options={validationOptions}
            onChange={(validate) => updateData({ validate })}
            disabled={disabled}
            __nextHasNoMarginBottom
          />
        </>
      )}

      {data.type === 'checkbox' && (
        <>
          <Spacer marginTop={4} />
          <ToggleControl
            label={__('Checked by default', 'mailpoet')}
            checked={data.checkboxChecked}
            onChange={(checkboxChecked) => updateData({ checkboxChecked })}
            disabled={disabled}
            __nextHasNoMarginBottom
          />
          <Spacer marginTop={4} />
          <TextControl
            label={__('Checkbox value', 'mailpoet')}
            value={data.checkboxValue}
            onChange={(checkboxValue) => updateData({ checkboxValue })}
            disabled={disabled}
            __nextHasNoMarginBottom
          />
        </>
      )}

      {(data.type === 'radio' || data.type === 'select') && (
        <>
          <Spacer marginTop={4} />
          <div className="mailpoet-custom-fields-form-options">
            <div className="mailpoet-custom-fields-form-options-label">
              {__('Options', 'mailpoet')}
            </div>
            <DragDropContext onDragEnd={onDragEnd}>
              <Droppable droppableId="custom-field-options">
                {(droppableProvided) => (
                  <div
                    ref={droppableProvided.innerRef}
                    {...droppableProvided.droppableProps}
                  >
                    {data.options.map((option, index) => (
                      <Draggable
                        key={option.id}
                        draggableId={option.id}
                        index={index}
                        isDragDisabled={disabled}
                      >
                        {(draggableProvided, snapshot) => (
                          <div
                            ref={draggableProvided.innerRef}
                            {...draggableProvided.draggableProps}
                            className={`mailpoet-custom-fields-form-option ${
                              snapshot.isDragging ? 'is-dragging' : ''
                            }`}
                          >
                            <div
                              {...draggableProvided.dragHandleProps}
                              aria-label={__('Drag to reorder', 'mailpoet')}
                              className="mailpoet-custom-fields-form-option-drag-handle"
                            >
                              <Dashicon icon="menu" />
                            </div>
                            <div className="mailpoet-custom-fields-form-option-input">
                              <TextControl
                                label={sprintf(
                                  __('Option %d', 'mailpoet'),
                                  index + 1,
                                )}
                                hideLabelFromVision
                                value={option.value}
                                onChange={(value) =>
                                  setOption(option.id, { value })
                                }
                                disabled={disabled}
                                __nextHasNoMarginBottom
                              />
                            </div>
                            <div>
                              <CheckboxControl
                                label={__('Default', 'mailpoet')}
                                checked={option.isChecked}
                                onChange={() =>
                                  setOption(option.id, {
                                    isChecked: !option.isChecked,
                                  })
                                }
                                disabled={disabled}
                                __nextHasNoMarginBottom
                              />
                            </div>
                            <div>
                              <Button
                                variant="tertiary"
                                onClick={() => removeOption(option.id)}
                                disabled={disabled || data.options.length === 1}
                                __next40pxDefaultSize
                              >
                                {__('Remove', 'mailpoet')}
                              </Button>
                            </div>
                          </div>
                        )}
                      </Draggable>
                    ))}
                    {droppableProvided.placeholder}
                  </div>
                )}
              </Droppable>
            </DragDropContext>
            <Button
              variant="secondary"
              onClick={() =>
                updateData({
                  options: [
                    ...data.options,
                    createOption(
                      sprintf(
                        __('Option %d', 'mailpoet'),
                        data.options.length + 1,
                      ),
                    ),
                  ],
                })
              }
              disabled={disabled}
              __next40pxDefaultSize
            >
              {__('Add option', 'mailpoet')}
            </Button>
          </div>
        </>
      )}

      {data.type === 'date' && (
        <>
          <Spacer marginTop={4} />
          <ToggleControl
            label={__('Default to today', 'mailpoet')}
            checked={data.defaultToday}
            onChange={(defaultToday) => updateData({ defaultToday })}
            disabled={disabled}
            __nextHasNoMarginBottom
          />
          <Spacer marginTop={4} />
          <SelectControl
            label={__('Date type', 'mailpoet')}
            value={data.dateType}
            options={dateSettings.dateTypes}
            onChange={(dateType) =>
              updateData({
                dateType,
                dateFormat:
                  dateSettings.dateFormats[dateType]?.[0] ?? 'MM/DD/YYYY',
              })
            }
            disabled={disabled}
            __nextHasNoMarginBottom
          />
          {dateFormatOptions.length > 1 && (
            <>
              <Spacer marginTop={4} />
              <SelectControl
                label={__('Date format', 'mailpoet')}
                value={data.dateFormat}
                options={dateFormatOptions}
                onChange={(dateFormat) => updateData({ dateFormat })}
                disabled={disabled}
                __nextHasNoMarginBottom
              />
            </>
          )}
        </>
      )}
    </>
  );
}
