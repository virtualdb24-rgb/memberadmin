# memberadmin

**Restricted member management for Nextcloud site owners.**

A Nextcloud app that lets an authorized user (a *site owner*) **add or remove
group members** — and **nothing else**. It is designed to give autonomy to
people who manage client folders without giving them the ability to create,
edit or delete accounts.

## Features

- Show the groups the user is entitled to manage:
  - groups delegated by an admin (`occ memberadmin:grant`), **and/or**
  - groups for which the user is a Nextcloud **group admin** (sub-admin).
- Add a member (search over the native Nextcloud user search).
- Remove a member (the user can never remove their own account).
- No other capability on accounts (no create/edit/delete user).
- Group management restricted to **local Nextcloud groups**
  (AD/LDAP-synced groups are read-only).
- UI language follows the user preference (French / English).
- Admin usage page: **Administration settings → Member management**
  (and per-user: avatar → Settings → Member management).

## Requirements

- Nextcloud ≥ 27 (tested on 33).
- Access to `occ` for installation/delegation.

## Installation

1. Copy this folder as `custom_apps/memberadmin` (persistent `custom_apps` volume recommended).
2. `occ app:enable memberadmin`
3. (Optional) delegate groups:
   ```
   occ memberadmin:grant <site-owner> <group>
   occ memberadmin:list
   occ memberadmin:import /path/rights.csv    # bulk: "owner;group" per line
   ```

## Security notes

- Authorizations are checked on every request (allowed group, otherwise 403).
- The `admin` group is never manageable.
- Self-removal is forbidden.
- CSRF is enforced on state-changing endpoints.
- Scope: **member add/remove in local groups only**.

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).
