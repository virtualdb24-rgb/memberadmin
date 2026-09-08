# Naming model & per-project management (memberadmin)

Goal: **site owners** manage the access groups of **team folders (projects)**,
isolated per project, without the Accounts menu, only add/remove members.

## Naming convention (to follow)
| Element | Name | Example |
|---|---|---|
| Project management group | `<Project>-management` | `OrderHub-management` |
| Internal access group | `<Project>-Internal` | `OrderHub-Internal` |
| External access group (B2B clients) | `<Project>-External` | `OrderHub-External` |
| Project team folder | `<Project>` | `OrderHub` |

## Rules
- A **site owner is only a member of the `-management` groups of the projects
  they manage** (no "all owners" group).
- An owner on several projects is a member of several `-management` groups
  → they manage only their projects (union).
- Each project has its own `-Internal` / `-External` → full isolation.

## App automatic behaviour
- Member of `<Project>-management` automatically manages
  `<Project>-Internal` and `<Project>-External` (automatic `-management` suffix
  detection; no `memberadmin:role` or `grant` needed).
- Adding/removing a member of the management group applies automatically.

## Setup (admin, once per project)
1. Create the groups: `occ group:add OrderHub-management`, `OrderHub-Internal`,
   `OrderHub-External`
2. Add the members:
   - project managers in `OrderHub-management`
   - internal team in `OrderHub-Internal`
   - external clients (accounts) in `OrderHub-External`
3. Create the team folder `OrderHub`:
   - access group `OrderHub-management` (full + Share) for co-management
   - access group `OrderHub-Internal` (e.g. read/write)
   - group share of the folder to `OrderHub-External` (read) for the clients
4. Optional: members of `OrderHub-management` can be allowed to manage the
   "Team folders" page (admin delegation, no Accounts).

## Reminders
- Never give "group admin / manager of group" to site owners
  (otherwise the Accounts menu appears + account edit/delete rights).
- The managed groups must be **local**.
