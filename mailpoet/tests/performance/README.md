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

You don't need to install it - it's Automattic! :)

To execute the tests, use the following command:

`./do test:performance`

There k6 installation is automatic. It will download the binary file in `/tools/vendor/` folder. Associated file for auto installing is `/tools/xk6browser.php`.

## Configuration

### Test Environment (to be added)

TBD

### Config Variables

`config.js` comes with some example values for using with the suggested local test environment. If using a different environment be sure to update the values.

#### Config Variables List (to be added)

TBD

## Running Tests

When refering to running k6 tests usually this means executing the test scenario. The test scenario file in turn determines which requests we run and how much load will be applied to them. It is also possible to execute individual test files containing requests and pass in scenario config as a CLI flag but scenario files allow for more configuration options.

### Running Individual Tests

To execute an individual test file (for example `requests/wp-admin/newsletter-listing.js`):

`./do test:performance requests/wp-admin/newsletter-listing.js`

This will run the individual test for 1 iteration.

### Running Scenarios

To execute a test scenario for pull requests, as an example:

`./do test:performance --scenario pullrequests`

This will run scenario with associated tests and options specificed inside `scenarios.js`.

Included in the `tests` folder is a single test script which includes all scenarios and that can be ran or used as a starting point to be modified to suit the context in which tests are being ran.

Another aspect that affects the traffic pattern of the tests is the amount of “Think Time” in between requests. In real world usage of an application users will spend some amount of time before doing some action. For some situations there will be a need to simulate this as part of the test traffic and it is common to have this as part of load tests.

To do this a sleep step is included between each request `` sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`)) ``.
The amount of think time can be controlled from `config.js`.

> **_Note for Protocol-level tests: It’s important to note to be very careful when adding load to a scenario. By accident a dangerous amount of load could be ran aginst the test environment that could effectively be like a denial-of-service attack on the test environment. Also important to consider any other consequences of running large load such as triggering of emails._**

### Debugging Tests

Browser-level tests: the easiest way is to turn off headless mode by running individual or scenario with this argument `--head` and it will execute test with browser. The example command would be:
`./do test:performance --head`

Protocol-level tests: to help with getting a test working, the `--http-debug="full"` flag prints to console the full log of requests and their responses. It is also useful to use `console.log()` to print messages when debugging.

## Results Output

### Default Terminal Output

When the tests are run, there is a summary report in the terminal at the end.

### HTML Output with k6 Reporter

The k6 reporter should have already been implemented. There's generated file called `k6report.html` inside `performance` folder after every suite/scenario execution. You can find the path and open it in a local browser or see it as artefact in CircleCI.

[See this guide for more details](https://github.com/benc-uk/k6-reporter)

## Writing Tests (to be added)

TBD

### Capturing Requests

Browser-level tests: k6 tests rely on Playwright technology to make E2E tests where then k6 measure performance with its metrics, tresholds and checks. There are also groups to divide one scenario intro parts like one group for creating newsletter and going through pages and the one for sending it, so we could measure performance of the whole scenario in single or multiple parts.

Protocol-level tests: k6 tests rely on HTTP requests in order to test the backend. They can either be constructed from scratch, by using the k6 recorder, or by converting a HAR file. The k6 recorder is a browser extension which captures http requests generated as you perform actions in a tab. It generates a test with all the HTTP requests from your actions which can then be modified to make it execuatable.

### Request Headers For Protocol-level Tests

Every HTTP requests tested includes the headers.

To make it easier to manage the headers they have been moved to a separate file so any changes can be made to all the requests at once.
In `headers.js` the common headers are grouped by type and then can be imported in for use in the requests. However if an individual request uniquely needs a specific header this can still be added in as an extra member of the headers object literal of that request.

If you don't see `headers.js` inside `performance` folder, that means we don't have yet protocol-level tests included. Feel free to ignore this section then.

### Groups

Groups are used to organize common logic in the test scripts and can help with the test result analysis. For example the `group` `"Proceed to checkout"` groups together multiple requests triggered by this action.

### Checks

Checks are like asserts but they don’t stop the tests if they record a failure (for example in a load test with 1000s of iterations of a request this allows for an isolated flakey iteration to not stop test execution).

All requests have had checks for at least a `200` http status repsonse added and most also have an additional check for a string contained in the response body.

## Other Resources

[k6 documention](https://k6.io/docs/) is a very useful resource for test creation and execution.
[xk6-browser documentation](https://k6.io/docs/javascript-api/xk6-browser/) is a very useful resource for test creation and execution.
