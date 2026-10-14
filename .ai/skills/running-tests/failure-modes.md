# Common failure modes

Part of the `running-tests` skill.

| Symptom                                                 | Likely cause / fix                                                                                                                                                                        |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| “Cannot connect to the Docker daemon” on a Docker suite | Docker Desktop / OrbStack isn’t running. Start it, then re-run.                                                                                                                           |
| First integration/acceptance run takes minutes to start | First-time container build/pull. Tail the test stack from the repo root: `docker compose -f tests_env/docker/docker-compose.yml logs -f` (not `pnpm env:logs` — that’s the wp-env stack). |
| `vendor-prefixed/` disappeared after a test run         | Ran `./do test:*` without `--skip-deps` on PHP 8.4+. Recover with `pnpm bootstrap`.                                                                                                       |
| Acceptance run starts but every test fails at login     | Test WordPress site is in a broken state. `cd mailpoet && ./do reset:test-docker` drops the test volumes (keeps images) and rebuilds.                                                     |
| Docker is genuinely confused (zombies, weird state)     | `cd mailpoet && ./do delete:docker` does the harder reset: stops containers, drops volumes, **removes images** (next run will re-pull/build, ~1–2min). Does not touch the wp-env stack.   |
| JS test errors with `X is not a function` for real code | `assets/dist/` is stale. Run `pnpm compile:js` and re-run.                                                                                                                                |
| WC-related integration failures (orders, HPOS)          | Toggle HPOS for the run: `--enable-hpos`, `--disable-hpos`, or `--enable-hpos-sync`.                                                                                                      |
| “Unknown option --file” or similar                      | Wrong wrapper for the suite (e.g. JavaScript has no `--file`). Check the Quick Reference in [SKILL.md](SKILL.md).                                                                         |
| Test passes locally but failed on CI                    | Hand off to [[debugging-failed-tests]] with the CI job URL, branch name, failing test path, and any WP/WC version overrides the CI run used.                                              |

## Interrupting a long run

Ctrl-C is fine, but it can leave Selenium / Chrome containers running for an acceptance run. They usually self-correct on the next invocation; if things look off, `./do reset:test-docker` is the soft reset and `./do delete:docker` is the hard one.
