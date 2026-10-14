# Performance tests

Part of the `running-tests` skill.

k6 + Playwright, Docker-backed. Free plugin only. Runs against a live MailPoet instance you supply (the test is the load generator, not a fixture). Three-step lifecycle:

```bash
cd mailpoet
./do test:performance-setup                                       # one-time prep (seed data, etc.)
./do test:performance --url=<wp-url> --us=<user> --pw=<pass>      # actual run
./do test:performance-clean                                       # tear down test data
```

Optional flags on `test:performance`:

- `--head` — run the Playwright browser headed (default headless).
- `--scenario=<name>` — restrict to one scenario from `mailpoet/tests/performance/scenarios.js`.
- Positional `<path>` (before `--`) — point at a different scenarios file.

Cloud mode kicks in automatically when `K6_CLOUD_TOKEN` is set; results upload to k6 Cloud instead of printing locally. Full signature: `testPerformance` in `mailpoet/RoboFile.php`.
