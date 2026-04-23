import { appendFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

export interface TelemetryEvent {
  ts: string; // ISO8601 UTC
  tool: string; // e.g. "mp.env.status"
  duration_ms: number;
  status: 'ok' | 'error';
  error_code?: string;
  input_keys: string[]; // keys of the arguments object, not values (avoid leaking secrets/PII)
}

let logPath: string | null = null;
let warnedOnce = false;

export function initTelemetry(path: string): void {
  logPath = path;
  try {
    mkdirSync(dirname(path), { recursive: true });
  } catch {
    // swallow — logging must never kill the server
  }
}

export function recordToolCall(event: TelemetryEvent): void {
  if (!logPath) return;
  try {
    appendFileSync(logPath, JSON.stringify(event) + '\n');
  } catch (e) {
    if (!warnedOnce) {
      warnedOnce = true;
      process.stderr.write(
        `[mailpoet-dev-mcp] telemetry write failed: ${
          e instanceof Error ? e.message : String(e)
        }\n`,
      );
    }
  }
}
