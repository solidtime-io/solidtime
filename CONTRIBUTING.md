# Contributing to solidtime

Contributions are greatly apprecited, please make sure to read the rules and vision for solidtime before contributing. 

## Rules

### Issues for Bugs, Discussions for Feature requests

In order to keep the issues of the repository clean we decided to only use them for bugs. Feature Requests and enhancement are handled in discussions. This also helps us to see which feature requests are popular as they can be upvoted. 

### Only work on approved issues

To respect your time and help us manage contributions effectively, please open an issue or start a discussion and wait for approval before submitting a pull request (PR). This does not apply to tiny fixes or changes however, please keep in mind that we might not merge PRs for various reasons. 

### Contributor License Agreement

You'll also notice that we’ve set up a [Contributor License Agreement (CLA)](https://cla-assistant.io/solidtime-io/solidtime), which must be signed before any PR can be merged. Don’t worry - the process is quick and only takes a few clicks.

We want to be transparent about why we require the CLA and what it means for your contributions and the codebase. That’s why we’ve written a few paragraphs below outlining our plans and vision for solidtime in the **Vision** part of this document. 

### Prevent Duplicate Work

Before you submit a new PR, make sure that none exists already. If you plan to work on an issue, make sure to let us and others know by commenting on the issue/discussion. 

### Give context

Tell us what you thinking was behind the decisions you made while drafting the PR. Treat the PR itself as documentation for everyone who wants to go back and understand why certain decisions were made. 

### Summarize your PR

Please make sure to include a short summary at the top of your PR to make it easy for us to quickly check what the PR is about, without looking at the code changes. 

### Use Github Keywords and Auto-Link Issues

Use phrases like "Closes #123" or "Fixes #123" in the PR description to link the PR with the issue that you are adressing. 

### Mention what you tested and how

Explain how you tested and validated the implementation. 

### Keep Naming consistent

Look at existing code patterns and use naming conventions that already exist in the code base. 

### Testing

We have an exhaustive test-suite of PHPUnit (Backend) and Playwright (Frontend) testing. Whereever applicable please make sure to write add tests to the codebase. 

### Linting & Formatting

Make sure to run linting and formatting commands before you commit the changes. 

For backend changes:

```
composer fix
composer analyse
```

For frontend changes: 

```
npm run lint:fix
```

## Vision

We started solidtime to provide an open infrastructure solution for time tracking—one that empowers teams and individuals to fully own their data, instead of depending on proprietary platforms. We believe infrastructure software should be open, accessible, and built to last. However, competing with established market leaders in this space requires long-term financial sustainability.

solidtime is licensed under the AGPL, which we believe is the best available license to strike a balance between openness and financial viability. The AGPL gives us, as the copyright holders, certain exclusive rights that we plan to leverage to fund development. To ensure we retain those rights across the entire codebase, we've put a CLA in place that contributors must sign before submitting code.

One of solidtime’s key advantages is that it's built to be self-hostable. This makes it a great solution for organizations like governments, healthcare providers, and enterprises that are required to keep data on their own infrastructure due to regulations or internal policies. These organizations may need custom licenses, integrations, or modifications that aren't suitable for the open-source version. To support them, we offer relicensed versions of solidtime along with support plans.

We’ll also provide proprietary extensions for solidtime. These will be available to enterprise customers with support plans, but also to individual users or teams who don’t need support, at much more accessible price points. For companies running solidtime on their own infrastructure, this is the easiest way to support the project while gaining additional functionality. While we plan to make it easier to build custom extensions in the future, our current APIs are still highly experimental.

Finally - and perhaps most importantly - we offer a hosted SaaS version called solidtime Cloud, for users who can’t or don’t want to run the software themselves. This version includes proprietary extensions, always runs the latest commit, and includes monitoring and billing features available exclusively on this hosted instance. We expect solidtime Cloud to play a critical role in funding the project long-term.

Having full control over the source code’s licensing also gives us the ability to change the license of the main project in the future. That said, we have no plans to do so and would only consider it in extreme cases - for example, if a malicious actor were to directly compete with our hosted service in a way that threatens the sustainability of the project, the legal interpretation of AGPL changes in a way that would make it unreasonable to use for certain companies, or a new similar license gains wide-spread adoption. Regardless, solidtime will always remain free to self-host for individuals and companies who use it as part of their work, and all previous releases will remain licensed under AGPL.

If you are using the open-source version of solidtime and want to support us, the best way to do so is to spread the word. 
