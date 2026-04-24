import { resolve, relative, extname } from 'node:path';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import type { Config } from '../config.js';
import { exec } from '../util/exec.js';
import { ToolError } from '../util/errors.js';
import { runHandler } from './register.js';

const QaTool = z.enum([
  'phpstan',
  'phpcs',
  'eslint',
  'stylelint',
  'prettier',
  'tsc',
]);
type QaTool = z.infer<typeof QaTool>;
const QaScope = z.enum(['all', 'changed', 'file']);

type Severity = 'error' | 'warning' | 'info';

interface Violation {
  tool: QaTool;
  file: string; // relative to repo root
  line: number;
  column: number | null;
  rule: string;
  severity: Severity;
  message: string;
  fixable: boolean;
}

const EXTENSIONS_BY_TOOL: Record<QaTool, string[]> = {
  phpstan: ['.php'],
  phpcs: ['.php'],
  eslint: ['.js', '.jsx', '.ts', '.tsx'],
  stylelint: ['.scss', '.css'],
  prettier: [
    '.js',
    '.jsx',
    '.ts',
    '.tsx',
    '.scss',
    '.css',
    '.json',
    '.md',
    '.yml',
    '.yaml',
    '.html',
  ],
  tsc: [],
};

async function changedFiles(
  repoRoot: string,
  extensions: string[],
): Promise<string[]> {
  const res = await exec(
    'git',
    ['diff', '--name-only', '--diff-filter=ACMR', 'trunk...HEAD'],
    { cwd: repoRoot },
  );
  if (res.exitCode !== 0) {
    throw new ToolError(
      'git_diff_failed',
      `git diff failed: ${res.stderr.trim() || res.stdout.trim()}`,
    );
  }
  const files = res.stdout
    .split('\n')
    .map((l) => l.trim())
    .filter(Boolean);
  if (extensions.length === 0) return files;
  return files.filter((f) => extensions.includes(extname(f)));
}

async function uncommittedFiles(
  repoRoot: string,
  extensions: string[],
): Promise<string[]> {
  const res = await exec('git', ['status', '--porcelain'], { cwd: repoRoot });
  if (res.exitCode !== 0) return [];
  // git status --porcelain format:
  //   XY path          — standard changes (X = staged, Y = unstaged)
  //   R  old -> new    — rename (index or worktree)
  //   C  old -> new    — copy
  //   D  path          — deletion — skip, file is gone
  // We want the current path on disk, excluding deletions.
  const files: string[] = [];
  for (const rawLine of res.stdout.split('\n')) {
    if (rawLine.length < 4) continue;
    const x = rawLine[0];
    const y = rawLine[1];
    // Skip deletions in either index or worktree — no file to lint.
    if (x === 'D' || y === 'D') continue;
    const rest = rawLine.slice(3).trim();
    if (!rest) continue;
    // Rename / copy keep the new name (after ' -> ').
    if (x === 'R' || x === 'C') {
      const idx = rest.indexOf(' -> ');
      files.push(idx === -1 ? rest : rest.slice(idx + 4).trim());
    } else {
      files.push(rest);
    }
  }
  if (extensions.length === 0) return files;
  return files.filter((f) => extensions.includes(extname(f)));
}

// Per-tool scope filter. Changed-file discovery returns every modified file
// matching the tool's extension, but some tools can't meaningfully analyze
// files outside their configured scope (e.g. phpstan without WordPress
// stubs, eslint paths ignored by the mailpoet config). Filtering up front
// keeps `files_analyzed` accurate in the response.
function scopeFilter(tool: QaTool): (repoRel: string) => boolean {
  if (tool === 'phpstan') return isPhpstanAnalyzable;
  if (tool === 'phpcs') return isPhpcsAnalyzable;
  if (tool === 'eslint') return isEslintAnalyzable;
  return () => true;
}

