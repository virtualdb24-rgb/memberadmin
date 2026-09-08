# Publier l'app sur GitHub & l'appstore Nextcloud (FR)

## 1. Dépôt GitHub
- Repo : `virtualdb24-rgb/memberadmin` (public) — `main` = code à jour, versions taggées `vX.Y.Z`.
- Une release GitHub = archive `memberadmin-X.Y.Z.tar.gz` contenant le dossier `memberadmin/`.

## 2. Publier une nouvelle version (depuis ~/memberadmin-repo)
```bash
git add -A && git commit -m "feat: <resume>, version X.Y.Z"
git push origin main
git tag -a vX.Y.Z -m "vX.Y.Z"
git push origin main --tags
rm -rf /tmp/rel && mkdir -p /tmp/rel/memberadmin
git archive HEAD | tar -x -C /tmp/rel/memberadmin
tar czf /tmp/memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
gh release create vX.Y.Z /tmp/memberadmin-X.Y.Z.tar.gz --title "vX.Y.Z" --notes "<notes>"
```

## 3. Certificat de signature (obligatoire pour l'appstore)
- Demande via PR sur `nextcloud/app-certificate-requests` : fichier `memberadmin/memberadmin.csr` (généré avec `openssl req … -CN=memberadmin`), PR depuis ton fork vers `master`.
- Quand l'équipe fournit `memberadmin.crt`, le placer dans `~/memberadmin-certs/`.

## 4. Signer une release
```bash
cd /tmp && rm -rf rel && mkdir -p rel/memberadmin
git archive HEAD | tar -x -C rel/memberadmin
# injecter le certificat dans l'app (info.xml doit contenir <certificate>…)
occ integrity:sign-app --path=/tmp/rel/memberadmin --privateKey=~/memberadmin-certs/memberadmin.key --certificate=~/memberadmin-certs/memberadmin.crt
tar czf memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
```

## 5. Soumettre sur apps.nextcloud.com
1. Compte développeur connecté (GitHub).
2. Add app « from GitHub » : repo `virtualdb24-rgb/memberadmin`, tag `vX.Y.Z`.
3. Renseigner : nom, résumé, description longue (cf. `store-listing.txt`), screenshot `screenshots/admin.png`.
4. Soumettre → revue de code → publication.

## Vérifications avant chaque release
- `php -l` sans erreur, code testé sur une instance.
- `info.xml` : `<version>` = tag, dépendances OK (min 27 / max 34).
- Archive contient `memberadmin/appinfo/info.xml`.
