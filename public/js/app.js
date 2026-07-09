const API = "/api/cfp";
let token = localStorage.getItem("token");
let currentUser = null;

const messageEl = document.getElementById("message");

function showMessage(text, type = "success") {
    messageEl.textContent = text;
    messageEl.className = type;
    setTimeout(() => (messageEl.className = ""), 3000);
}

function showSection(id) {
    document
        .querySelectorAll("section")
        .forEach((s) => s.classList.remove("active"));
    document.getElementById(id).classList.add("active");
}

function showAuthForm(type) {
    document.getElementById("login-form").style.display =
        type === "login" ? "block" : "none";
    document.getElementById("register-form").style.display =
        type === "register" ? "block" : "none";
    document
        .getElementById("tab-login")
        .classList.toggle("active", type === "login");
    document
        .getElementById("tab-register")
        .classList.toggle("active", type === "register");
}

async function apiCall(endpoint, options = {}) {
    const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
    const res = await fetch(`${API}${endpoint}`, { ...options, headers });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || "Erreur inconnue");
    return data;
}

// --- AUTH ---
document.getElementById("login-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const data = await apiCall("/login", {
            method: "POST",
            body: JSON.stringify(Object.fromEntries(fd)),
        });
        onLoginSuccess(data);
    } catch (err) {
        showMessage(err.message, "error");
    }
});

document
    .getElementById("register-form")
    .addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            const data = await apiCall("/register", {
                method: "POST",
                body: JSON.stringify(Object.fromEntries(fd)),
            });
            onLoginSuccess(data);
        } catch (err) {
            showMessage(err.message, "error");
        }
    });

function onLoginSuccess(data) {
    token = data.data.token;
    localStorage.setItem("token", token);
    currentUser = data.data.user;
    showMessage("Connecté avec succès");
    renderApp();
}

document.getElementById("logout-btn").addEventListener("click", async () => {
    try {
        await apiCall("/logout", { method: "POST" });
    } catch (e) {}
    token = null;
    currentUser = null;
    localStorage.removeItem("token");
    renderApp();
});

// --- NAVIGATION selon rôle ---
function renderApp() {
    const roles = currentUser?.roles?.map((r) => r.name) || [];
    const isFormateur = roles.includes("formateur");
    const isApprenant = roles.includes("apprenant");

    document.getElementById("user-bar").style.display = currentUser
        ? "flex"
        : "none";

    if (currentUser) {
        document.getElementById("user-info").textContent =
            `${currentUser.name} (${roles.join(", ")})`;
    }

    const tabs = document.getElementById("tabs");
    tabs.innerHTML = "";

    // Toujours visible, connecté ou non
    addTab(tabs, "courses-section", "Catalogue", () => loadCourses());

    if (isFormateur) {
        addTab(tabs, "my-courses-section", "Mes formations", () =>
            loadMyCourses(),
        );
    }
    if (isApprenant) {
        addTab(tabs, "my-inscriptions-section", "Mes inscriptions", () =>
            loadMyInscriptions(),
        );
        addTab(tabs, "my-certificates-section", "Mes certificats", () =>
            loadMyCertificates(),
        );
    }

    addTab(tabs, "verify-section", "Vérifier un certificat", () => {});

    if (!currentUser) {
        addTab(tabs, "auth-section", "Connexion / Inscription", () => {});
    }

    if (currentUser) {
        showSection("courses-section");
        loadCourses();
    } else {
        showSection("courses-section");
        loadCourses();
    }
}

function addTab(container, sectionId, label, onClick) {
    const btn = document.createElement("button");
    btn.textContent = label;
    btn.onclick = () => {
        showSection(sectionId);
        onClick();
    };
    container.appendChild(btn);
}