async function resolveScope(
  scope: 'all' | 'changed' | 'file',
  path: string | undefined,
  tool: QaTool,
  config: Config,
): Promise<{ files: string[] | 'all'; resolvedFromBranch: boolean }> {
  if (scope === 'all') return { files: 'all', resolvedFromBranch: false };
  if (scope === 'file') {
    if (!path)
      throw new ToolError(
        'path_required',
        "scope='file' requires a 'path' argument.",
      );
    // Defensive: don't let an agent point a linter at ../../etc/passwd.
    // spawn() has no shell, and linters are read-only, so the practical
    // blast radius is nil — but this keeps the invariant obvious.
    const absolute = resolve(config.repoRoot, path);
    const repoRootWithSep = config.repoRoot.endsWith('/')
      ? config.repoRoot
      : config.repoRoot + '/';
    if (absolute !== config.repoRoot && !absolute.startsWith(repoRootWithSep)) {
      throw new ToolError(
        'path_outside_repo',
        `Path '${path}' resolves outside the repo root (${config.repoRoot}).`,
      );
    }
    return { files: [path], resolvedFromBranch: false };
  }
  const exts = EXTENSIONS_BY_TOOL[tool];
  const committed = await changedFiles(config.repoRoot, exts);
  const uncommitted = await uncommittedFiles(config.repoRoot, exts);
  const merged = Array.from(new Set([...committed, ...uncommitted])).filter(
    scopeFilter(tool),
  );
  return { files: merged, resolvedFromBranch: true };
}

function toAbsolute(path: string, config: Config): string {
  return resolve(config.repoRoot, path);
}

function toRepoRel(absOrRel: string, config: Config): string {
  return relative(config.repoRoot, resolve(config.repoRoot, absOrRel));
}

// When a linter dies before writing any JSON (PHPStan OOM, missing config
// file, etc.), its real error usually lands on stderr. Surface that in the
// tool error message so the agent doesn't see a useless "output was not JSON".
function badJsonError(
  code: string,
  toolLabel: string,
  res: { stdout: string; stderr: string; exitCode: number },
): ToolError {
  const stdoutTrim = res.stdout.trim();
  const stderrTrim = res.stderr.trim();
  const hint =
    stdoutTrim === '' && stderrTrim !== ''
      ? `${toolLabel} produced no stdout. stderr: ${stderrTrim.slice(0, 300)}`
      : `${toolLabel} output was not JSON.`;
  return new ToolError(code, hint, {
    exit_code: res.exitCode,
    stdout: res.stdout.slice(0, 500),
    stderr: res.stderr.slice(0, 500),
  });
}

// PHPStan's config (mailpoet/tasks/phpstan/phpstan.neon) only sets up stubs
// and autoload for plugin code. Files outside mailpoet/ (e.g. .wp-env/
// mu-plugins/) produce hundreds of false 'class.notFound' errors because
// WordPress stubs aren't registered for them. Match RoboFile's scope.
function isPhpstanAnalyzable(repoRel: string): boolean {
  return (
    repoRel.startsWith('mailpoet/lib/') ||
    repoRel.startsWith('mailpoet/tests/_support/') ||
    repoRel.startsWith('mailpoet/tests/DataFactories/') ||
    repoRel.startsWith('mailpoet/tests/acceptance/') ||
    repoRel.startsWith('mailpoet/tests/integration/') ||
    repoRel.startsWith('mailpoet/tests/unit/')
  );
}

// PHPCS's MailPoet ruleset targets lib/ and tests/. Linting a PHP file
// outside that scope doesn't make sense — the ruleset can't find project
// context for it.
function isPhpcsAnalyzable(repoRel: string): boolean {
  return (
    repoRel.startsWith('mailpoet/lib/') || repoRel.startsWith('mailpoet/tests/')
  );
}

// mailpoet's eslint config ignores paths like tools/mcp-server/. Passing
// ignored files to eslint produces one "File ignored" warning per file —
// noise that isn't actionable. Skip these paths up front.
function isEslintAnalyzable(repoRel: string): boolean {
  return !repoRel.startsWith('tools/mcp-server/');
}

async function runPhpstan(
  files: string[] | 'all',
  config: Config,
): Promise<Violation[]> {
  // PHPStan must run from mailpoet/tasks/phpstan/ so it picks up
  // phpstan.neon there and avoids autoloading conflicts from the plugin's
  // own vendor/ (see RoboFile::qaPhpstan for the same reasoning).
  const phpstanDir = resolve(config.mailpoetDir, 'tasks/phpstan');
  const phpstan = './vendor/bin/phpstan';
  const args = [
    'analyse',
    '--no-progress',
    '--error-format=json',
    '--memory-limit=2G',
  ];
  if (files !== 'all') {
    if (files.length === 0) return [];
    args.push(...files.map((f) => toAbsolute(f, config)));
  }
  const res = await exec(phpstan, args, {
    cwd: phpstanDir,
    timeoutMs: 5 * 60 * 1000,
  });
  // PHPStan exits 1 when it finds violations; that's expected.
  let parsed: {
    files: Record<
      string,
      {
        messages: {
          message: string;
          line: number;
          ignorable?: boolean;
          identifier?: string;
        }[];
      }
    >;
  };
  try {
    parsed = JSON.parse(res.stdout);
  } catch {
    throw badJsonError('phpstan_bad_json', 'PHPStan', res);
  }
  const out: Violation[] = [];
  for (const [file, fileData] of Object.entries(parsed.files ?? {})) {
    for (const msg of fileData.messages) {
      out.push({
        tool: 'phpstan',
        file: toRepoRel(file, config),
        line: msg.line ?? 0,
        column: null,
        rule: msg.identifier || 'phpstan',
        severity: 'error',
        message: msg.message,
        fixable: false,
      });
    }
  }
  return out;
}

