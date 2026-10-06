# 🌿 AgroSentinel — Agriculture intelligente (version web de démonstration)

Plateforme de diagnostic agricole pour les producteurs de **cacao, mangue et tomate** en Côte d'Ivoire.
Cette version web reprend les fonctions du prototype mobile *AgroSentinel* (Expo / React Native) afin de pouvoir être hébergée sur un hébergement mutualisé cPanel, sans Composer, sans SSH et sans Node.js.

> ⚠️ **Prototype de démonstration.** Le diagnostic par IA est **simulé** (voir « Limites »). Les fiches de conseils, elles, sont réelles.

## Fonctionnalités

| Écran | Description |
|---|---|
| Diagnostic par photo | Choix de la culture, photo, maladie probable, niveau de risque, fiche de conseils (action immédiate, prévention, traitement), lecture audio, historique 30 jours |
| Météo agricole | Données Open-Meteo, indices de risque (mildiou, pourriture brune), prévision 3 jours |
| Carte des diagnostics | Carte Leaflet / OpenStreetMap, position **facultative** et arrondie à 3 décimales |
| Calculateur de scénario | Estimation de perte et de valeur préservée selon surface, culture et prix |
| Marché | Publier une offre, acheter (simulation, aucun paiement réel) |
| Groupage logistique | Volumes récoltés par zone, déclaration de récolte |
| Tableau de bord national | Indicateurs agrégés, réservé au rôle administrateur |

## Pile technique

- **Back-end** : Laravel (11 ou plus récent), API REST JSON, requêtes via `DB::table`
- **Authentification** : jetons d'API stockés **hachés (SHA-256)**, expiration 7 jours, rôles `producteur` / `admin`
- **Base de données** : MySQL (InnoDB, utf8mb4)
- **Front-end** : React 18 + htm + Tailwind (CDN), **sans compilation**, PWA installable
- **Carte** : Leaflet 1.9 · **Météo** : Open-Meteo · **Voix** : Web Speech API
- **Design** : couche `modern.css` / `modern.js` (animations, compteurs, squelettes de chargement, mode sombre)

## Structure

```
laravel-files/        fichiers à copier DANS un projet Laravel existant
  app/Http/Controllers/   AuthController, AgroController
  app/Http/Middleware/    ApiAuth (jeton + rôles)
  app/Support/recos.php   19 fiches de recommandations phytosanitaires (extraites du prototype mobile)
  bootstrap/app.php       enregistre les routes API et le middleware
  routes/                 api.php, web.php
  public/                 app.html (application), modern.css/js, manifest, service worker, icône
database/agrosentinel.sql   tables + comptes et données de démonstration
```

## Installation sur cPanel

1. Créer un sous-domaine et le passer en **PHP 8.3** (MultiPHP Manager).
2. Installer Laravel avec l'installateur d'applications, dans un dossier `laravel`.
3. Régler la racine du document sur `…/laravel/public`.
4. Dans `laravel/.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine`, `APP_TIMEZONE=Africa/Abidjan`.
5. Copier le contenu de `laravel-files/` dans le dossier `laravel` (accepter de remplacer `bootstrap/app.php` et `routes/web.php`).
6. Dans phpMyAdmin, ouvrir la base du projet, onglet **SQL**, importer `database/agrosentinel.sql`.
   > Ce script **supprime et recrée la table `users`** : ne jamais l'exécuter sur la base d'un autre projet.

## Comptes de test

Mot de passe : `Demo@2026`

| Rôle | E-mail |
|---|---|
| Administrateur | admin@agrosentinel.ci |
| Producteur | producteur@agrosentinel.ci |

> 🔒 **À supprimer ou à changer avant toute mise en ligne publique** (et retirer les boutons de comptes de test dans `app.html`).

## API

| Méthode | Route | Accès |
|---|---|---|
| POST | `/api/auth/register`, `/api/auth/login` | public (10 req/min) |
| GET | `/api/cultures`, `/api/offers`, `/api/zones` | public |
| POST | `/api/diagnostics` | connecté — le serveur valide la maladie, calcule le risque, associe la fiche et enregistre |
| GET | `/api/diagnostics/mine`, `/api/diagnostics/map` | connecté (l'admin voit tout) |
| POST | `/api/offers`, `/api/offers/{id}/buy`, `/api/zones/{id}/harvest` | connecté |
| GET | `/api/dashboard` | **admin uniquement** |

## Sécurité et confidentialité

- Position GPS facultative, arrondie à 3 décimales, jamais remplacée par une fausse position
- Historique limité aux 30 derniers jours
- Achat d'une offre protégé contre le double achat (mise à jour conditionnelle en base)
- Limitation de débit sur la connexion et l'inscription
- Contrôle des rôles côté serveur sur chaque route sensible

## Limites connues

- **Le diagnostic est simulé** : la maladie est tirée au hasard parmi les classes réelles de la culture. Le prototype mobile utilisait 3 modèles TFLite embarqués ; ils ne sont pas exécutables tels quels dans un navigateur. Pour un vrai diagnostic, convertir les modèles en TensorFlow.js (ou exposer une API d'inférence côté serveur), puis remplacer la simulation dans `app.html` et enregistrer `source = 'tflite'`.
- Les modèles du dépôt d'origine sont stockés avec **Git LFS** : vérifier qu'ils sont bien récupérés.
- Paiement mobile money, synchronisation nationale et mode hors-ligne : non implémentés.
- Rendements et hypothèses du calculateur : données de démonstration à valider.

## Feuille de route

1. Brancher de vrais modèles d'IA validés sur un jeu de tests métier
2. Paiement Mobile Money côté serveur via un prestataire certifié
3. Mode hors-ligne (cache des fiches et file d'envoi des diagnostics)
4. Chiffrement, consentement et export/suppression des données personnelles

## Avertissement

Les recommandations phytosanitaires sont indicatives. Consultez toujours un conseiller agricole agréé (ANADER) pour valider produits, doses et homologations.
