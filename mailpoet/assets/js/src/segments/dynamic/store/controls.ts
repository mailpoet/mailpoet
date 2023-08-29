import { MailPoet } from 'mailpoet';
import { assign, has } from 'lodash/fp';

import { AnyFormItem, Segment } from '../types';
import { isErrorResponse } from '../../../ajax';

function convertSavedData(data: Record<string, string | number>): AnyFormItem {
  let converted: AnyFormItem = JSON.parse(JSON.stringify(data));
  // for compatibility with older data
  if (has('link_id', data))
    converted = assign(converted, { link_id: data.link_id.toString() });
  if (has('newsletter_id', data))
    converted = assign(converted, {
      newsletter_id: data.newsletter_id.toString(),
    });
  if (has('product_id', data))
    converted = assign(converted, { product_id: data.product_id.toString() });
  if (has('category_id', data))
    converted = assign(converted, { category_id: data.category_id.toString() });
  return converted;
}

// eslint-disable-next-line @typescript-eslint/naming-convention
export async function LOAD_SEGMENT(actionData): Promise<{
  success: boolean;
  res?: AnyFormItem;
  error?: string[];
}> {
  const segmentId: number = actionData.segmentId;
  try {
    const res = await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'dynamic_segments',
      action: 'get',
      data: {
        id: segmentId,
      },
    });
    return {
      success: true,
      res: convertSavedData(res.data as Record<string, string | number>),
    };
  } catch (res) {
    const error = isErrorResponse(res)
      ? res.errors.map((e) => e.message)
      : null;
    return { success: false, error, res };
  }
}

// eslint-disable-next-line @typescript-eslint/naming-convention
export async function SAVE_SEGMENT(actionData): Promise<{
  success: boolean;
  error?: string[];
  data?: unknown;
}> {
  const segment: Segment = actionData.segment;
  try {
    const response = await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'dynamic_segments',
      action: 'save',
      data: segment,
    });

    segment.id = response.data.id;
    segment.name = response.data.name;

    return {
      success: true,
      data: response.data,
    };
  } catch (res) {
    const error = isErrorResponse(res)
      ? res.errors.map((e) => e.message)
      : null;
    return { success: false, error };
  }
}
