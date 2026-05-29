# Developer profile import drop folder

Drop `*.json` files here. The cron command

    php yii import/developers

picks up every `*.json` in this folder and imports the records. On success the
file is **deleted**; if it can't be parsed (invalid JSON / not a list) it is moved
to `failed/` for inspection. It is safe to run every few minutes — only newly
dropped files are read.

Publishers work the same way via `php yii import/publishers` reading
`console/data/import/publishers/`.

## File format

Either a bare array of records, or an envelope:

```json
{ "version": 1, "items": [ { "name": "..." }, ... ] }
```

## Record schema

| field          | type            | notes |
|----------------|-----------------|-------|
| `id`           | int (req.)      | **Match key** — the developer/publisher id from our own system. |
| `name`         | string \| null  | Optional, informational only (the authoritative name stays on the developer row). |
| `description`  | string \| null  | |
| `history`      | string \| null  | Free text; rendered with line breaks. |
| `logo_url`     | string \| null  | max 500 chars |
| `country`      | string \| null  | max 100 — keep names consistent (drives the country filter). |
| `city`         | string \| null  | max 100 |
| `founded_year` | int \| null     | |
| `closed_year`  | int \| null     | set to mark a studio inactive |
| `website`      | string \| null  | max 500 |
| `twitter`      | string \| null  | handle **or** full URL — normalized to a bare handle on import. |
| `discord`      | string \| null  | max 500 |

## Semantics & tips

- **Match is by `id`** (the primary key in our system). Rows without a matching
  id are skipped and logged.
- A profile attached to a developer is auto-propagated to the same-named publisher
  (and vice versa) via CompanyProfileSync.
- Only **existing** developers/publishers are enriched (companies normally enter
  the catalog through the Steam sync).
- The row is the **source of truth**: a field present with `null` clears the column;
  a field omitted entirely is left untouched (good for partial top-ups).
- Each record imports in its own transaction, so one bad row won't abort the file.
- For very large feeds, prefer many smaller files (they're processed and moved
  independently) over one huge file.
