<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>API — Centre de Formation Professionnelle</title>
    <style>
        body {
            font-family: -apple-system, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            color: #1a1a1a;
            line-height: 1.6;
        }

        h1 {
            border-bottom: 3px solid #ff2d20;
            padding-bottom: 10px;
        }

        h2 {
            margin-top: 40px;
            background: #f5f5f5;
            padding: 8px 12px;
            border-left: 4px solid #ff2d20;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            text-align: left;
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        th {
            background: #fafafa;
        }

        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        .method {
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #fff;
        }

        .get {
            background: #2ea44f;
        }

        .post {
            background: #0969da;
        }

        .put {
            background: #9a6700;
        }

        .delete {
            background: #cf222e;
        }

        .public {
            color: #2ea44f;
            font-weight: bold;
        }

        .private {
            color: #cf222e;
            font-weight: bold;
        }

        .example {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <h1>API — Centre de Formation Professionnelle</h1>
    <p>Documentation des endpoints disponibles. Base URL : <code>{{ url('/api/cfp') }}</code></p>

    <h2>Authentification</h2>
    <table>
        <tr>
            <th>Méthode</th>
            <th>Route</th>
            <th>Accès</th>
            <th>Description</th>
        </tr>
        <tr>
            <td><span class="method post">POST</span></td>
            <td><code>/api/cfp/register</code></td>
            <td class="public">Public</td>
            <td>Créer un compte (name, phone, password, role)</td>
        </tr>
        <tr>
            <td><span class="method post">POST</span></td>
            <td><code>/api/cfp/login</code></td>
            <td class="public">Public</td>
            <td>Se connecter (phone, password) → retourne un token</td>
        </tr>
        <tr>
            <td><span class="method post">POST</span></td>
            <td><code>/api/cfp/logout</code></td>
            <td class="private">Authentifié</td>
            <td>Déconnexion (révoque le token)</td>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/user</code></td>
            <td class="private">Authentifié</td>
            <td>Infos du compte connecté + rôles</td>
        </tr>
    </table>

    <h2>Formations</h2>
    <table>
        <tr>
            <th>Méthode</th>
            <th>Route</th>
            <th>Accès</th>
            <th>Description</th>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/courses</code></td>
            <td class="public">Public</td>
            <td>Liste toutes les formations</td>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/courses/{course}</code></td>
            <td class="public">Public</td>
            <td>Détail d'une formation</td>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/my-courses</code></td>
            <td class="private">Formateur</td>
            <td>Mes formations enseignées</td>
        </tr>
        <tr>
            <td><span class="method post">POST</span></td>
            <td><code>/api/cfp/courses</code></td>
            <td class="private">Formateur</td>
            <td>Créer une formation</td>
        </tr>
        <tr>
            <td><span class="method put">PUT</span></td>
            <td><code>/api/cfp/courses/{course}</code></td>
            <td class="private">Propriétaire</td>
            <td>Modifier une formation</td>
        </tr>
        <tr>
            <td><span class="method delete">DELETE</span></td>
            <td><code>/api/cfp/courses/{course}</code></td>
            <td class="private">Propriétaire</td>
            <td>Supprimer une formation</td>
        </tr>
    </table>

    <h2>Inscriptions</h2>
    <table>
        <tr>
            <th>Méthode</th>
            <th>Route</th>
            <th>Accès</th>
            <th>Description</th>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/my-enrolled-courses</code></td>
            <td class="private">Student</td>
            <td>Mes formations suivies</td>
        </tr>
        <tr>
            <td><span class="method post">POST</span></td>
            <td><code>/api/cfp/courses/{course}/register</code></td>
            <td class="private">Student</td>
            <td>S'inscrire à une formation</td>
        </tr>
        <tr>
            <td><span class="method put">PUT</span></td>
            <td><code>/api/cfp/registrations/{registration}/status</code></td>
            <td class="private">Instructor</td>
            <td>Marquer une inscription terminée → génère le certificat</td>
        </tr>
    </table>

    <h2>Certificats</h2>
    <table>
        <tr>
            <th>Méthode</th>
            <th>Route</th>
            <th>Accès</th>
            <th>Description</th>
        </tr>
        <tr>
            <td><span class="method get">GET</span></td>
            <td><code>/api/cfp/verify/{uuid}</code></td>
            <td class="public">Public</td>
            <td>Vérification publique d'un certificat</td>
        </tr>
    </table>

    <h2>Mode d'emploi rapide</h2>

    <p><strong>1. Créer un compte instructor</strong></p>
    <div class="example">curl -X POST {{ url('/api/cfp/register') }} \
        -H "Content-Type: application/json" -H "Accept: application/json" \
        -d '{"name":"Marc","phone":"0341111111","password":"password123","role":"instructor"}'</div>

    <p><strong>2. Se connecter et récupérer un token</strong></p>
    <div class="example">curl -X POST {{ url('/api/cfp/login') }} \
        -H "Content-Type: application/json" -H "Accept: application/json" \
        -d '{"phone":"0341111111","password":"password123"}'</div>

    <p><strong>3. Utiliser le token sur une route protégée</strong></p>
    <div class="example">curl {{ url('/api/cfp/my-courses') }} \
        -H "Authorization: Bearer VOTRE_TOKEN" \
        -H "Accept: application/json"</div>

    <p><strong>4. Vérifier un certificat (sans authentification)</strong></p>
    <div class="example">curl {{ url('/api/cfp/verify/UUID_DU_CERTIFICAT') }} \
        -H "Accept: application/json"</div>

    <p style="margin-top: 40px; color: #666; font-size: 13px;">
        Toutes les routes protégées nécessitent l'en-tête <code>Authorization: Bearer &lt;token&gt;</code>,
        obtenu via <code>/register</code> ou <code>/login</code>.
        Pensez à toujours envoyer <code>Accept: application/json</code>.
    </p>

</body>

</html>
