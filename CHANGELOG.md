# Changelog

## 0.0.5
- Model "per project" : automatic detection of `<Project>-management` (suffix `-management`
  added to the auto role suffixes) → members manage `<Project>-Internal` / `<Project>-External`.
- Docs FR/EN (naming model).

## 0.0.4
- Security hardening: search restricted to users managing at least one group, minimum 2
  characters; CSRF enforced on state-changing endpoints (add/remove).
- Display names for members/search (uid in tooltip).

## 0.0.3
- Role-based grant: `occ memberadmin:role <group> add <prefixes…>`.
- Auto-convention `*_gestion` / `*-gestion` → manage the same base prefix.
- Documentation FR/EN.

## 0.0.2
- Enriched info.xml, single screenshot, public GitHub release.

## 0.0.1
- Initial restricted member management app (add/remove members in delegated groups).
