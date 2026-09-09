# Publier l'app sur GitHub & l'appstore Nextcloud (FR)

## État actuel
- Certificat **obtenu** (PR `nextcloud/app-certificate-requests#1222`, mergée).
- App **enregistrée** et release **v0.0.5 téléversée** sur apps.nextcloud.com.
- Page publique : https://apps.nextcloud.com/apps/memberadmin (en attente de la
  validation finale → apparition dans le catalogue).

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

## 3. Certificat de signature
- Obtenu via PR sur `nextcloud/app-certificate-requests` : fichier
  `memberadmin/memberadmin.csr` généré par
  `openssl req -nodes -newkey rsa:4096 -keyout memberadmin.key -out memberadmin.csr -subj "/CN=memberadmin"`.
- Clés rangées dans `~/memberadmin-certs/` (`memberadmin.key` **secret**,
  `memberadmin.crt` public). Ne JAMAIS publier la clé privée.

## 4. Signer une release (vérifié)
```bash
cd /tmp && rm -rf rel && mkdir -p rel/memberadmin
git archive HEAD | tar -x -C rel/memberadmin
occ integrity:sign-app --path=/tmp/rel/memberadmin \
  --privateKey=~/memberadmin-certs/memberadmin.key \
  --certificate=~/memberadmin-certs/memberadmin.crt
tar czf memberadmin-X.Y.Z.tar.gz -C /tmp/rel memberadmin
```
- Produit `appinfo/signature.json` (avec certificat embarqué).
- Vérif intégrité serveur : `occ integrity:check-app memberadmin` → exit 0.
- Valider `appinfo/info.xml` contre le schéma appstore :
  `xmllint --noout --schema <(curl -s https://apps.nextcloud.com/schema/apps/info.xsd) appinfo/info.xml`
  (⚠️ `<screenshot>` : pas d'attribut `type`, références d'images **existantes**).

## 5. Enregistrer l'app (une seule fois)
1. Connecté sur https://apps.nextcloud.com (compte développeur GitHub).
2. Page « Inscrire une application » :
   - **Certificat public** : contenu de `memberadmin.crt` (PEM complet).
   - **Signature ID** : `echo -n "memberadmin" | openssl dgst -sha512 -sign memberadmin.key | openssl base64`

## 6. Téléverser une release
1. Téléverser l'archive signée sur la release GitHub :
   `gh release upload vX.Y.Z memberadmin-X.Y.Z.tar.gz`
2. Page « Publier une version d'application » :
   - **Lien (tar.gz)** : `https://github.com/virtualdb24-rgb/memberadmin/releases/download/vX.Y.Z/memberadmin-X.Y.Z.tar.gz`
   - **Signature** : `openssl dgst -sha512 -sign memberadmin.key memberadmin-X.Y.Z.tar.gz | openssl base64`
3. Le store télécharge l'archive et vérifie la signature avec ton certificat
   (RSA PKCS1v15 SHA-512). Succès = « Version téléversée ».
4. Attendre la **revue** de l'équipe Nextcloud → apparition dans le catalogue.

### Pièges rencontrés (vérifiés)
- **Format de la signature** : coller la sortie `openssl base64` telle quelle
  (lignes de 64 caractères). Une version « sur une seule ligne » peut être rejetée.
- **`info.xml`** : l'attribut `type` sur `<screenshot>` fait échouer la validation
  du store (`Element 'screenshot', attribute 'type' is not allowed`).
- **Cache / octets** : si « signature invalid » persiste alors que la signature est
  bonne, re-téléverser l'archive sous un **nouveau nom d'asset unique** (nouvelle URL).
- Vérifier toujours sa signature localement avant soumission :
  ```bash
  openssl x509 -in memberadmin.crt -pubkey -noout > /tmp/pub.pem
  openssl base64 -d -in sig.txt -out /tmp/sig.bin
  openssl dgst -sha512 -verify /tmp/pub.pem -signature /tmp/sig.bin memberadmin-X.Y.Z.tar.gz  # → Verified OK
  ```

## Vérifications avant chaque release
- `php -l` sans erreur, code testé sur une instance.
- `info.xml` : `<version>` = tag, dépendances OK (min 27 / max 34), licence valide, screenshots existants.
- Archive contient `memberadmin/appinfo/info.xml` et `appinfo/signature.json`.
- CHANGELOG.md avec entrée du numéro de version (format `## X.Y.Z`).
