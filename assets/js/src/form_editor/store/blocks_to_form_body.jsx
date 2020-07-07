import { has } from 'lodash';
import {
  mapInputBlockStyles,
  mapColorSlugToValue,
  mapFontSizeSlugToValue,
  mapGradientSlugToValue,
} from './mapping/from_blocks/styles_mapper';

const mapCustomField = (block, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === block.attributes.customFieldId);
  if (!customField) return null;
  const mapped = {
    ...mappedCommonProperties,
    id: block.attributes.customFieldId.toString(),
    name: customField.name,
  };
  if (block.name.startsWith('mailpoet-form/custom-date')) {
    mapped.type = 'date';
  }
  if (block.name.startsWith('mailpoet-form/custom-text')) {
    mapped.type = 'text';
    mapped.styles = mapInputBlockStyles(block.attributes.styles);
  }
  if (block.name.startsWith('mailpoet-form/custom-textarea')) {
    mapped.type = 'textarea';
    mapped.styles = mapInputBlockStyles(block.attributes.styles);
  }
  if (block.name.startsWith('mailpoet-form/custom-radio')) {
    mapped.type = 'radio';
  }
  if (block.name.startsWith('mailpoet-form/custom-checkbox')) {
    mapped.type = 'checkbox';
  }
  if (block.name.startsWith('mailpoet-form/custom-select')) {
    mapped.type = 'select';
  }
  if (has(block.attributes, 'validate')) {
    mapped.params.validate = block.attributes.validate;
  }
  if (has(block.attributes, 'hideLabel') && block.attributes.hideLabel) {
    mapped.params.hide_label = '1';
  }
  if (has(block.attributes, 'defaultToday') && block.attributes.defaultToday) {
    mapped.params.is_default_today = '1';
  }
  if (has(block.attributes, 'dateType')) {
    mapped.params.date_type = block.attributes.dateType;
  }
  if (has(block.attributes, 'lines')) {
    mapped.params.lines = block.attributes.lines;
  }
  if (has(block.attributes, 'dateFormat')) {
    mapped.params.date_format = block.attributes.dateFormat;
  }
  if (has(block.attributes, 'values')) {
    mapped.params.values = block.attributes.values.map((value) => {
      const mappedValue = {
        value: value.name,
      };
      if (has(value, 'isChecked') && value.isChecked) {
        mappedValue.is_checked = '1';
      }
      return mappedValue;
    });
  }
  return mapped;
};

/**
 * Factory for block to form data mapper
 * @param {Array.<{name: string, slug: string, size: number}>} fontSizeDefinitions
 * @param {Array.<{name: string, slug: string, color: string}>} colorDefinitions
 * @param {Array.<{name: string, slug: string, gradient: string}>} gradientDefinitions
 * @param customFields - list of all custom Fields
 */
