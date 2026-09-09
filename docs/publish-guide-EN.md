# Publishing the app on GitHub & the Nextcloud app store (EN)

## Current status
- Certificate **obtained** (PR `nextcloud/app-certificate-requests#1222`, merged).
- App **registered** and release **v0.0.5 uploaded** on apps.nextcloud.com.
- Public page: https://apps.nextcloud.com/apps/memberadmin (awaiting the final
  review → appearance in the catalog).

## 1. GitHub repository
- Repo: `virtualdb24-rgb/memberadmin` (public) — `main` = up-to-date code, versions tagged `vX.Y.Z`.
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

## 3. Signing certificate
- Obtained via a PR on `nextcloud/app-certificate-requests`: the
  `memberadmin/memberadmin.csr` file generated with
  `openssl req -nodes -newkey rsa:4096 -keyout memberadmin.key -out memberadmin.csr -subj "/CN=memberadmin"`.
- Keys kept in `~/memberadmin-certs/` (`memberadmin.key` **secret**, `memberadmin.crt`
  public). Never publish the private key.

## 4. Sign a release (verified)
```bash
cd /tmp && rm -rf rel && mkdir -p rel/memberadmin
git archive HEAD | tar -x -C rel/memberadmin
occ integrity:sign-app --path=/tmp/rel/memberadmin \
  --privateKey=~/memberadmin-certs/memberadmin.key \
  --certificate=~/memberadmin-certs/memberadmin.crt
tar czf memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
```
- Produces `appinfo/signature.json` (with embedded certificate).
- Server integrity check: `occ integrity:check-app memberadmin` → exit 0.
- Validate `appinfo/info.xml` against the app store schema:
  `xmllint --noout --schema <(curl -s https://apps.nextcloud.com/schema/apps/info.xsd) appinfo/info.xml`
  (⚠️ `<screenshot>`: no `type` attribute, reference **existing** images only).

## 5. Register the app (once)
1. Log in on https://apps.nextcloud.com (GitHub developer account).
2. "Register an application" page:
   - **Public certificate**: contents of `memberadmin.crt` (full PEM).
   - **App ID signature**: `echo -n "memberadmin" | openssl dgst -sha512 -sign memberadmin.key | openssl base64`

## 6. Upload a release
1. Upload the signed archive to the GitHub release:
   `gh release upload vX.Y.Z memberadmin-X.Y.Z.tar.gz`
2. "Upload app release" page:
   - **Download (tar.gz)**: `https://github.com/virtualdb24-rgb/memberadmin/releases/download/vX.Y.Z/memberadmin-X.Y.Z.tar.gz`
   - **Signature**: `openssl dgst -sha512 -sign memberadmin.key memberadmin-X.Y.Z.tar.gz | openssl base64`
3. The store downloads the archive and verifies the signature against your
   certificate (RSA PKCS1v15 SHA-512). Success = "Version uploaded".
4. Wait for the **review** by the Nextcloud team → appearance in the catalog.

### Pitfalls encountered (verified)
- **Signature format**: paste the `openssl base64` output as-is (64-char lines).
  A single-line version can be rejected.
- **`info.xml`**: the `type` attribute on `<screenshot>` fails store validation
  (`Element 'screenshot', attribute 'type' is not allowed`).
- **Cache / bytes**: if "signature invalid" persists while the signature is good,
  re-upload the archive under a **new unique asset name** (new URL).
- Always verify your signature locally before submitting:
  ```bash
  openssl x509 -in memberadmin.crt -pubkey -noout > /tmp/pub.pem
  openssl base64 -d -in sig.txt -out /tmp/sig.bin
  openssl dgst -sha512 -verify /tmp/pub.pem -signature /tmp/sig.bin memberadmin-X.Y.Z.tar.gz  # → Verified OK
  ```

## Checks before each release
- `php -l` without errors, code tested on an instance.
- `info.xml`: `<version>` = tag, dependencies OK (min 27 / max 34), valid licence, existing screenshots.
- Archive contains `memberadmin/appinfo/info.xml` and `appinfo/signature.json`.
- CHANGELOG.md with a `## X.Y.Z` entry for the version.
