export class ToolError extends Error {
  readonly code: string;
  readonly data?: unknown;

  constructor(code: string, message: string, data?: unknown) {
    super(message);
    this.code = code;
    this.data = data;
  }
}

export function toToolResult(err: unknown): { isError: true; content: { type: "text"; text: string }[] } {
  if (err instanceof ToolError) {
    return {
      isError: true,
      content: [
        {
          type: "text",
          text: JSON.stringify({ error: { code: err.code, message: err.message, data: err.data } }, null, 2),
        },
      ],
    };
  }
  const message = err instanceof Error ? err.message : String(err);
  return {
    isError: true,
    content: [{ type: "text", text: JSON.stringify({ error: { code: "unknown", message } }, null, 2) }],
  };
}
