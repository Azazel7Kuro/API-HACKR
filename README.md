# API - HACKR
### NGUYEN Thomas - MBA DEV

## Installation du projet en local

1. Cloner le projet
2. Installer les dépendances avec ```composer install```
3. Créer un fichier .env à la racine du projet et y ajouter les informations de connexion à la base de données
4. Génerer la clé d'application avec ```php artisan key:generate```
5. Créer la base de données avec ```php artisan migrate```
6. Lancer le serveur avec ```php artisan serve```

## Fonctionnalités

* Outil de vérification d'existence d'adresse mail ✅
* Spammer de mail (contenu + nombre d'envoi) ✅
* Générateur de mot de passe sécurisé ✅
* Est-ce que le MDP est sur la liste des plus courants ✅
* récupérer tous domaines & sous-domaines associés à un Nom De Domaine ✅
* Génération d'identité fictive => utilisez la lirairie Faker ! ✅
* changement d'image random ✅

## Obligations

* Contrôller l'accès à votre API grâce à un système de connexion basé sur JWT. ✅
* Intégrer un fichier Swagger.json pour la partie documentation. ✅
* Mettre en place un système de droits, gérable par des administrateurs, qui permet de définir quelles fonctionnalités peuvent être utilisées par quel utilisateur. ✅
* Vous allez mettre en place un système de logs, interne à l'API, et consultable uniquement par les admins, qui permet de savoir quelles sont :

  - les dernièrs actions réalisées ✅
  - les dernières actions d'un utilisateur spécifique ✅
  - les dernières actions d'une fonctionnalité spécifique ✅

* Vous devrez obligatoirement tester votre API via POSTMAN. En y incluant :

  - Organiser vos routes en collection et dans un projet ✅
  - Automatisant la génération du bearer et sa transmission dans toutes les requêtes. (Bearer = JWT) ✅

