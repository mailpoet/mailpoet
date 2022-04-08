/* eslint-disable camelcase */
import { has } from 'lodash';
import asNum from './server_value_as_num';
import {
  mapInputBlockStyles,
  mapColorSlug,
  mapFontSizeSlug,
  mapGradientSlug,
} from './mapping/to_blocks/styles_mapper';
import formatCustomFieldBlockName from '../blocks/format_custom_field_block_name.jsx';
import { defaultAttributes as dividerDefaultAttributes } from '../blocks/divider/divider_types';

const generateId = () => `${Math.random().toString()}-${Date.now()}`;

export const customFieldValuesToBlockValues = (values) =>
  values.map((value) => {
    const mappedValue = {
      name: value.value,
      id: generateId(),
    };
    if (has(value, 'is_checked') && value.is_checked) {
      mappedValue.isChecked = true;
    }
    return mappedValue;
  });

const mapCustomField = (item, customFields, mappedCommonProperties) => {
  const customField = customFields.find(
    (cf) => cf.id === parseInt(item.id, 10),
  );
  if (!customField) return null;

  const namesMap = {
    text: 'mailpoet-form/custom-text',
    textarea: 'mailpoet-form/custom-textarea',
    radio: 'mailpoet-form/custom-radio',
    checkbox: 'mailpoet-form/custom-checkbox',
    select: 'mailpoet-form/custom-select',
    date: 'mailpoet-form/custom-date',
  };
  const mapped = {
    ...mappedCommonProperties,
    name: formatCustomFieldBlockName(namesMap[customField.type], customField),
  };
  mapped.attributes.customFieldId = customField.id;
  if (item.params) {
    if (has(item.params, 'validate') && !!item.params.validate) {
      mapped.attributes.validate = item.params.validate;
    }
    if (has(item.params, 'hide_label')) {
      mapped.attributes.hideLabel = !!item.params.hide_label;
    }
    if (has(item.params, 'lines')) {
      mapped.attributes.lines = item.params.lines;
    }
    if (has(item.params, 'date_type')) {
      mapped.attributes.dateType = item.params.date_type;
    }
    if (has(item.params, 'date_format')) {
      mapped.attributes.dateFormat = item.params.date_format;
    }
    if (has(item.params, 'is_default_today')) {
      mapped.attributes.defaultToday = !!item.params.is_default_today;
    }
    if (has(item.params, 'values') && Array.isArray(item.params.values)) {
      mapped.attributes.values = customFieldValuesToBlockValues(
        item.params.values,
      );
    }
  }

  if (customField.type === 'text' || customField.type === 'textarea') {
    mapped.attributes.styles = mapInputBlockStyles(item.styles);
  }
  return mapped;
};

/**
 * @param {Object} data - column or columns block data
 * @param {Array.<{name: string, slug: string, size: number}>} fontSizeDefinitions
 * @param {Array.<{name: string, slug: string, color: string}>} colorDefinitions
 * @param {Array.<{name: string, slug: string, gradient: string}>} gradientDefinitions
 * @param customFields - list of all custom Fields
 */
const mapColumnBlocks = (
  data,
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
  customFields = [],
) => {
  // eslint-disable-next-line no-use-before-define
  const mapFormBodyToBlocks = formBodyToBlocksFactory(
    fontSizeDefinitions,
    colorDefinitions,
    gradientDefinitions,
    customFields,
  );
  const mapped = {
    clientId: generateId(),
    name: `core/${data.type}`,
    isValid: true,
    attributes: {
      style: {
        color: {},
      },
    },
    innerBlocks: mapFormBodyToBlocks(data.body ? data.body : []),
  };
  const textColorSlug = mapColorSlug(colorDefinitions, data.params.text_color);
  const backgroundColorSlug = mapColorSlug(
    colorDefinitions,
    data.params.background_color,
  );
  const gradientSlug = mapGradientSlug(
    gradientDefinitions,
    data.params.gradient,
  );
  if (has(data.params, 'width')) {
    // BC fix: Set % as unit for values saved before units were introduced
    mapped.attributes.width = Number.isNaN(Number(data.params.width))
      ? data.params.width
      : `${data.params.width}%`;
  }
  if (has(data.params, 'vertical_alignment')) {
    mapped.attributes.verticalAlignment = data.params.vertical_alignment;
  }
  if (has(data.params, 'text_color')) {
    mapped.attributes.textColor = textColorSlug;
    mapped.attributes.style.color.text = !textColorSlug
      ? data.params.text_color
      : undefined;
  }
  if (has(data.params, 'background_color')) {
    mapped.attributes.backgroundColor = backgroundColorSlug;
    mapped.attributes.style.color.background = !backgroundColorSlug
      ? data.params.background_color
      : undefined;
  }
  if (has(data.params, 'gradient')) {
    mapped.attributes.gradient = gradientSlug;
    mapped.attributes.style.color.gradient = !gradientSlug
      ? data.params.gradient
      : undefined;
  }
  if (has(data.params, 'class_name') && data.params.class_name) {
    mapped.attributes.className = data.params.class_name;
  }
  if (has(data.params, 'padding')) {
    mapped.attributes.style.spacing = { padding: data.params.padding };
  }
  if (has(data.params, 'is_stacked_on_mobile')) {
    mapped.attributes.isStackedOnMobile =
      data.params.is_stacked_on_mobile === '1';
  }
  // BC for columns data without is_stacked_on_mobile property
  if (data.type === 'columns' && !has(data.params, 'is_stacked_on_mobile')) {
    mapped.attributes.isStackedOnMobile = true;
  }
  return mapped;
};

