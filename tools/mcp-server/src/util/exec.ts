import { spawn } from "node:child_process";
import type { SpawnOptionsWithoutStdio } from "node:child_process";

export interface ExecResult {
  exitCode: number;
  stdout: string;
  stderr: string;
  duration_ms: number;
}

export interface ExecOptions extends SpawnOptionsWithoutStdio {
  timeoutMs?: number;
  input?: string;
}

export function exec(command: string, args: string[], options: ExecOptions = {}): Promise<ExecResult> {
  const { timeoutMs, input, ...spawnOpts } = options;
  return new Promise((resolvePromise, reject) => {
    const started = Date.now();
    const child = spawn(command, args, { ...spawnOpts, stdio: ["pipe", "pipe", "pipe"] });
    let stdout = "";
    let stderr = "";
    let timedOut = false;

    child.stdout.on("data", (chunk: Buffer) => {
      stdout += chunk.toString("utf8");
    });
    child.stderr.on("data", (chunk: Buffer) => {
      stderr += chunk.toString("utf8");
    });

    const timer = timeoutMs
      ? setTimeout(() => {
          timedOut = true;
          child.kill("SIGTERM");
        }, timeoutMs)
      : null;

    child.on("error", (err) => {
      if (timer) clearTimeout(timer);
      reject(err);
    });

    child.on("close", (code) => {
      if (timer) clearTimeout(timer);
      if (timedOut) {
        reject(new Error(`Command timed out after ${timeoutMs}ms: ${command} ${args.join(" ")}`));
        return;
      }
      resolvePromise({
        exitCode: code ?? -1,
        stdout,
        stderr,
        duration_ms: Date.now() - started,
      });
    });

    if (input !== undefined) {
      child.stdin.write(input);
      child.stdin.end();
    } else {
      child.stdin.end();
    }
  });
}