// --- CATALOGUE (public) ---
async function loadCourses() {
    const courses = await apiCall("/courses");
    const list = document.getElementById("courses-list");
    list.innerHTML =
        courses
            .map(
                (c) => `
        <div class="card">
            <strong>${c.titre}</strong><br>
            <small>Par ${c.formateur?.name ?? "—"} · ${c.inscriptions_count} inscrit(s)</small>
            <p>${(c.description ?? "").slice(0, 100)}${c.description?.length > 100 ? "..." : ""}</p>
            <div class="card-actions">
                <button class="secondary" onclick="loadCourseDetail(${c.id})">Voir le détail</button>
            </div>
        </div>
    `,
            )
            .join("") || "<p>Aucune formation disponible.</p>";
}

// --- DETAIL D'UNE FORMATION (public) ---
async function loadCourseDetail(courseId) {
    const c = await apiCall(`/courses/${courseId}`);
    const isApprenant = currentUser?.roles?.some((r) => r.name === "apprenant");
    const isOwner = currentUser && c.formateur_id === currentUser.id;

    document.getElementById("course-detail").innerHTML = `
        <h2>${c.titre}</h2>
        <p><small>Par ${c.formateur?.name ?? "—"} · ${c.inscriptions_count} inscrit(s)</small></p>
        <p>${c.description ?? "Pas de description."}</p>
        ${
            isApprenant
                ? `<button onclick="enroll(${c.id})">S'inscrire à cette formation</button>`
                : ""
        }
        ${
            !currentUser
                ? `<p><small>Connectez-vous en tant qu'apprenant pour vous inscrire.</small></p>`
                : ""
        }
    `;
    showSection("course-detail-section");
}

async function enroll(courseId) {
    try {
        await apiCall(`/courses/${courseId}/register`, { method: "POST" });
        showMessage("Inscription réussie !");
        loadCourseDetail(courseId);
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- MES FORMATIONS (formateur) ---
async function loadMyCourses() {
    const courses = await apiCall("/my-courses");
    const list = document.getElementById("my-courses-list");
    list.innerHTML =
        courses
            .map(
                (c) => `
        <div class="card">
            <strong>${c.titre}</strong>
            <small> — ${c.inscriptions_count} inscrit(s)</small>
            <p>${c.description ?? ""}</p>
            <div class="card-actions">
                <button class="secondary" onclick="loadCourseInscriptions(${c.id}, '${c.titre.replace(/'/g, "\\'")}')">Voir les inscrits</button>
                <button class="secondary" onclick="openEditCourse(${c.id}, '${c.titre.replace(/'/g, "\\'")}', '${(c.description ?? "").replace(/'/g, "\\'")}')">Modifier</button>
                <button class="danger" onclick="deleteCourse(${c.id})">Supprimer</button>
            </div>
        </div>
    `,
            )
            .join("") || "<p>Vous n'avez pas encore créé de formation.</p>";
}

document
    .getElementById("create-course-form")
    .addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiCall("/courses", {
                method: "POST",
                body: JSON.stringify(Object.fromEntries(fd)),
            });
            showMessage("Formation créée");
            e.target.reset();
            loadMyCourses();
        } catch (err) {
            showMessage(err.message, "error");
        }
    });

// --- EDITER UNE FORMATION (formateur) ---
function openEditCourse(id, titre, description) {
    const form = document.getElementById("edit-course-form");
    form.id.value = id;
    form.titre.value = titre;
    form.description.value = description;
    showSection("edit-course-section");
}

document
    .getElementById("edit-course-form")
    .addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const id = fd.get("id");
        try {
            await apiCall(`/courses/${id}`, {
                method: "PUT",
                body: JSON.stringify({
                    titre: fd.get("titre"),
                    description: fd.get("description"),
                }),
            });
            showMessage("Formation modifiée");
            showSection("my-courses-section");
            loadMyCourses();
        } catch (err) {
            showMessage(err.message, "error");
        }
    });

async function deleteCourse(id) {
    if (!confirm("Supprimer cette formation ?")) return;
    try {
        await apiCall(`/courses/${id}`, { method: "DELETE" });
        showMessage("Formation supprimée");
        loadMyCourses();
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- INSCRITS A UNE FORMATION + changement de statut (formateur) ---
async function loadCourseInscriptions(courseId, titre) {
    document.getElementById("course-inscriptions-title").textContent =
        `Inscrits — ${titre}`;
    const inscriptions = await apiCall(`/courses/${courseId}/register`);
    const list = document.getElementById("course-inscriptions-list");
    list.innerHTML =
        inscriptions
            .map(
                (i) => `
        <div class="card">
            <strong>${i.user.name}</strong> — ${i.user.phone}
            <span class="badge ${i.statut}">${i.statut === "terminee" ? "Terminée" : "En cours"}</span>
            ${i.certificat ? `<p><small>Certificat : ${i.certificat.uuid}</small></p>` : ""}
            <div class="card-actions">
                ${
                    i.statut === "en_cours"
                        ? `<button onclick="updateInscriptionStatus(${i.id}, 'terminee', ${courseId}, '${titre.replace(/'/g, "\\'")}')">Marquer terminée (génère le certificat)</button>`
                        : `<button class="secondary" onclick="updateInscriptionStatus(${i.id}, 'en_cours', ${courseId}, '${titre.replace(/'/g, "\\'")}')">Repasser en cours</button>`
                }
            </div>
        </div>
    `,
            )
            .join("") || "<p>Aucun inscrit pour cette formation.</p>";
    showSection("course-inscriptions-section");
}

async function updateInscriptionStatus(inscriptionId, statut, courseId, titre) {
    try {
        await apiCall(`/registered/${inscriptionId}/status`, {
            method: "PUT",
            body: JSON.stringify({ statut }),
        });
        showMessage(
            statut === "terminee"
                ? "Formation marquée terminée, certificat généré"
                : "Statut mis à jour",
        );
        loadCourseInscriptions(courseId, titre);
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- MES INSCRIPTIONS (apprenant) ---
async function loadMyInscriptions() {
    const courses = await apiCall("/my-register-courses");
    const list = document.getElementById("my-inscriptions-list");
    list.innerHTML =
        courses
            .map(
                (c) => `
        <div class="card">
            <strong>${c.titre}</strong><br>
            <small>Par ${c.formateur?.name ?? "—"}</small>
        </div>
    `,
            )
            .join("") || "<p>Vous n'êtes inscrit à aucune formation.</p>";
}

// --- MES CERTIFICATS (apprenant) ---
async function loadMyCertificates() {
    const certificats = await apiCall("/my-certificates");
    const list = document.getElementById("my-certificates-list");
    console.log(certificats[0].date_emission);
    list.innerHTML =
        certificats
            .map(
                (c) => `
        <div class="card">
            <strong>${c.inscription.formation.titre}</strong><br>
            <small>Délivré le ${new Date(c.date_emission * 1000).toLocaleDateString()}</small><br>
            <small>UUID : ${c.uuid}</small>
        </div>
    `,
            )
            .join("") || "<p>Vous n'avez pas encore de certificat.</p>";
}

// --- VERIFICATION CERTIFICAT (public) ---
document.getElementById("verify-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const uuid = new FormData(e.target).get("uuid");
    const resultEl = document.getElementById("verify-result");
    try {
        const data = await apiCall(`/verify/${uuid}`);
        resultEl.innerHTML = `
            <div class="card">
                ✅ Certificat valide<br>
                <strong>${data.certificat.apprenant}</strong> — ${data.certificat.formation}<br>
                <small>Délivré le ${new Date(data.certificat.date_emission).toLocaleDateString()}</small>
            </div>`;
    } catch (err) {
        resultEl.innerHTML = `<div class="card">❌ ${err.message}</div>`;
    }
});

// --- INIT ---
if (token) {
    apiCall("/user")
        .then((u) => {
            currentUser = u;
            renderApp();
        })
        .catch(() => {
            token = null;
            renderApp();
        });
} else {
    renderApp();
}
