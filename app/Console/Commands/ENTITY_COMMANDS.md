# Commandes de Gestion d'Entités

Ce projet inclut deux commandes Artisan puissantes pour automatiser la création et la suppression complète d'entités dans votre application Laravel.

## Table des matières

- [make:entity - Génération d'entités](#makeentity---génération-dentités)
- [delete:entity - Suppression d'entités](#deleteentity---suppression-dentités)
- [Exemples pratiques](#exemples-pratiques)

---

## make:entity - Génération d'entités

La commande `make:entity` génère automatiquement tous les fichiers nécessaires pour une entité complète dans votre application Laravel.

### Synopsis

```bash
php artisan make:entity {name} [options]
```

### Arguments

| Argument | Description | Exemple |
|----------|-------------|---------|
| `name` | Nom de l'entité en StudlyCase | `Post`, `AcademicYear`, `StudentEnrollment` |

### Options

| Option | Valeurs | Description | Défaut |
|--------|---------|-------------|--------|
| `--source` | `db` ou `migration` | Source d'inférence du schéma | `db` |
| `--table` | Nom de table | Nom de la table (si --source=db) | snake_case pluriel du nom |
| `--migration` | Chemin fichier | Chemin du fichier de migration (si --source=migration) | - |
| `--no-resources` | Flag | Ne pas générer Resources/Collections | false |
| `--no-factory` | Flag | Ne pas générer Factory | false |
| `--no-seeder` | Flag | Ne pas générer Seeder | false |
| `--no-collection-json` | Flag | Ne pas générer la collection API JSON | false |
| `--force` | Flag | Écraser les fichiers existants | false |

### Fichiers générés

La commande génère les fichiers suivants :

1. **Model** : `app/Models/{Name}.php`
   - Propriétés `$fillable` et `$casts` automatiquement remplies
   - Relations `belongsTo` générées pour les clés étrangères
   - Support du SoftDeletes si détecté

2. **Repository** : `app/Repositories/{Name}Repository.php`
   - Méthodes CRUD standard : `all()`, `paginate()`, `find()`, `create()`, `update()`, `delete()`

3. **Controller** : `app/Http/Controllers/{Name}Controller.php`
   - Actions API Resource complètes : `index()`, `store()`, `show()`, `update()`, `destroy()`
   - Injection de dépendance du Repository

4. **Form Requests** :
   - `app/Http/Requests/{Name}/Store{Name}Request.php`
   - `app/Http/Requests/{Name}/Update{Name}Request.php`
   - Règles de validation générées automatiquement avec :
     - Gestion des champs `nullable`
     - Contraintes `unique` avec `ignore()` pour les updates
     - Validation `exists` pour les clés étrangères
     - Support des enums MySQL

5. **API Resources** :
   - `app/Http/Resources/{Name}Resource.php`
   - `app/Http/Resources/{Name}Collection.php`

6. **Factory** : `database/factories/{Name}Factory.php`
   - Données faker adaptées aux types de colonnes
   - Relations factory pour les clés étrangères

7. **Seeder** : `database/seeders/{Name}Seeder.php`
   - Génère 20 enregistrements par défaut

8. **Collection API JSON** : `storage/api-collections/{Name}_collection.json`
   - Collection Postman prête à l'emploi avec tous les endpoints
   - Exemples de body pour POST et PUT

### Modes d'inférence

#### Mode base de données (--source=db)

Inspecte une table existante dans votre base de données et en déduit le schéma.

**Prérequis** : La table doit exister dans la base de données.

```bash
php artisan make:entity Post --source=db
php artisan make:entity AcademicYear --source=db --table=academic_years
```

**Avantages** :
- Détecte automatiquement tous les types de colonnes
- Inspecte les contraintes réelles (UNIQUE, FOREIGN KEY)
- Support MySQL et PostgreSQL

#### Mode migration (--source=migration)

Parse un fichier de migration Laravel et en déduit le schéma.

**Prérequis** : Le fichier de migration doit exister.

```bash
php artisan make:entity Grade --source=migration --migration=database/migrations/2025_12_27_135105_create_grades_table.php
```

**Avantages** :
- Fonctionne avant l'exécution des migrations
- Utile pour la génération en développement
- Ne nécessite pas de connexion base de données

### Inférence automatique

La commande détecte intelligemment :

- **Types de données** : converti en casts Laravel appropriés
- **Champs nullable** : règles de validation `nullable` vs `required`
- **Contraintes UNIQUE** : règles `unique` avec `ignore()` pour les updates
- **Clés étrangères** :
  - Règles `exists` dans les Form Requests
  - Relations `belongsTo` dans le Model
  - Factory avec références aux modèles liés
- **Enums MySQL** : règles `in:value1,value2,...`
- **Soft Deletes** : trait `SoftDeletes` ajouté au Model

### Drivers supportés

- MySQL
- PostgreSQL

### Workflow complet

Après génération, suivez ces étapes :

1. **Ajouter la route** dans `routes/api.php` :
```php
Route::apiResource('posts', \App\Http\Controllers\PostController::class);
```

2. **Ajouter le seeder** dans `database/seeders/DatabaseSeeder.php` :
```php
$this->call(PostSeeder::class);
```

3. **Tester l'API** avec la collection Postman générée dans `storage/api-collections/`

---

## delete:entity - Suppression d'entités

La commande `delete:entity` supprime tous les fichiers générés par `make:entity` pour une entité donnée.

### Synopsis

```bash
php artisan delete:entity {name} [options]
```

### Arguments

| Argument | Description | Exemple |
|----------|-------------|---------|
| `name` | Nom de l'entité en StudlyCase | `Post`, `AcademicYear` |

### Options

| Option | Description |
|--------|-------------|
| `--force` | Supprimer sans confirmation |

### Fichiers supprimés

La commande supprime automatiquement :

1. `app/Models/{Name}.php`
2. `app/Repositories/{Name}Repository.php`
3. `app/Http/Controllers/{Name}Controller.php`
4. `app/Http/Requests/{Name}/Store{Name}Request.php`
5. `app/Http/Requests/{Name}/Update{Name}Request.php`
6. `app/Http/Resources/{Name}Resource.php`
7. `app/Http/Resources/{Name}Collection.php`
8. `database/factories/{Name}Factory.php`
9. `database/seeders/{Name}Seeder.php`
10. `storage/api-collections/{Name}_collection.json`

**Nettoyage automatique** : Si le dossier `app/Http/Requests/{Name}` devient vide, il est également supprimé.

### Mode interactif

Par défaut, la commande demande confirmation pour chaque fichier :

```bash
php artisan delete:entity Post
```

Sortie :
```
Supprimer /path/to/app/Models/Post.php ? (yes/no) [no]:
```

### Mode force

Supprime tous les fichiers sans confirmation :

```bash
php artisan delete:entity Post --force
```

---

## Exemples pratiques

### Exemple 1 : Génération depuis la base de données

Vous avez déjà une table `students` dans votre base de données :

```bash
php artisan make:entity Student --source=db
```

Résultat :
```
📦 Inférence DB réussie (mysql) : students
✅ Model : app/Models/Student.php
✅ Repository : app/Repositories/StudentRepository.php
✅ StoreRequest : app/Http/Requests/Student/StoreStudentRequest.php
✅ UpdateRequest : app/Http/Requests/Student/UpdateStudentRequest.php
✅ Controller : app/Http/Controllers/StudentController.php
✅ Resource : app/Http/Resources/StudentResource.php
✅ ResourceCollection : app/Http/Resources/StudentCollection.php
✅ Factory : database/factories/StudentFactory.php
✅ Seeder : database/seeders/StudentSeeder.php
✅ Collection API JSON : storage/api-collections/Student_collection.json

✨ Terminé. Ajoute la route : Route::apiResource('students', \App\Http\Controllers\StudentController::class);
```

### Exemple 2 : Génération depuis une migration

Vous venez de créer une migration mais ne l'avez pas encore exécutée :

```bash
php artisan make:migration create_grades_table
# Éditer la migration...

php artisan make:entity Grade --source=migration --migration=database/migrations/2025_12_27_135105_create_grades_table.php
```

### Exemple 3 : Génération minimale (sans Factory/Seeder)

Pour un modèle simple sans données de test :

```bash
php artisan make:entity Setting --source=db --no-factory --no-seeder --no-collection-json
```

### Exemple 4 : Régénération avec --force

Vous avez modifié votre table et voulez régénérer tous les fichiers :

```bash
php artisan make:entity Post --source=db --force
```

Tous les fichiers existants seront écrasés.

### Exemple 5 : Suppression d'une entité

Vous voulez supprimer une entité de test :

```bash
# Mode interactif
php artisan delete:entity TestEntity

# Mode force (sans confirmation)
php artisan delete:entity TestEntity --force
```

### Exemple 6 : Table avec nom personnalisé

Votre table ne suit pas la convention de nommage :

```bash
php artisan make:entity AcademicYear --source=db --table=custom_years_table
```

---

## Notes importantes

### Inférence des types

| Type SQL | Cast Laravel | Règle validation | Faker |
|----------|--------------|------------------|-------|
| varchar, text | string | string, max:{length} | sentence() |
| int, bigint | integer | integer | numberBetween(1, 9999) |
| boolean | boolean | boolean | boolean() |
| decimal, float | float | numeric | randomFloat(2, 0, 9999) |
| date | date | date | date('Y-m-d') |
| datetime, timestamp | datetime | date | dateTime() |
| json, jsonb | array | array | [] |
| enum | string | in:val1,val2 | sentence() |

### Limitations

- **Migration parser** : Parse uniquement les colonnes définies avec `$table->method('name')`. Les colonnes complexes ou dynamiques peuvent ne pas être détectées.
- **Enums** : Support MySQL uniquement pour l'inférence DB. En mode migration, les valeurs enum doivent être définies dans le tableau.
- **Relations** : Seules les relations `belongsTo` sont générées. Les relations `hasMany`, `hasOne`, `belongsToMany` doivent être ajoutées manuellement.
- **Validation complexe** : Les règles de validation générées sont basiques. Ajoutez des règles personnalisées selon vos besoins.

### Bonnes pratiques

1. **Versionner les migrations** : Utilisez toujours des migrations pour définir votre schéma
2. **Tester avant --force** : Vérifiez les fichiers existants avant d'utiliser `--force`
3. **Personnaliser après génération** : Les fichiers générés sont des points de départ, personnalisez-les selon vos besoins
4. **Supprimer les routes** : Après `delete:entity`, n'oubliez pas de supprimer manuellement les routes dans `routes/api.php`
5. **UUID support** : Si votre modèle utilise UUID, le code généré fonctionne avec `int|string` dans les signatures

---

## Support et contributions

Ces commandes utilisent :
- `illuminate/database` pour l'introspection de schéma
- `illuminate/filesystem` pour la génération de fichiers
- `illuminate/support/Str` pour les conventions de nommage Laravel

Pour toute question ou amélioration, consultez la documentation Laravel ou contactez l'équipe de développement.
