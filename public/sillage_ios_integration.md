# Design Document: Intégration Sillage (iOS) ↔ SillageGPX (Web)

Ce document décrit l'architecture et les développements nécessaires côté serveur (SillageGPX) pour permettre à l'application iOS Sillage de se synchroniser, d'envoyer ses traces GPX et de communiquer avec le backend.

## 1. Vue d'ensemble
L'objectif est de transformer SillageGPX, qui est pour l'instant un site web monolithique, en une **véritable API** capable de dialoguer avec une application mobile native. 
L'application iOS devra pouvoir :
- S'authentifier de manière sécurisée.
- Envoyer automatiquement ou manuellement une trace GPX enregistrée en mer.
- Récupérer la liste des traces existantes.

---

## 2. Authentification (Le gros morceau)

Le site web utilise actuellement les **Passkeys** pour l'authentification. C'est parfait pour le web, mais l'intégration mobile demande une réflexion.

### Option A : API Tokens (Classique & Simple)
On crée un système de jetons d'accès (API Keys ou JWT). 
- L'utilisateur se connecte sur l'app iOS (avec un mot de passe ou un passkey).
- Le serveur renvoie un `Bearer Token` valide pour X mois.
- L'app iOS utilise ce token dans les headers HTTP pour chaque requête.
- **Complexité côté serveur** : Faible/Moyenne (création d'une table `api_tokens`).

### Option B : Passkeys Natifs iOS (Moderne & Complexe)
Apple supporte les Passkeys natifs (via `ASAuthorizationPlatformPublicKeyCredentialProvider`). L'utilisateur pourrait se connecter sur l'app iOS avec FaceID, exactement comme sur le web.
- Nécessite d'héberger un fichier `apple-app-site-association` (AASA) sur le domaine web pour lier l'application iOS au domaine SillageGPX.
- **Complexité côté serveur** : Moyenne. (Il faut configurer le fichier AASA et adapter un peu les routes de challenge/verify pour renvoyer du JSON strict sans redirection).
- **Complexité côté iOS** : Élevée (Configuration des entitlements Apple).

> [!RECOMMENDATION]
> Je recommande de configurer le fichier **AASA** pour permettre l'authentification Passkey sur iOS, puis de délivrer un **API Token** persistant à l'application iOS pour simplifier les envois de GPX en arrière-plan (quand le réseau revient près des côtes).

---

## 3. Nouveaux Endpoints API à Créer

Il faudra créer des routes spécifiques (ex: `/api/v1/...`) qui ne renvoient **que du JSON** (pas de vues HTML).

### A. Endpoint d'Upload (`POST /api/v1/trips/upload`)
- **Rôle** : Recevoir un fichier `.gpx` depuis l'application iOS.
- **Payload** : `multipart/form-data` contenant le fichier `.gpx`, le titre de la navigation, et la confidentialité (public/privé).
- **Logique** : Vérifier le token, parser le GPX (comme on le fait déjà sur le web via `TripController`), enregistrer dans la base de données SQLite, et stocker le fichier.
- **Complexité** : Moyenne (On peut réutiliser 80% du code existant dans `TripController::handleCreate`).

### B. Endpoint de Synchronisation (`GET /api/v1/trips`)
- **Rôle** : Renvoyer la liste des navigations au format JSON pour que l'app iOS affiche un historique.
- **Complexité** : Faible (Simple `SELECT` avec formattage JSON).

### C. Endpoint de Téléchargement (`GET /api/v1/trips/{id}/gpx`)
- **Rôle** : Permettre à l'app iOS de télécharger une trace GPX existante pour la visualiser sur sa propre carte native (MapKit).
- **Complexité** : Faible.

---

## 4. Modifications sur la Base de Données

Il n'y aura pas besoin de bouleverser la base de données. Quelques ajouts mineurs :
1. **Table `api_tokens`** : Pour gérer les sessions persistantes de l'application iOS.
2. **Table `trips` (Optionnel)** : Ajouter une colonne `source VARCHAR` (ex: `"web"` ou `"ios"`) pour identifier d'où provient la trace.

---

## 5. Résumé de la Complexité Globale

| Tâche | Complexité Backend | Temps estimé |
|-------|-------------------|--------------|
| Configuration AASA (Apple App Site Association) | Faible | Rapide |
| Authentification API (Tokens) | Moyenne | 1 session |
| Endpoint Upload GPX (API) | Faible (code existant) | Rapide |
| Endpoints Listing/Download | Faible | Rapide |
| **Total** | **Moyenne** | **Très faisable** |

## Prochaines étapes suggérées :
1. Valider cette architecture (Préfères-tu des API Tokens ou une connexion via Passkey sur iOS ?).
2. Créer le fichier `apple-app-site-association` à la racine du dossier `public/`.
3. Créer un `ApiController` dédié pour séparer la logique Web de la logique Mobile.
