# Modèle de nommage & gestion par projet (memberadmin)

But : des **site owners** gèrent des groupes d'accès de **dossiers d'équipe (projets)**,
avec cloisonnement par projet, sans menu Accounts, et ajout/retrait de membres
uniquement.

## Convention de nommage (à respecter)
| Élément | Nommage | Exemple |
|---|---|---|
| Groupe de gestion du projet | `<Projet>-management` | `OrderHub-management` |
| Groupe accès interne | `<Projet>-Internal` | `OrderHub-Internal` |
| Groupe accès externe (clients B2B) | `<Projet>-External` | `OrderHub-External` |
| Team folder du projet | `<Projet>` | `OrderHub` |

## Règles
- Un **site owner n'est membre que des groupes `-management` des projets qu'il gère**
  (pas de groupe « tous les owners »).
- Un owner sur plusieurs projets est membre de plusieurs groupes `-management`
  → il gère uniquement ses projets (union).
- Chaque projet a ses propres `-Internal` / `-External` → cloisonnement.

## Comportement automatique de l'app
- Membre de `<Projet>-management` gère automatiquement
  `<Projet>-Internal` et `<Projet>-External` (détection automatique du suffixe
  `-management` ; aucun `memberadmin:role` ni `grant` requis).
- Ajouter/retirer un membre du groupe de gestion → l'accès suit automatiquement.

## Mise en place (admin, une fois par projet)
1. Créer les groupes : `occ group:add OrderHub-management`, `OrderHub-Internal`,
   `OrderHub-External`
2. Ajouter les membres :
   - gestionnaires du projet dans `OrderHub-management`
   - équipe interne dans `OrderHub-Internal`
   - clients externes (comptes) dans `OrderHub-External`
3. Créer le team folder `OrderHub` :
   - groupe d'accès `OrderHub-management` (perm. complètes + Partager) → co-gestion
   - groupe d'accès `OrderHub-Internal` (ex. lecture/écriture)
   - partage de groupe du dossier vers `OrderHub-External` (lecture) pour les clients
4. Facultatif : membres de `OrderHub-management` peuvent être désignés pour gérer
   la page « Team folders » (délégation admin, sans Accounts).

## Rappels
- Jamais de rôle « admin de groupe / manager of group » pour les site owners
  (sinon menu Accounts + droits de modification/suppression des comptes).
- Les groupes gérés doivent être **locaux**.
