# solidtime - The modern Open-Source Time Tracker (DeepVision fork)

[![GitHub License](https://img.shields.io/github/license/solidtime-io/solidtime?style=flat-square)](https://github.com/solidtime-io/solidtime/blob/main/LICENSE.md)
[![Codecov](https://img.shields.io/codecov/c/github/solidtime-io/solidtime?style=flat-square&logo=codecov)](https://codecov.io/gh/solidtime-io/solidtime)
![GitHub Actions Unit Tests Status](https://img.shields.io/github/actions/workflow/status/solidtime-io/solidtime/phpunit.yml?style=flat-square)
![PHPStan badge](https://img.shields.io/badge/PHPStan-Level_7-blue?style=flat-square&color=blue)

![Screenshot of the solidtime application with header: solidtime - The modern Open-Source Time Tracker](docs/solidtime-banner.png "solidtime Banner")

solidtime is a modern open-source time tracking application for Freelancers and Agencies.

## Features

 - Time tracking: Track your time with a modern and easy-to-use interface
 - Projects: Create and manage projects and assign project members
 - Tasks: Create and manage tasks and assign tasks to projects
 - Clients: Create and manage clients and assign clients to projects
 - Billable rates: Set billable rates for projects, project members, organization members and organizations 
 - Multiple organizations: Create and manage multiple organizations with one account
 - Roles and permissions: Create and manage organizations
 - Import: Import your time tracking data from other time tracking applications (Supported: Toggl, Clockify, Timeentry CSV)

## Self Hosting

If you are looking into self-hosting solidtime, you can find the guides [here](https://docs.solidtime.io/self-hosting/intro)

We also have an examples repository [here](https://github.com/solidtime-io/self-hosting-examples)

If you do not want to self-host solidtime or try it out you can sign up for [solidtime cloud](https://www.solidtime.io/)

## Issues & Feature Requests

If you find any **bugs in solidtime**, please feel free to [**open an issue**](https://github.com/solidtime-io/solidtime/issues/new) in this repository, with instructions on how to reproduce the bug. 
If you have a **feature request**, please [**create a discussion**](https://github.com/solidtime-io/solidtime/discussions/new?category=feature-requests) in this repository.

## Contributing

Please open an issue or start a discussion and wait for approval before submitting a pull request. This does not apply to tiny fixes or changes however, please keep in mind that we might not merge PRs for various reasons. 

Please read the [CONTRIBUTING.md](./CONTRIBUTING.md) before sumbitting a Pull Request.

We do accept contributions in the [documentation repository](https://github.com/solidtime-io/docs) f.e. to add new self-hosting guides.

## Security

Looking to report a vulnerability? Please refer our [SECURITY.md](./SECURITY.md) file.

## License

This project is open-source and available under the GNU Affero General Public License v3.0 (AGPL v3). Please see the [license file](LICENSE.md) for more information.

## How to sync

### Sync with upstream
To keep fork up to date with the upstream repository, use following command and resolve conflicts
```bash
git pull upstream main
```

Update `composer.lock` file after merge. Use php 8.3
```bash
rm -f composer.lock
composer install
```

### Changed files
#### GitHub Actions
Existing GitHub Action workflows are disabled
* `.github/workflows/*.yml`

```yaml
...
# DeepVision Patch: disable private build
if: false
...
```
New GitHub Action workflow [build-deepvision.yml](.github/workflows/build-deepvision.yml) is copied from [build-private.yml](.github/workflows/build-private.yml). 
Need to keep it in sync with upstream.

#### Logo
The logo is replaced with the DeepVision logo in [AuthenticationCardLogo.vue](resources/js/Components/AuthenticationCardLogo.vue)
