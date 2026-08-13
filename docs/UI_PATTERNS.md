# UI patterns

## Forms

Use a visible label linked by `for`/`id`, preserve entered values after validation, show a concise field or form error, and never disclose whether an authentication identifier exists. Upload controls must state accepted formats and limits that match server validation.

## Lists and tables

Keep filtering and primary list actions above the result set. Provide an `x-empty-state` when no rows exist. Wide admin tables may scroll inside their own container; the document itself must not overflow.

## Navigation

Use the public or admin layout navigation. Mobile toggles expose `aria-expanded` and an accessible label. The active destination should remain visually identifiable.

## Feedback

Use badges for persistent statuses, inline validation for actionable errors, and the toast container for transient confirmation. Destructive operations require an explicit confirmation and server-side authorization.

## Content

User-entered rich text is sanitized on the server before persistence. Templates should continue to escape ordinary values; only sanitized rich content may be rendered as HTML.
