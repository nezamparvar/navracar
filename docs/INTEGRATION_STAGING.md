# Production-like integration staging

## Purpose

`https://staging.nezamparvar.com` is the shared integration environment for
the website, Navra Capture Chrome extension, and Navracar Android app. All
three clients use the same Laravel application, database, pricing engine,
quote workflow, CRM, and admin panel. There is no client-specific backend or
database.

The release path is:

1. merge website, Chrome, and Android work through a protected pull request;
2. require all six CI jobs on the exact commit;
3. build one immutable cPanel staging artifact from the exact `main` HEAD;
4. deploy and accept that artifact on integration staging;
5. promote only the accepted artifact in a separate, explicitly approved
   production operation.

The staging publisher is pinned to `refs/heads/main`. A selected feature
branch, an ancestor of `main`, or an arbitrary SHA cannot publish staging.
Production deployment is not part of this procedure.

## Client isolation

| Client | Staging identity | Production-like identity |
|---|---|---|
| Android | `com.navracar.mobile.staging`, `https://staging.nezamparvar.com` | `com.navracar.mobile`, `https://navracar.com` |
| Chrome | `Navra Capture — Staging`, staging host permission | `Navra Capture`, production host permission |
| Laravel | isolated staging `.env`, database, cookie/cache namespace, `noindex` | server-owned production configuration |

No API URL, signing credential, Firebase credential, session, or database
credential is shared through an APK or extension package. Business settings
remain server-side and are returned through the existing API.

## Current accepted integration candidate

The initial combined-client acceptance candidate was intentionally published
from the feature branch before enabling the main-only guard. It established
that the combined artifact works before the guard is merged.

| Field | Value |
|---|---|
| URL | `https://staging.nezamparvar.com` |
| Candidate | `rc-v1.4.0-3` |
| Source commit | `e4e70f1e3c35650820ebea3781f33135d25c3b04` |
| Artifact commit | `b80b20d4d23dd793ce006f761ebe01f944f45cde` |
| Artifact SHA-256 | `f6887aa2b73937baab80c4bdf70a1df62e033f091fb39b568c6f3ece20de0a40` |
| Candidate workflow | `32174109151` |
| Final protected CI | `32175996454` |
| Pull request | `https://github.com/nezamparvar/navracar/pull/46` |

The installed candidate exposes its identity through
`X-Navracar-Candidate` and `X-Navracar-Source`. The production deployment
timer remained disabled and inactive before and after staging deployment.

## Live acceptance evidence

The following checks ran against the installed candidate:

| Area | Result |
|---|---|
| Website `/`, health `/up`, admin login | HTTP 200 |
| Mobile bootstrap and vehicle listing | HTTP 200 JSON |
| Capacitor CORS preflight | HTTP 204 with `capacitor://localhost` |
| Chrome extension CORS | exact extension origin returned |
| Chrome pairing | one-time code exchanged for a 64-character token |
| Chrome capture | fixture queued as import item `21`; temporary pairings revoked |
| Android installation | HTTP 201 |
| Android analytics event | HTTP 202, one event accepted |
| Mobile insights panel | search, device, acquisition source, and Push controls visible |
| Shared pricing engine | HTTP 200; no client-supplied formula used |
| Android quote workflow | request `3` created and visible in the shared CRM with Android source and matching total |
| Basic-Auth challenge | absent from API and website responses |
| Production timer | disabled and inactive |

The acceptance records are clearly marked as integration fixtures. Temporary
Chrome access tokens were revoked after the test.

## Verified commands

```bash
composer validate --strict
composer audit --locked
npm ci
npm audit --audit-level=high
npm run build
npm run test:mobile
php artisan test --compact
npx playwright test -c playwright.android.config.js
bash tools/test-cpanel-staging-source-policy.sh
bash tools/test-cpanel-staging-runtime.sh
bash tools/test-cpanel-production-controls.sh

cd tools/navra-capture-extension
npm ci
npm audit --audit-level=high
npm run lint
npm test -- --runInBand
npm run build

cd ../..
bash tools/build-android-variants.sh
```

Local Android acceptance used Node 22, JDK 21, Android SDK/Build Tools 36,
`aapt`, `zipalign`, and `apksigner`. CI runs the same major toolchain.

## Artifacts

Android build outputs:

| Artifact | SHA-256 | Verification |
|---|---|---|
| `android/app/build/outputs/navracar-staging-debug.apk` | `21022b4f59cb36127af703bac6d5b46c4fed49ce463f7bcd3a9293a9900ff9cb` | aligned, Android debug signature |
| `android/app/build/outputs/navracar-staging-release-unsigned.apk` | `3a2b09714086c24f19595e069aa4b5a7cda0d814e3a9b2982e5e55ff27c6cee3` | aligned, intentionally unsigned |
| `android/app/build/outputs/navracar-production-debug.apk` | `c333e0c76cdcb11d420a57def698e408b83342849c81963d5f46ec58797b904b` | aligned, Android debug signature |

Chrome packages:

| Artifact | SHA-256 |
|---|---|
| `tools/navra-capture-extension/dist/navra-capture-staging.zip` | `b36b5f16909e984cc58eb78ac68acdcf9e955ae22883179e6d9deea77ead24c4` |
| `tools/navra-capture-extension/dist/navra-capture-production.zip` | `14067b4b2ae2d8f24300a68b6eea0b5c4990b148eca3ff2dc49c26e709aa8454` |

Android visual artifacts are in `artifacts/android-v1/screenshots/` and cover
privacy consent, Home RTL, vehicle listing, search/filter, vehicle detail,
pricing, quote request, requests, account, favorites, and mobile insights.

## External release requirements

- Live Push infrastructure is implemented but staging currently reports
  `push_available: false`: a server-side Firebase project ID and readable
  service-account credential must be provisioned outside Git before sending
  a real notification.
- A production Android release requires the owner-controlled signing key. It
  must never be committed or substituted with the debug key.
- The pull request requires the repository's approving review before merge.
- The one observed packaging failure was a transient Packagist security-feed
  timeout; rerunning the same locked workflow and commit succeeded without
  weakening the audit.

## Production promotion checklist

- [ ] Owner accepts the integration staging UI and workflows.
- [ ] PR `#46` receives the required independent approval and is merged.
- [ ] All six CI jobs pass on the resulting exact `main` HEAD.
- [ ] A new immutable candidate is built from that exact `main` HEAD.
- [ ] The new main candidate is deployed to staging and the live matrix above
      is repeated.
- [ ] Android production artifact is signed with the owner-controlled key and
      its checksum/certificate are recorded.
- [ ] Firebase server credentials and a real opt-in Push delivery are verified,
      if Push is required for the release.
- [ ] The accepted staging artifact is marked `accepted-owner` without being
      rebuilt.
- [ ] Production promotion is run only after explicit owner approval.
- [ ] Production health, headers, CRM, pricing, queue worker, and rollback are
      verified after promotion.

Production has not been deployed by this work.
