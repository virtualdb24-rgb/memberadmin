# Publishing the app on GitHub & the Nextcloud app store (EN)

## 1. GitHub repository
- Repo: `virtualdb24-rgb/memberadmin` (public) — `main` = up-to-date code, tagged releases `vX.Y.Z`.
- A GitHub release = `memberadmin-X.Y.Z.tar.gz` archive containing the `memberadmin/` folder.

## 2. Publish a new version (from ~/memberadmin-repo)
```bash
git add -A && git commit -m "feat: <summary>, version X.Y.Z"
git push origin main
git tag -a vX.Y.Z -m "vX.Y.Z"
git push origin main --tags
rm -rf /tmp/rel && mkdir -p /tmp/rel/memberadmin
git archive HEAD | tar -x -C /tmp/rel/memberadmin
tar czf /tmp/memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
gh release create vX.Y.Z /tmp/memberadmin-X.Y.Z.tar.gz --title "vX.Y.Z" --notes "<notes>"
```

## 3. Signing certificate (required for the app store)
- Request via a PR on `nextcloud/app-certificate-requests`: file `memberadmin/memberadmin.csr` (generated with `openssl req … -CN=memberadmin`), PR from your fork into `master`.
- When the team provides `memberadmin.crt`, place it in `~/memberadmin-certs/`.

## 4. Sign a release
```bash
cd /tmp && rm -rf rel && mkdir -p rel/memberadmin
git archive HEAD | tar -x -C rel/memberadmin
# inject the certificate into the app (info.xml must contain <certificate>…)
occ integrity:sign-app --path=/tmp/rel/memberadmin --privateKey=~/memberadmin-certs/memberadmin.key --certificate=~/memberadmin-certs/memberadmin.crt
tar czf memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
```

## 5. Submit on apps.nextcloud.com
1. Developer account logged in (GitHub).
2. Add app "from GitHub": repo `virtualdb24-rgb/memberadmin`, tag `vX.Y.Z`.
3. Fill in: name, summary, long description (see `store-listing.txt`), screenshot `screenshots/admin.png`.
4. Submit → code review → publication.

## Checks before each release
- `php -l` without errors, code tested on an instance.
- `info.xml`: `<version>` matches the tag, dependencies OK (min 27 / max 34).
- Archive contains `memberadmin/appinfo/info.xml`.
