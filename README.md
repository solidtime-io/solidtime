# solidtime - The modern Open-Source Time Tracker

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

## Planner time linking and reporting (optional)

This fork adds an optional Planner layer to link time to milestones and report by Phase/Milestone with minimal diffs.

- Enable: set `PIA_ENABLED=true` (planner.enabled aliases this).
- Data model additions:
  - Tasks: `due_at` (nullable), `is_milestone` (boolean)
  - Time entries: `milestone_id` (nullable, indexed)
- Start timer from milestones (when enabled):
  - In Planner views, a Start button on milestone rows pre-fills `project_id`, `milestone_id` (and optionally `task_id`),
    prefixes description with `[Milestone]`, and can auto-tag a Phase.
- Reporting (when enabled):
  - Group by: Milestone, Phase (Phase maps to tags). Combine with Project via sub-group.
  - Filters: Milestones-only, Milestone(s), and Phase (prefix or tags).
- Gating: When `planner.enabled=false`, API/exports/UI behave like upstream Solidtime (no milestone_id in responses; no extra columns).

## License

This project is open-source and available under the GNU Affero General Public License v3.0 (AGPL v3). Please see the [license file](LICENSE.md) for more information.
