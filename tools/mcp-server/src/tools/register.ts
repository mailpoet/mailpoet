import { toToolResult } from '../util/errors.js';
import { ToolError } from '../util/errors.js';
import { recordToolCall } from '../util/telemetry.js';

export type McpTextResult = {
  content: { type: 'text'; text: string }[];
  isError?: boolean;
};

export function toMcpResponse(result: unknown): McpTextResult {
  return {
    content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }],
  };
}

export async function runHandler(
  toolName: string,
  args: Record<string, unknown> | undefined,
  handler: () => Promise<unknown>,
): Promise<McpTextResult> {
  const started = Date.now();
  const inputKeys = args ? Object.keys(args) : [];
  try {
    const result = await handler();
    recordToolCall({
      ts: new Date().toISOString(),
      tool: toolName,
      duration_ms: Date.now() - started,
      status: 'ok',
      input_keys: inputKeys,
    });
    return toMcpResponse(result);
  } catch (err) {
    const errResult = toToolResult(err);
    recordToolCall({
      ts: new Date().toISOString(),
      tool: toolName,
      duration_ms: Date.now() - started,
      status: 'error',
      error_code: err instanceof ToolError ? err.code : 'unknown',
      input_keys: inputKeys,
    });
    return errResult;
  }
}
