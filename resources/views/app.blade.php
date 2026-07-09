<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Centre de Formation Professionnelle</title>
    <style>
        body {
            font-family: -apple-system, sans-serif;
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
            color: #1a1a1a;
        }

        h1 {
            border-bottom: 3px solid #ff2d20;
            padding-bottom: 10px;
        }

        section {
            display: none;
            margin-top: 20px;
        }

        section.active {
            display: block;
        }

        input,
        select,
        button,
        textarea {
            display: block;
            width: 100%;
            padding: 8px;
            margin: 6px 0;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            background: #ff2d20;
            color: #fff;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 4px;
        }

        button:hover {
            opacity: 0.9;
        }

        button.secondary {
            background: #eee;
            color: #333;
        }

        button.danger {
            background: #cf222e;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            margin: 10px 0;
        }

        .card small {
            color: #666;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .card-actions button {
            width: auto;
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .tabs button {
            width: auto;
            background: #eee;
            color: #333;
        }

        .tabs button.active {
            background: #ff2d20;
            color: #fff;
        }

        #message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }

        #message.success {
            background: #d1f4dd;
            color: #16653a;
            display: block;
        }

        #message.error {
            background: #fde2e1;
            color: #a3231e;
            display: block;
        }

        #user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        #user-bar button {
            width: auto;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .badge.en_cours {
            background: #fff3cd;
            color: #856404;
        }

        .badge.terminee {
            background: #d1f4dd;
            color: #16653a;
        }

        .back-link {
            color: #ff2d20;
            cursor: pointer;
            text-decoration: underline;
            margin-bottom: 10px;
            display: inline-block;
        }
    </style>
</head>

<body>

    <h1>Centre de Formation Professionnelle</h1>
    <div id="message"></div>

    <div id="user-bar" style="display:none;">
        <span id="user-info"></span>
        <button id="logout-btn" class="secondary">Déconnexion</button>
    </div>

    <div class="tabs" id="tabs"></div>

    <!-- AUTH -->
    <section id="auth-section" class="active">
        <div class="tabs">
            <button onclick="showAuthForm('login')" class="active" id="tab-login">Connexion</button>
            <button onclick="showAuthForm('register')" id="tab-register">Inscription</button>
        </div>

        <form id="login-form">
            <input type="text" name="phone" placeholder="Téléphone" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>

        <form id="register-form" style="display:none;">
            <input type="text" name="name" placeholder="Nom complet" required>
            <input type="text" name="phone" placeholder="Téléphone" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <select name="role" required>
                <option value="apprenant">Apprenant</option>
                <option value="formateur">Formateur</option>
            </select>
            <button type="submit">Créer un compte</button>
        </form>
    </section>

    <!-- CATALOGUE (public) -->
    <section id="courses-section">
        <h2>Catalogue de formations</h2>
        <div id="courses-list"></div>
    </section>

    <!-- DETAIL D'UNE FORMATION (public) -->
    <section id="course-detail-section">
        <span class="back-link" onclick="showSection('courses-section'); loadCourses();">&larr; Retour au
            catalogue</span>
        <div id="course-detail"></div>
    </section>

    <!-- MES FORMATIONS (formateur) -->
    <section id="my-courses-section">
        <h2>Mes formations</h2>
        <form id="create-course-form">
            <input type="text" name="titre" placeholder="Titre de la formation" required>
            <textarea name="description" placeholder="Description"></textarea>
            <button type="submit">Créer une formation</button>
        </form>
        <div id="my-courses-list"></div>
    </section>

    <!-- EDITER UNE FORMATION (formateur) -->
    <section id="edit-course-section">
        <span class="back-link" onclick="showSection('my-courses-section'); loadMyCourses();">&larr; Retour à mes
            formations</span>
        <h2>Modifier la formation</h2>
        <form id="edit-course-form">
            <input type="hidden" name="id">
            <input type="text" name="titre" placeholder="Titre" required>
            <textarea name="description" placeholder="Description"></textarea>
            <button type="submit">Enregistrer</button>
        </form>
    </section>

    <!-- INSCRITS A UNE FORMATION (formateur) -->
    <section id="course-inscriptions-section">
        <span class="back-link" onclick="showSection('my-courses-section'); loadMyCourses();">&larr; Retour à mes
            formations</span>
        <h2 id="course-inscriptions-title">Inscrits</h2>
        <div id="course-inscriptions-list"></div>
    </section>

    <!-- MES INSCRIPTIONS (apprenant) -->
    <section id="my-inscriptions-section">
        <h2>Mes inscriptions</h2>
        <div id="my-inscriptions-list"></div>
    </section>

    <!-- MES CERTIFICATS (apprenant) -->
    <section id="my-certificates-section">
        <h2>Mes certificats</h2>
        <div id="my-certificates-list"></div>
    </section>

    <!-- VERIFICATION CERTIFICAT (public) -->
    <section id="verify-section">
        <h2>Vérifier un certificat</h2>
        <form id="verify-form">
            <input type="text" name="uuid" placeholder="UUID du certificat" required>
            <button type="submit">Vérifier</button>
        </form>
        <div id="verify-result"></div>
    </section>

    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