async function runPhpcs(
  files: string[] | 'all',
  config: Config,
): Promise<Violation[]> {
  const phpcs = './tasks/code_sniffer/vendor/bin/phpcs';
  const args = [
    '--report=json',
    '--standard=./tasks/code_sniffer/MailPoet/free-ruleset.xml',
  ];
  if (files !== 'all') {
    if (files.length === 0) return [];
    args.push(
      ...files.map((f) => relative(config.mailpoetDir, toAbsolute(f, config))),
    );
  } else {
    args.push('lib/', 'tests/');
  }
  const res = await exec(phpcs, args, {
    cwd: config.mailpoetDir,
    timeoutMs: 5 * 60 * 1000,
  });
  let parsed: {
    files: Record<
      string,
      {
        messages: {
          message: string;
          source: string;
          line: number;
          column: number;
          type: string;
          fixable: boolean;
        }[];
      }
    >;
  };
  try {
    parsed = JSON.parse(res.stdout);
  } catch {
    throw badJsonError('phpcs_bad_json', 'PHPCS', res);
  }
  const out: Violation[] = [];
  for (const [file, fileData] of Object.entries(parsed.files ?? {})) {
    for (const m of fileData.messages) {
      out.push({
        tool: 'phpcs',
        file: toRepoRel(file, config),
        line: m.line,
        column: m.column,
        rule: m.source,
        severity:
          m.type === 'ERROR'
            ? 'error'
            : m.type === 'WARNING'
            ? 'warning'
            : 'info',
        message: m.message,
        fixable: !!m.fixable,
      });
    }
  }
  return out;
}

async function runEslint(
  files: string[] | 'all',
  config: Config,
): Promise<Violation[]> {
  const args = ['--format=json', '--max-warnings', '9999'];
  if (files !== 'all') {
    if (files.length === 0) return [];
    args.push(
      ...files.map((f) => relative(config.mailpoetDir, toAbsolute(f, config))),
    );
  } else {
    args.push('assets/js/src/**/*.{js,jsx,ts,tsx}');
  }
  const res = await exec('./node_modules/.bin/eslint', args, {
    cwd: config.mailpoetDir,
    timeoutMs: 5 * 60 * 1000,
    env: { ...process.env, NODE_OPTIONS: '--max_old_space_size=2048' },
  });
  let parsed: {
    filePath: string;
    messages: {
      ruleId: string | null;
      severity: number;
      message: string;
      line: number;
      column: number;
      fix?: unknown;
    }[];
  }[];
  try {
    parsed = JSON.parse(res.stdout);
  } catch {
    throw badJsonError('eslint_bad_json', 'ESLint', res);
  }
  const out: Violation[] = [];
  for (const fileReport of parsed) {
    for (const m of fileReport.messages) {
      out.push({
        tool: 'eslint',
        file: toRepoRel(fileReport.filePath, config),
        line: m.line,
        column: m.column,
        rule: m.ruleId ?? 'eslint',
        severity: m.severity === 2 ? 'error' : 'warning',
        message: m.message,
        fixable: !!m.fix,
      });
    }
  }
  return out;
}

async function runStylelint(
  files: string[] | 'all',
  config: Config,
): Promise<Violation[]> {
  const args = ['--formatter=json'];
  if (files !== 'all') {
    if (files.length === 0) return [];
    args.push(
      ...files.map((f) => relative(config.mailpoetDir, toAbsolute(f, config))),
    );
  } else {
    args.push('assets/css/src/**/*.scss');
  }
  const res = await exec('./node_modules/.bin/stylelint', args, {
    cwd: config.mailpoetDir,
    timeoutMs: 5 * 60 * 1000,
  });
  let parsed: {
    source: string;
    warnings: {
      line: number;
      column: number;
      rule: string;
      severity: string;
      text: string;
    }[];
  }[];
  try {
    parsed = JSON.parse(res.stdout || '[]');
  } catch {
    throw badJsonError('stylelint_bad_json', 'Stylelint', res);
  }
  const out: Violation[] = [];
  for (const file of parsed) {
    for (const w of file.warnings) {
      out.push({
        tool: 'stylelint',
        file: toRepoRel(file.source, config),
        line: w.line,
        column: w.column,
        rule: w.rule,
        severity: w.severity === 'error' ? 'error' : 'warning',
        message: w.text,
        fixable: false,
      });
    }
  }
  return out;
}