const blocksToFormBodyFactory = (
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
  customFields
) => {
  if (!Array.isArray(customFields)) {
    throw new Error('Mapper expects customFields to be an array.');
  }

  /**
   * @param blocks
   * @param parent  - parent block of nested block
   * @returns {*}
   */
  const mapBlocks = (blocks, parent = null) => {
    if (!Array.isArray(blocks)) {
      throw new Error('Mapper expects blocks to be an array.');
    }

    return blocks.map((block) => {
      const mapped = {
        type: 'text',
        params: {
          label: block.attributes.label,
          class_name: block.attributes.className || null,
        },
      };
      if (block.attributes.mandatory) {
        mapped.params.required = '1';
      }
      if (block.attributes.labelWithinInput) {
        mapped.params.label_within = '1';
      }
      const childrenCount = parent ? parent.innerBlocks.length : 1;
      switch (block.name) {
        case 'core/heading':
          return {
            type: 'heading',
            id: 'heading',
            params: {
              content: block.attributes.content,
              level: block.attributes.level,
              align: block.attributes.align || 'left',
              font_size: mapFontSizeSlugToValue(
                fontSizeDefinitions,
                block.attributes.fontSize,
                block.attributes.style?.typography?.fontSize
              ),
              text_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.textColor,
                block.attributes.style?.color?.text
              ),
              line_height: block.attributes.style?.typography?.lineHeight,
              background_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.backgroundColor,
                block.attributes.style?.color?.background
              ),
              anchor: block.attributes.anchor || null,
              class_name: block.attributes.className || null,
            },
          };
        case 'core/paragraph':
          return {
            type: 'paragraph',
            id: 'paragraph',
            params: {
              content: block.attributes.content,
              drop_cap: block.attributes.dropCap ? '1' : '0',
              align: block.attributes.align || 'left',
              font_size: mapFontSizeSlugToValue(
                fontSizeDefinitions,
                block.attributes.fontSize,
                block.attributes.style?.typography?.fontSize
              ),
              line_height: block.attributes.style?.typography?.lineHeight,
              text_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.textColor,
                block.attributes.style?.color?.text
              ),
              background_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.backgroundColor,
                block.attributes.style?.color?.background
              ),
              class_name: block.attributes.className || null,
            },
          };
        case 'core/image':
          return {
            type: 'image',
            id: 'image',
            params: {
              class_name: block.attributes.className || null,
              align: block.attributes.align || null,
              url: block.attributes.url || null,
              alt: block.attributes.alt || null,
              title: block.attributes.title || null,
              caption: block.attributes.caption || null,
              link_destination: block.attributes.linkDestination || null,
              link: block.attributes.link || null,
              href: block.attributes.href || null,
              link_class: block.attributes.linkClass || null,
              rel: block.attributes.rel || null,
              link_target: block.attributes.linkTarget || null,
              id: block.attributes.id || null, // Image id
              size_slug: block.attributes.sizeSlug || null,
              width: block.attributes.width || null,
              height: block.attributes.height || null,
            },
          };
        case 'core/column':
          return {
            type: 'column',
            params: {
              class_name: block.attributes.className || null,
              vertical_alignment: block.attributes.verticalAlignment || null,
              width: block.attributes.width
                ? block.attributes.width : Math.round(100 / childrenCount),
            },
            body: mapBlocks(block.innerBlocks, block),
          };
        case 'core/columns':
          return {
            type: 'columns',
            body: mapBlocks(block.innerBlocks, block),
            params: {
              vertical_alignment: block.attributes.verticalAlignment || null,
              class_name: block.attributes.className || null,
              text_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.textColor,
                block.attributes.style?.color?.text
              ),
              background_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.backgroundColor,
                block.attributes.style?.color?.background
              ),
              gradient: mapGradientSlugToValue(
                gradientDefinitions,
                block.attributes.gradient,
                block.attributes.style?.color?.gradient
              ),
            },
          };
        case 'mailpoet-form/email-input':
          return {
            ...mapped,
            id: 'email',
            name: 'Email',
            params: {
              ...mapped.params,
              required: '1',
            },
            styles: mapInputBlockStyles(block.attributes.styles),
          };
        case 'mailpoet-form/first-name-input':
          return {
            ...mapped,
            id: 'first_name',
            name: 'First name',
            styles: mapInputBlockStyles(block.attributes.styles),
          };
        case 'mailpoet-form/last-name-input':
          return {
            ...mapped,
            id: 'last_name',
            name: 'Last name',
            styles: mapInputBlockStyles(block.attributes.styles),
          };
        case 'mailpoet-form/segment-select':
          return {
            ...mapped,
            id: 'segments',
            type: 'segment',
            name: 'List selection',
            params: {
              ...mapped.params,
              values: block.attributes.values.map((segment) => ({
                id: segment.id,
                is_checked: segment.isChecked ? '1' : undefined,
              })),
            },
          };
        case 'mailpoet-form/submit-button':
          return {
            ...mapped,
            id: 'submit',
            type: 'submit',
            name: 'Submit',
            styles: mapInputBlockStyles(block.attributes.styles),
          };
        case 'mailpoet-form/divider':
          return {
            ...mapped,
            id: 'divider',
            type: 'divider',
            name: 'Divider',
            params: {
              class_name: block.attributes.className || null,
              height: block.attributes.height,
              type: block.attributes.type,
              style: block.attributes.style,
              divider_height: block.attributes.dividerHeight,
              divider_width: block.attributes.dividerWidth,
              color: block.attributes.color,
            },
          };
        case 'mailpoet-form/html':
          return {
            ...mapped,
            id: 'html',
            type: 'html',
            name: 'Custom text or HTML',
            params: {
              text: block.attributes && block.attributes.content ? block.attributes.content : '',
              nl2br: block.attributes && block.attributes.nl2br ? '1' : '0',
              class_name: block.attributes.className || null,
            },
          };
        default:
          if (block.name.startsWith('mailpoet-form/custom-')) {
            return mapCustomField(block, customFields, mapped);
          }
          return null;
      }
    }).filter(Boolean);
  };
  return mapBlocks;
};

export default blocksToFormBodyFactory;
