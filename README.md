# memberadmin

**Restricted member management for Nextcloud site owners.**

[![Available on the Nextcloud App Store](https://img.shields.io/badge/Nextcloud%20App%20Store-memberadmin-0082C9)](https://apps.nextcloud.com/apps/memberadmin)

> 📦 **App Store** : memberadmin est **publié** et disponible sur l'App Store
> Nextcloud → https://apps.nextcloud.com/apps/memberadmin
> (installation en un clic depuis la page Apps de votre instance).

A Nextcloud app that lets an authorized user (a *site owner*) **add or remove group
members** — and nothing else. Designed to give autonomy to people who manage client
folders / team folders, **without** the ability to create, edit or delete accounts,
and **without** the Accounts/Users admin menu.

## Naming model (per project)

Follow this convention to get everything automatic:

| Element | Name | Example |
|---|---|---|
| Project management group | `<Project>-management` | `OrderHub-management` |
| Internal access group | `<Project>-Internal` | `OrderHub-Internal` |
| External access group (B2B clients) | `<Project>-External` | `OrderHub-External` |
| Team folder | `<Project>` | `OrderHub` |

- A site owner is a member **only** of the `-management` groups of the projects they
  manage (several projects = several groups, union).
- Being a member of `<Project>-management` automatically grants the management of
  `<Project>-Internal` and `<Project>-External`. No per-user configuration.

## Features

- **Groups shown automatically**:
  - delegated groups (`occ memberadmin:grant`),
  - groups the user is Nextcloud **group admin** of,
  - groups derived from a **role group** (`occ memberadmin:role <gestion> add <prefixes…>`),
  - **automatic by convention**: member of `<Project>-management`
    (also `*_gestion`, `*-gestion`, `*_management`, …) manages `<Project>*`.
- Add a member through the **native Nextcloud user search** (≥ 2 characters).
- Remove a member (the user can never remove their own account).
- **Display names** shown (uid in tooltip) — works with LDAP/OIDC/SAML accounts.
- No other capability on accounts (no create/edit/delete user).
- Managed groups must be **local groups** (AD/LDAP-synced groups are read-only).
- UI follows the user language preference (English / French).
- Admin usage page: **Administration settings → Member management**.
  Per-user: avatar → Settings → **Member management**.

## Security

- Every request is authorized (non-delegated group → 403).
- The `admin` group is never manageable.
- Self-removal is forbidden.
- CSRF enforced on state-changing calls.
- Search restricted to users who manage at least one group, minimum 2 characters,
  limited results.
- No user create/edit/delete, no direct SQL, JSON + `textContent` output only.

## Requirements

- Nextcloud 27 – 34.
- `occ` access for installation.

## Installation

1. Copy this folder as `custom_apps/memberadmin` (a persistent `custom_apps` volume is
   recommended).
2. `occ app:enable memberadmin`
3. (Optional) delegate explicitly:
   ```
   occ memberadmin:grant <site-owner> <group>
   occ memberadmin:list
   occ memberadmin:import /path/rights.csv    # bulk: "owner;group" per line
   ```
4. (Optional) role prefixes for a management group:
   ```
   occ memberadmin:role <management-group> add "<Prefix-1>" "<Prefix-2>"
   occ memberadmin:role <management-group> list
   ```

With the naming model above (step 3/4 optional), adding a site owner to
`<Project>-management` is enough.

## Configuration example (per project)

```bash
occ group:add OrderHub-management
occ group:add OrderHub-Internal
occ group:add OrderHub-External
# managers / team / external clients as members (Users)
# team folder OrderHub: access OrderHub-management (full+Share) & OrderHub-Internal (rw);
# group share to OrderHub-External (read)
```

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).

## Development / releases

See [publish-guide-EN.md](docs/publish-guide-EN.md) (also FR in the docs) for GitHub
releases, certificate and app store submission.
