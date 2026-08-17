# Branch protection

`main` protection was enabled after the first successful pull-request run registered these exact GitHub Actions check contexts:

- `Dependencies`
- `Backend tests`
- `Frontend build`
- `Browser QA`
- `Android build`

The workflow is named `CI`; GitHub displays these jobs under that workflow, but branch protection stores the job names above as its registered contexts.

Current policy requires the branch to be up to date, all four checks to pass, one approving review, dismissal of stale approvals, and resolution of review conversations. It applies to administrators and blocks force pushes and branch deletion.

Branch protection is repository configuration rather than a guarantee supplied by this file. Administrators should periodically compare this document with the live GitHub settings.
