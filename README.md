# Centre de Formation Professionnelle — API

API REST développée avec **Laravel 13**, permettant de centraliser des centres de formation professionnelle : gestion des comptes à rôles cumulables (apprenant / formateur), catalogue de formations, inscriptions, et génération de certificats numériques vérifiables publiquement.

---

## Sommaire

- [Stack technique](#stack-technique)
- [Choix d'architecture](#choix-darchitecture)
- [Schéma de données](#schéma-de-données)
- [Installation et lancement](#installation-et-lancement)
- [Endpoints de l'API](#endpoints-de-lapi)
- [Hypothèses prises sur les zones ambiguës](#hypothèses-prises-sur-les-zones-ambiguës)
- [Limites connues](#limites-connues)
- [Tests](#tests)

---

## Stack technique

| Composant            | Choix                   |
| -------------------- | ----------------------- |
| Framework            | Laravel 13              |
| Base de données      | SQLite                  |
| Authentification     | Laravel Sanctum         |
| Génération de schéma | Laravel Shift Blueprint |

---

## Choix d'architecture

### Rôles cumulables sans héritage

Le sujet interdit explicitement de modéliser les rôles avec de l'héritage de classes/tables, ou avec un simple champ `type_utilisateur` unique — cette dernière approche empêcherait un compte de cumuler plusieurs rôles.

**Solution retenue : relation many-to-many (composition)**

```
users ⇄ role_user ⇄ roles
```

Une table pivot `role_user` relie `users` et `roles`. Un utilisateur peut ainsi avoir 0, 1 ou plusieurs rôles simultanément (ex: apprenant **et** formateur), sans dupliquer sa structure de compte ni utiliser d'héritage.

```php
// app/Models/User.php
public function roles()
{
    return $this->belongsToMany(Role::class);
}

public function hasRole(string $name): bool
{
    return $this->roles->contains('name', $name);
}
```

### Certificats vérifiables publiquement

Chaque certificat possède :

- Un `id` interne auto-incrémenté (clé primaire, utilisé uniquement pour les relations en base, jamais exposé).
- Un `uuid` public, généré automatiquement à la création (`Str::uuid()`), utilisé exclusivement dans l'URL de vérification.

Cette séparation évite qu'un tiers puisse deviner ou énumérer les certificats existants en parcourant des identifiants séquentiels (`/verify/1`, `/verify/2`, ...).

La route `GET /api/cfp/verify/{uuid}` est volontairement placée **en dehors** du groupe de middleware `auth:sanctum`, afin d'être accessible sans authentification — un tiers externe (employeur, autre centre) doit pouvoir vérifier un certificat sans posséder de compte sur la plateforme.

### Séparation public / privé

| Type de donnée                                     | Accès                                            |
| -------------------------------------------------- | ------------------------------------------------ |
| Catalogue de formations (liste + détail)           | Public                                           |
| Vérification de certificat                         | Public                                           |
| Création / modification / suppression de formation | Authentifié (formateur, propriétaire uniquement) |
| Inscription à une formation                        | Authentifié (apprenant)                          |
| Mes formations / mes inscriptions                  | Authentifié (rôle correspondant)                 |

### Écrans différenciés selon le rôle

Le comportement de l'API varie selon le rôle de l'utilisateur connecté, plutôt que d'exposer un seul endpoint générique :

- `GET /api/cfp/my-courses` → réservé aux formateurs, retourne les formations qu'ils enseignent.
- `GET /api/cfp/my-register-courses` → réservé aux apprenants, retourne les formations auxquelles ils sont inscrits.
- `GET /api/cfp/courses` reste commun et public (catalogue global).

---

## Schéma de données

```
users ──┬── role_user ──── roles
        │
        ├── formations (formateur_id)
        │
        └── inscriptions ──┬── formations
                            └── certificats
```

| Table          | Colonnes clés                                        | Rôle                                                                    |
| -------------- | ---------------------------------------------------- | ----------------------------------------------------------------------- |
| `users`        | id, name, phone (unique), email (nullable), password | Compte unique par personne, identifié par téléphone                     |
| `roles`        | id, name                                             | Référentiel des rôles (apprenant, formateur)                            |
| `role_user`    | user_id, role_id                                     | Table pivot — permet le cumul de rôles                                  |
| `formations`   | id, titre, description, formateur_id                 | Catalogue de formations                                                 |
| `inscriptions` | id, user_id, formation_id, statut, date_inscription  | Lien apprenant ↔ formation, avec contrainte unique évitant les doublons |
| `certificats`  | id, uuid (unique), inscription_id, date_emission     | Certificat généré à la fin d'une formation, vérifiable via son UUID     |



Les drivers correspondants ont été simplifiés dans `.env` :

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

## Installation et lancement

### Prérequis

- PHP > 8.2
- Composer
- SQLite

### Étapes

1. **Cloner le dépôt et installer les dépendances**

```bash
git clone https://github.com/tokyNomenjanahary/CFP-Stage.git
cd CFP-Stage
composer install
```

2. **Configurer l'environnement**

```bash
cp .env.example .env
php artisan key:generate
```

3. **Créer la base de données SQLite**

```bash
touch database/database.sqlite
```

Vérifier que `.env` contient bien :

```dotenv
DB_CONNECTION=sqlite
```

4. **Lancer les migrations et le seeder**

```bash
php artisan migrate:fresh --seed
```

Cela crée le schéma complet et génère des données de test (rôles, formateurs, formations, apprenants, inscriptions).

5. **Lancer le serveur de développement**

```bash
php artisan serve
```

L'API est accessible sur `http://localhost:8000/api/cfp`.

### Réinitialiser les données à tout moment

```bash
php artisan migrate:fresh --seed
```

---

## Endpoints de l'API

Toutes les routes sont préfixées par `/api/cfp`.

### Authentification (publiques)

| Méthode | Route       | Description                                        |
| ------- | ----------- | -------------------------------------------------- |
| POST    | `/register` | Créer un compte (name, phone, password, role)      |
| POST    | `/login`    | Se connecter (phone, password) → retourne un token |

### Formations (publiques en lecture)

| Méthode | Route               | Auth               | Description                 |
| ------- | ------------------- | ------------------ | --------------------------- |
| GET     | `/courses`          | Non                | Liste toutes les formations |
| GET     | `/courses/{course}` | Non                | Détail d'une formation      |
| POST    | `/courses`          | Oui (formateur)    | Créer une formation         |
| PUT     | `/courses/{course}` | Oui (propriétaire) | Modifier une formation      |
| DELETE  | `/courses/{course}` | Oui (propriétaire) | Supprimer une formation     |
| GET     | `/my-courses`       | Oui (formateur)    | Mes formations enseignées   |

### Inscriptions (authentifiées)

| Méthode | Route                                  | Auth            | Description                                           |
| ------- | -------------------------------------- | --------------- | ----------------------------------------------------- |
| GET     | `/my-register-courses`                 | Oui (apprenant) | Mes formations suivies                                |
| POST    | `/courses/{course}/register`           | Oui (apprenant) | S'inscrire à une formation                            |
| POST    | `/register/{inscription}/finish`       | Oui (formateur) | Marquer une formation terminée → génère le certificat |

### Certificats

| Méthode | Route            | Auth    | Description                           |
| ------- | ---------------- | ------- | ------------------------------------- |
| GET     | `/verify/{uuid}` | **Non** | Vérification publique d'un certificat |

### Compte

| Méthode | Route     | Auth | Description                             |
| ------- | --------- | ---- | --------------------------------------- |
| GET     | `/user`   | Oui  | Informations du compte connecté + rôles |
| POST    | `/logout` | Oui  | Déconnexion (révoque le token)          |

---

## Hypothèses prises sur les zones ambiguës

Le sujet contient volontairement des zones sous-spécifiées. Voici les décisions prises et leur justification :

1. **Catalogue de formations public** : le sujet ne précise pas si la liste des formations doit être accessible sans compte. Choix fait de la rendre publique (comme un catalogue e-commerce), car un visiteur doit pouvoir consulter l'offre avant de créer un compte. L'inscription, elle, reste protégée.

2. **Un formateur ne peut pas s'inscrire à sa propre formation** : règle métier non explicitée mais logique, ajoutée par cohérence.

3. **Écrans différenciés par rôle** : interprétés côté API comme des réponses différentes selon le rôle de l'utilisateur connecté (`my-courses` vs `my-register-courses`), plutôt que des vues front-end distinctes, le sujet demandant explicitement une API en priorité.

4. **Génération du certificat** : déclenchée automatiquement dès qu'une inscription passe au statut `terminee`, plutôt que par une action manuelle séparée.

---

## Limites connues

- Pas de gestion des tokens expirés/révoqués au-delà du comportement par défaut de Sanctum.
- Pas de pagination sur les listes (`/courses`, `/my-courses`, etc.) — acceptable vu le volume de données du périmètre de test, mais à ajouter pour une mise en production.
- Le mécanisme de parrainage (bonus) [préciser ici si implémenté ou non, et son état].
- Pas de tests automatisés exhaustifs (voir section Tests) — le temps a été priorisé sur la modélisation et l'API elle-même.
- Pas d'interface graphique — le périmètre obligatoire du sujet est l'API, l'interface étant mentionnée comme "si le temps le permet".

---

## Tests

Pour vérifier le bon fonctionnement du cumul de rôles, des inscriptions et de la génération de certificats :

```bash
php artisan tinker
```

```php
// Vérifier le cumul de rôles
$user = \App\Models\User::factory()->create();
$user->roles()->attach(\App\Models\Role::where('name', 'apprenant')->first());
$user->roles()->attach(\App\Models\Role::where('name', 'formateur')->first());
$user->fresh()->roles->pluck('name'); // ["apprenant", "formateur"]

// Vérifier la génération de certificat
$inscription = \App\Models\Inscription::factory()->terminee()->create();
$inscription->certificat->uuid;
```

Des tests PHPUnit/Pest peuvent être lancés avec :

```bash
php artisan test
```
