import { MailPoet } from 'mailpoet';
import { isErrorResponse } from '../../ajax';

export async function callApi<D = unknown, M = unknown>(
  actionData,
): Promise<{
  success: boolean;
  res: { data: D; meta?: M };
  error?: unknown;
}> {
  const { endpoint, action, data } = actionData;
  try {
    const res = await MailPoet.Ajax.post<{ data: D; meta?: M }>({
      api_version: MailPoet.apiVersion,
      endpoint,
      action,
      data,
    });
    return { success: true, res };
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } catch (res: any) {
    const error = isErrorResponse(res)
      ? res.errors.map((e) => e.message)
      : null;
    return { success: false, error, res };
  }
}
