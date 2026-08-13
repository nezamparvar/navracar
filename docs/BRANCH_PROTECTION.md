# Branch protection readiness

Protect `main` after the first pull request run creates these exact check contexts:

- `CI / Dependencies`
- `CI / Backend tests`
- `CI / Frontend build`
- `CI / Browser QA`

Recommended settings: require a pull request, at least one approval, dismiss stale approvals, require all four checks, require conversation resolution, block force pushes and deletions, and include administrators. Do not enable required checks until one successful run has registered the contexts.

Branch protection is currently not enabled and cannot be treated as a repository-controlled code change; an administrator must apply it in GitHub settings.
