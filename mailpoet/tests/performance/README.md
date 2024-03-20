# MailPoet Performance Tests

Automated k6 performance tests for MailPoet. To be used for benchmarking performance (both single user and under load) by simulating and measuring the response time of browser-level and protocol-lever tests (protocol-level yet to be implemented).

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Installing k6](#installing-k6)
- [Configuration](#configuration)
  - [Test Environment](#test-environment)
  - [Config Variables](#config-variables)
  - [Config Variables List](#config-variables-list)
- [Running Tests](#running-tests)
  - [Running Individual Tests](#running-individual-tests)
  - [Running Scenarios](#running-scenarios)
  - [Debugging Tests](#debugging-tests)
- [Results Output](#results-output)
  - [Default Terminal Output](#default-terminal-output)
  - [HTML Output with k6 Reporter](#html-output-with-k6-reporter)
- [Writing Tests](#writing-tests)
  - [Capturing Requests](#capturing-requests)
  - [Request Headers For Protocol-level Tests](#request-headers-for-protocol-level-tests)
  - [Groups](#groups)
  - [Checks](#checks)
- [Other Resources](#other-resources)

## Pre-requisites

### Installing k6

You don't need to install it - it's automatic!

To execute the tests locally, check the below instructions under [Running Scenarios](#running-scenarios)

The k6 installation is automatic. It will download the binary file in `/tools/vendor/` folder. Associated file for auto installing is `/tools/k6.php`.

## Configuration

### Test Environment

This test suite is using original MailPoet's environment with installed WordPress, MySQL and stuff like that.

The local env's url of the performance test site is: `https://localhost:9500`

When you're done with testing, you may want to stop the environment by the following command:

`./do test:performance-clean`

### Config Variables

`config.js` comes with some test data and \_\_ENV values stored in your .env file. You don't need to change the values there if you want to use different test site, user and password, but just include parameters when executing the test such as:
`./do test:performance --scenario pullrequests --url="yoururl" --pw="yourpassword"`

#### Config Variables List

These are some of the very important variables:
`baseURL` = this is the test site url
`scenario` = this is used for performing different scenarios, currently supported `pull requests` and `nightlytests` only
`projectName` = this is used for streaming results to Grafana Cloud k6. No need to use if testing locally, this is for CI.
`k6CloudID` = this is used for streaming results to Grafana Cloud k6. Same as above.
`adminPassword` = this is used for logging to a test site, locally or against online test site.

### Grafana k6 Cloud

We use Grafana k6 Cloud to streamline our test results. This is happening only for the CI tests. However, you can even stream the local tests in case you need it.

For the Grafana k6 Cloud access, please refer to the secret store and search for MailPoet: Grafana

Currently, the local test env. checks if your `.env` file contain the token for Grafana `K6_CLOUD_TOKEN=`, and if not, streaming to Grafana won't happen. In case you add it, it will automatically stream (there's cloud token in CI saved so it streams there).
You can have `K6_CLOUD_ID=` applied in your `.env`, but keep the token var empty.

## Running Tests

When refering to running k6 tests usually it means executing some of the scenarios available. The test scenario file in turn determines which requests we run and what metrics and thresholds will be applied to them. It is also possible to execute individual test files containing requests and pass in scenario config as a CLI flag but scenario files allow for more configuration options.

### Running Individual Tests

To execute an individual test file (for example `newsletter-listing.js`):

`./do test:performance tests/performance/tests/newsletter-listing.js`

This will run the individual test for 1 iteration.

### Running Scenarios

To execute a test scenario for pull requests, as an example:

`./do test:performance --scenario pullrequests`

This will run scenario with associated tests and options specificed inside `scenarios.js`.

Included in the `tests` folder is a set of test scripts which includes all scenarios and that can be ran or used as a starting point to be modified to suit the context in which tests are being ran.

Another aspect that affects the traffic pattern of the tests is the amount of “Think Time” in between requests. In real world usage of an application users will spend some amount of time before doing some action. For some situations there will be a need to simulate this as part of the test traffic and it is common to have this as part of load tests.

To do this a sleep step is included between each request `` sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`)) ``.
The amount of think time can be controlled from `config.js`.

> **_Note for Protocol-level tests: It’s important to note to be very careful when adding load to a scenario. By accident a dangerous amount of load could be ran aginst the test environment that could effectively be like a denial-of-service attack on the test environment. Also important to consider any other consequences of running large load such as triggering of emails._**

### Debugging Tests

Browser-level tests: the easiest way is to turn off headless mode by running individual or scenario with this argument `--head` and it will execute test with browser. The example command would be:
`./do test:performance [test script path here] --head`

Protocol-level tests: to help with getting a test working, the `--http-debug="full"` flag prints to console the full log of requests and their responses. It is also useful to use `console.log()` to print messages when debugging.

## Results Output

### Default Terminal Output

When you run a single test script, there will be a default k6 output in the end, but without applied thresholds. Threshold are applied only on the `scenarios.js` script, so when running a set of test scripts.

### HTML Output with k6 Reporter

The k6 reporter should have already been implemented. There's generated file called `k6report.html` inside `performance` folder after every suite/scenario execution. You can find the path and open it in a local browser or see it as artefact in CircleCI.

[See this guide for more details](https://github.com/benc-uk/k6-reporter)

## Writing Tests

Start by adding a new file under `tests` folder and make sure it follows the name order with the other tests.

Then, you can see and copy/paste the imports from the other tests, as they might be the same... along with the comments on the top.

Then, begin with adding a new function called with your new test, for example:

`export async function yourNewTest() {`

and make sure to add browser and page constants.

Then you're ready to start your first test.

The very first step is to add the method `goto()`, here's an example:
`await page.goto(${baseURL}/wp-admin...` as you can see, we have `baseURL` here for the domain name and the rest is the page url where you want to land.

The second step is to add authenticating method, currently we use `authenticate(page)`.

The third step is to tak a screenshot (you can later add more in the test), we want to have screenshots for easier debugging especially in CI.
When adding screenshot name, please stay in line with other screenshots as they are stored altogether in a single folder `_screenshots`.
The screenshot name should consist of the test name, example: `Subscribers_Filtering_01.png`. Please add one in the beginning and at least one in the end, but also in between if the test is longer.

The rest of the test should contain a very simple E2E steps using k6 browser module, here are some links for the commands:
[Selecting elements](https://k6.io/docs/using-k6-browser/selecting-elements/), or checking [the full list](https://k6.io/docs/javascript-api/k6-experimental/browser/) of k6 browser possibilities.
Note: Because k6 does not run in NodeJS, the browser module APIs will slightly differ from their Playwright counterparts.

To make assertions, we are using `k6chai.js` library, here is an example how you should assert things:

```js
describe(subscribersPageTitle, () => {
    describe('should be able to see Lists Filter', () => {
      expect(page.locator('[data-automation-id="listing_filter_segment"]')).to
        .exist;
    });
```

In order to keep the output results in a clear view, we use `describe` to group them.

There's required `sleep` at the end, above is explanation in [Running Scenarios](#running-scenarios) section.

To complete and export the test to `scenarios.js`, you need to end it with `export default async function yourNewTestTest() {`.

After doing that, make sure to add the test in `scenarios.js` in appropriate section by pasting `yourNewTest()` function.

Finally, you need to check if your new test require any change to the existing options, thresholds, tags or maxDuration in `scenarios.js`.

That's it. Running the scenarios will include also your test. In `nightly()` scenario, you can include test that relies on premium, or even sending. These tests are executing against `mpperftesting.mystagingwebsite.com` website hosted on Pressable.com and have a premium key applied.

### Capturing Requests

Browser-level tests: k6 tests rely on Playwright technology to make E2E tests where then k6 measure performance with its metrics, tresholds and checks. There are also groups to divide one scenario intro parts like one group for creating newsletter and going through pages and the one for sending it, so we could measure performance of the whole scenario in single or multiple parts.

Protocol-level tests: k6 tests rely on HTTP requests in order to test the backend. They can either be constructed from scratch, by using the k6 recorder, or by converting a HAR file. The k6 recorder is a browser extension which captures http requests generated as you perform actions in a tab. It generates a test with all the HTTP requests from your actions which can then be modified to make it execuatable.

### Request Headers For Protocol-level Tests

Every HTTP requests tested includes the headers.

To make it easier to manage the headers they have been moved to a separate file so any changes can be made to all the requests at once.
In `headers.js` the common headers are grouped by type and then can be imported in for use in the requests. However if an individual request uniquely needs a specific header this can still be added in as an extra member of the headers object literal of that request.

If you don't see `headers.js` inside `performance` folder, that means we don't have yet protocol-level tests included. Feel free to ignore this section then.

### Groups (deprecated in our tests)

Groups are used to organize common logic in the test scripts and can help with the test result analysis. For example the `group` `"Proceed through the newsletter creation flow until Send step"` groups together multiple requests triggered by this action.

### Checks

There are checks inside the tests but they aren't failing tests, instead, they are more of a check that the process we use in tests are finished.

Currently, there are 36 checks in total for the nightly build, and 19 checks for the pull requests.

If you find less than this in our CI runs over to Grafana streaming, that means some of the tests are failing in execution and they require investigation, whether due to maintance or bug found.

## Other Resources

[k6 documention](https://k6.io/docs/) is a very useful resource for test creation and execution.
[using k6 browser doc](https://k6.io/docs/using-k6-browser/overview/) is a very useful resource for test creation and execution.