async function runPrettier(
  files: string[] | 'all',
  config: Config,
): Promise<Violation[]> {
  const args = ['--list-different'];
  if (files !== 'all') {
    if (files.length === 0) return [];
    args.push(...files);
  } else {
    args.push('.');
  }
  const res = await exec('npx', ['prettier', ...args], {
    cwd: config.repoRoot,
    timeoutMs: 5 * 60 * 1000,
  });
  const out: Violation[] = [];
  for (const line of res.stdout.split('\n')) {
    const trimmed = line.trim();
    if (!trimmed) continue;
    out.push({
      tool: 'prettier',
      file: toRepoRel(trimmed, config),
      line: 0,
      column: null,
      rule: 'prettier/formatting',
      severity: 'warning',
      message: 'File is not Prettier-formatted.',
      fixable: true,
    });
  }
  return out;
}

async function runTsc(config: Config): Promise<Violation[]> {
  const res = await exec(
    './node_modules/.bin/tsc',
    ['--noEmit', '--pretty', 'false'],
    {
      cwd: config.mailpoetDir,
      timeoutMs: 5 * 60 * 1000,
      env: { ...process.env, NODE_OPTIONS: '--max_old_space_size=2048' },
    },
  );
  const out: Violation[] = [];
  // TSC line format: "path/to/file.ts(line,col): error TSxxxx: message"
  const re = /^(.+?)\((\d+),(\d+)\):\s+(error|warning)\s+(TS\d+):\s+(.+)$/;
  for (const line of res.stdout.split('\n')) {
    const m = re.exec(line.trim());
    if (!m) continue;
    out.push({
      tool: 'tsc',
      file: toRepoRel(resolve(config.mailpoetDir, m[1]!), config),
      line: Number(m[2]),
      column: Number(m[3]),
      rule: m[5]!,
      severity: m[4] === 'error' ? 'error' : 'warning',
      message: m[6]!,
      fixable: false,
    });
  }
  return out;
}

export function registerQaRun(server: McpServer, config: Config): void {
  server.registerTool(
    'mp.qa.run',
    {
      title: 'Run a QA / lint tool',
      description:
        "Runs one of the MailPoet QA tools (phpstan, phpcs, eslint, stylelint, prettier, tsc) and returns a flat list of structured violations. Default scope='changed' runs against files modified vs trunk (committed + uncommitted). scope='file' requires 'path'. scope='all' runs everywhere (slow).",
      inputSchema: {
        tool: QaTool.describe('Which linter / static analyser to run.'),
        scope: QaScope.optional().describe(
          "'changed' (default): files changed vs trunk + uncommitted. 'file': single path. 'all': everywhere.",
        ),
        path: z
          .string()
          .optional()
          .describe("Path relative to repo root. Required when scope='file'."),
      },
      annotations: { readOnlyHint: true, openWorldHint: false },
    },
    async (args) =>
      runHandler('mp.qa.run', args, async () => {
        const tool = args.tool;
        const scope = args.scope ?? 'changed';

        const { files, resolvedFromBranch } = await resolveScope(
          scope,
          args.path,
          tool,
          config,
        );
        const started = Date.now();

        let violations: Violation[];
        if (tool === 'phpstan') violations = await runPhpstan(files, config);
        else if (tool === 'phpcs') violations = await runPhpcs(files, config);
        else if (tool === 'eslint') violations = await runEslint(files, config);
        else if (tool === 'stylelint')
          violations = await runStylelint(files, config);
        else if (tool === 'prettier')
          violations = await runPrettier(files, config);
        else violations = await runTsc(config);

        const errors = violations.filter((v) => v.severity === 'error').length;
        const warnings = violations.filter(
          (v) => v.severity === 'warning',
        ).length;
        const fixable = violations.filter((v) => v.fixable).length;

        return {
          tool,
          scope,
          resolved_from_branch: resolvedFromBranch,
          files_analyzed: files === 'all' ? null : files.length,
          duration_ms: Date.now() - started,
          violations,
          summary: { errors, warnings, fixable },
        };
      }),
  );
}