/**
 * Factory for form data to blocks mapper
 * @param {Array.<{name: string, slug: string, size: number}>} fontSizeDefinitions
 * @param {Array.<{name: string, slug: string, color: string}>} colorDefinitions
 * @param {Array.<{name: string, slug: string, gradient: string}>} gradientsDefinitions
 * @param customFields - list of all custom Fields
 */
export const formBodyToBlocksFactory = (
  fontSizeDefinitions,
  colorDefinitions,
  gradientsDefinitions,
  customFields = [],
) => {
  if (!Array.isArray(customFields)) {
    throw new Error('Mapper expects customFields to be an array.');
  }

  /**
   * Transforms form body items to array of blocks which can be passed to block editor.
   * @param {array} data - from form.body property
   */
  const formBodyToBlocks = (data) => {
    if (!Array.isArray(data)) {
      throw new Error('Mapper expects form body to be an array.');
    }

    return data
      .map((item) => {
        if (['column', 'columns'].includes(item.type)) {
          return mapColumnBlocks(
            item,
            fontSizeDefinitions,
            colorDefinitions,
            gradientsDefinitions,
            customFields,
          );
        }

        const mapped = {
          clientId: `${item.id}_${generateId()}`,
          isValid: true,
          innerBlocks: [],
          attributes: {
            labelWithinInput: false,
            mandatory: false,
            className: null,
          },
        };

        if (['heading', 'paragraph'].includes(item.type)) {
          mapped.attributes.style = {
            color: {},
            typography: {
              fontSize: undefined,
              lineHeight: undefined,
            },
          };
        }

        if (item.params && has(item.params, 'class_name')) {
          mapped.attributes.className = item.params.class_name;
        }
        if (item.params && has(item.params, 'required')) {
          mapped.attributes.mandatory = !!item.params.required;
        }
        if (item.params && has(item.params, 'label_within')) {
          mapped.attributes.labelWithinInput = !!item.params.label_within;
        }
        if (item.params) {
          mapped.attributes.label = item.params.label ? item.params.label : '';
        }
        if (item.params && has(item.params, 'text_color')) {
          const textColorSlug = mapColorSlug(
            colorDefinitions,
            item.params.text_color,
          );
          mapped.attributes.textColor = textColorSlug;
          if (['heading', 'paragraph'].includes(item.type) && !textColorSlug) {
            mapped.attributes.style.color.text = item.params.text_color;
          } else if (!textColorSlug) {
            mapped.attributes.customTextColor = item.params.text_color;
          }
        }
        if (item.params && has(item.params, 'background_color')) {
          const slug = mapColorSlug(
            colorDefinitions,
            item.params.background_color,
          );
          mapped.attributes.backgroundColor = slug;
          if (['heading', 'paragraph'].includes(item.type) && !slug) {
            mapped.attributes.style.color.background =
              item.params.background_color;
          } else if (!slug) {
            mapped.attributes.customBackgroundColor =
              item.params.background_color;
          }
        }
        if (item.params && has(item.params, 'font_size')) {
          const slug = mapFontSizeSlug(
            fontSizeDefinitions,
            item.params.font_size,
          );
          mapped.attributes.fontSize = slug;
          mapped.attributes.style.typography.fontSize = !slug
            ? asNum(item.params.font_size)
            : undefined;
        }
        if (item.params && has(item.params, 'line_height')) {
          mapped.attributes.style.typography.lineHeight =
            item.params.line_height;
        }
        let level = 2;
        switch (item.id) {
          case 'email':
            return {
              ...mapped,
              name: 'mailpoet-form/email-input',
              attributes: {
                ...mapped.attributes,
                styles: mapInputBlockStyles(item.styles),
              },
            };
          case 'heading':
            if (item.params && has(item.params, 'level')) {
              level = asNum(item.params.level);
              if (level === undefined) {
                level = 2;
              }
            }
            return {
              ...mapped,
              attributes: {
                ...mapped.attributes,
                content: item.params?.content || '',
                level,
                textAlign: item.params?.align,
                anchor: item.params?.anchor,
                className: item.params?.class_name,
              },
              name: 'core/heading',
            };
          case 'paragraph':
            return {
              ...mapped,
              attributes: {
                ...mapped.attributes,
                content: item.params?.content || '',
                align: item.params?.align,
                className: item.params?.class_name,
                dropCap: item.params?.drop_cap === '1',
              },
              name: 'core/paragraph',
            };
          case 'image':
            return {
              ...mapped,
              name: 'core/image',
              attributes: {
                className: item.params?.class_name || '',
                align: item.params?.align,
                url: item.params?.url,
                alt: item.params?.alt,
                title: item.params?.title,
                caption: item.params?.caption,
                linkDestination: item.params?.link_destination,
                link: item.params?.link,
                href: item.params?.href,
                linkClass: item.params?.link_class,
                rel: item.params?.rel,
                linkTarget: item.params?.link_target,
                id: item.params?.id,
                sizeSlug: item.params?.size_slug,
                width: item.params?.width,
                height: item.params?.height,
              },
            };
          case 'first_name':
            return {
              ...mapped,
              name: 'mailpoet-form/first-name-input',
              attributes: {
                ...mapped.attributes,
                styles: mapInputBlockStyles(item.styles),
              },
            };
          case 'last_name':
            return {
              ...mapped,
              name: 'mailpoet-form/last-name-input',
              attributes: {
                ...mapped.attributes,
                styles: mapInputBlockStyles(item.styles),
              },
            };
          case 'segments':
            if (
              item.params &&
              has(item.params, 'values') &&
              Array.isArray(item.params.values)
            ) {
              mapped.attributes.values = item.params.values.map((value) => ({
                id: value.id,
                isChecked: value.is_checked === '1' ? true : undefined,
              }));
            } else {
              mapped.attributes.values = [];
            }
            return {
              ...mapped,
              name: 'mailpoet-form/segment-select',
            };
          case 'submit':
            return {
              ...mapped,
              name: 'mailpoet-form/submit-button',
              attributes: {
                ...mapped.attributes,
                styles: mapInputBlockStyles(item.styles),
              },
            };
          case 'divider':
            delete mapped.attributes.label;
            return {
              ...mapped,
              name: 'mailpoet-form/divider',
              attributes: {
                className: mapped.attributes.className,
                height: asNum(
                  item.params?.height ?? dividerDefaultAttributes.height,
                ),
                type: item.params?.type ?? dividerDefaultAttributes.type,
                style: item.params?.style ?? dividerDefaultAttributes.style,
                dividerHeight: asNum(
                  item.params?.divider_height ??
                    dividerDefaultAttributes.dividerHeight,
                ),
                dividerWidth: asNum(
                  item.params?.divider_width ??
                    dividerDefaultAttributes.dividerWidth,
                ),
                color: item.params?.color ?? dividerDefaultAttributes.color,
              },
            };
          case 'html':
            return {
              ...mapped,
              name: 'mailpoet-form/html',
              attributes: {
                className: mapped.attributes.className,
                content:
                  item.params && item.params.text ? item.params.text : '',
                nl2br:
                  item.params && item.params.nl2br
                    ? !!item.params.nl2br
                    : false,
              },
            };
          default:
            if (Number.isInteger(parseInt(item.id, 10))) {
              return mapCustomField(item, customFields, mapped);
            }
            return null;
        }
      })
      .filter(Boolean);
  };

  return formBodyToBlocks;
};
