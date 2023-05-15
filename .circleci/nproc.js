import * as fs from 'fs/promises';
import * as os from 'os';

// Get logical CPU count for a container on CircleCI. Adapted from:
//   https://circleci.canny.io/cloud-feature-requests/p/have-nproc-accurately-reporter-number-of-cpus-available-to-container

async function cgroupCpuCount() {
  const quotaS = await fs.readFile('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
  const periodS = await fs.readFile('/sys/fs/cgroup/cpu/cpu.cfs_period_us');
  const quota = parseInt(quotaS);
  const period = parseInt(periodS);
  return quota / period;
}

async function cpuCount() {
  try {
    return await cgroupCpuCount();
  } catch {
    return os.cpus().length;
  }
}

cpuCount().then(console.log);
