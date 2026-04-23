#!/usr/bin/env node
// Generates the companion secret file if missing and prints its path.
// Run this once before `pnpm env:start` so wp-env can map the secret into the container.

import { loadConfig } from "./config.js";

const config = loadConfig();
process.stdout.write(
  JSON.stringify(
    {
      ok: true,
      secret_path: config.companionSecretPath,
      companion_url: config.companionBaseUrl,
      note: "Secret generated or already present. Start wp-env next to mount it into the container.",
    },
    null,
    2,
  ) + "\n",
);
