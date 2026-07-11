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
    const isInstructor = roles.includes("instructor");
    const isStudent = roles.includes("student");

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

    if (isInstructor) {
        addTab(tabs, "my-courses-section", "Mes formations", () =>
            loadMyCourses(),
        );
    }
    if (isStudent) {
        addTab(tabs, "my-inscriptions-section", "Mes inscriptions", () =>
            loadMyInscriptions(),
        );
        addTab(tabs, "my-certificates-section", "Mes certificats", () =>
            loadMyCertificates(),
        );
    }

    if (currentUser) {
        addTab(tabs, "profile-section", "Mon compte", () => loadProfile());
        addTab(tabs, "referral-section", "Parrainage", () => loadReferrals());
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
            <strong>${c.title}</strong><br>
            <small>Par ${c.instructor?.name ?? "—"} · ${c.registrations_count} inscrit(s)</small>
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
    const isStudent = currentUser?.roles?.some((r) => r.name === "student");
    const isOwner = currentUser && c.instructor_id === currentUser.id;

    document.getElementById("course-detail").innerHTML = `
        <h2>${c.title}</h2>
        <p><small>Par ${c.instructor?.name ?? "—"} · ${c.registrations_count} inscrit(s)</small></p>
        <p>${c.description ?? "Pas de description."}</p>
        ${
            isStudent
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
            <strong>${c.title}</strong>
            <small> — ${c.registrations_count} inscrit(s)</small>
            <p>${c.description ?? ""}</p>
            <div class="card-actions">
                <button class="secondary" onclick="loadCourseInscriptions(${c.id}, '${c.title.replace(/'/g, "\\'")}')">Voir les inscrits</button>
                <button class="secondary" onclick="openEditCourse(${c.id}, '${c.title.replace(/'/g, "\\'")}', '${(c.description ?? "").replace(/'/g, "\\'")}')">Modifier</button>
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
function openEditCourse(id, title, description) {
    const form = document.getElementById("edit-course-form");
    form.id.value = id;
    form.title.value = title;
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
                    title: fd.get("title"),
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
async function loadCourseInscriptions(courseId, title) {
    document.getElementById("course-inscriptions-title").textContent =
        `Inscrits — ${title}`;
    const registrations = await apiCall(`/courses/${courseId}/registrations`);
    const list = document.getElementById("course-inscriptions-list");
    list.innerHTML =
        registrations
            .map(
                (i) => `
        <div class="card">
            <strong>${i.user.name}</strong> — ${i.user.phone}
            <span class="badge ${i.status}">${i.status === "completed" ? "Terminée" : "En cours"}</span>
            ${i.certificate ? `<p><small>Certificat : ${i.certificate.uuid}</small></p>` : ""}
            <div class="card-actions">
                ${
                    i.status === "in_progress"
                        ? `<button onclick="updateRegistrationStatus(${i.id}, 'completed', ${courseId}, '${title.replace(/'/g, "\\'")}')">Marquer terminée (génère le certificat)</button>`
                        : `<button class="secondary" onclick="updateRegistrationStatus(${i.id}, 'in_progress', ${courseId}, '${title.replace(/'/g, "\\'")}')">Repasser en cours</button>`
                }
            </div>
        </div>
    `,
            )
            .join("") || "<p>Aucun inscrit pour cette formation.</p>";
    showSection("course-inscriptions-section");
}

async function updateRegistrationStatus(registrationId, status, courseId, title) {
    try {
        await apiCall(`/registrations/${registrationId}/status`, {
            method: "PUT",
            body: JSON.stringify({ status }),
        });
        showMessage(
            status === "completed"
                ? "Formation marquée terminée, certificat généré"
                : "Statut mis à jour",
        );
        loadCourseInscriptions(courseId, title);
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- MON COMPTE (authentifié) ---
async function loadProfile() {
    try {
        const user = await apiCall("/user");
        let pointsHtml = "";
        try {
            const points = await apiCall("/user/points");
            pointsHtml = `
                <p><small>Code de parrainage : <strong>${points.referral_code}</strong></small></p>
                <p><small>Points de fidélité cumulés : <strong>${points.loyalty_points}</strong></small></p>
            `;
        } catch (e) {}

        document.getElementById("profile-info").innerHTML = `
            <div class="card">
                <strong>${user.name}</strong><br>
                <small>Téléphone : ${user.phone ?? "—"}</small><br>
                <small>Rôles : ${user.roles?.map((r) => r.name).join(", ") ?? "—"}</small>
                ${pointsHtml}
            </div>
        `;
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- MES INSCRIPTIONS (apprenant) ---
async function loadMyInscriptions() {
    const courses = await apiCall("/my-enrolled-courses");
    const list = document.getElementById("my-inscriptions-list");
    list.innerHTML =
        courses
            .map(
                (c) => `
        <div class="card">
            <strong>${c.title}</strong><br>
            <small>Par ${c.instructor?.name ?? "—"}</small>
            <span class="badge ${c.pivot?.status === "completed" ? "terminee" : "en_cours"}">
                ${c.pivot?.status === "completed" ? "Terminée" : "En cours"}
            </span>
            ${c.pivot?.registered_at ? `<p><small>Inscrit le ${new Date(c.pivot.registered_at.replace(" ", "T")).toLocaleDateString()}</small></p>` : ""}
        </div>
    `,
            )
            .join("") || "<p>Vous n'êtes inscrit à aucune formation.</p>";
}

// --- MES CERTIFICATS (apprenant) ---
async function loadMyCertificates() {
    const certificates = await apiCall("/my-certificates");
    const list = document.getElementById("my-certificates-list");
    list.innerHTML =
        certificates
            .map(
                (c) => `
        <div class="card">
            <strong>${c.registration.course.title}</strong><br>
            <small>Délivré le ${new Date(c.issued_at * 1000).toLocaleDateString()}</small><br>
            <small>UUID : ${c.uuid}</small>
            <div class="card-actions">
                <button class="secondary" onclick="verifyCertificate('${c.uuid}')">Vérifier ce certificat</button>
            </div>
        </div>
    `,
            )
            .join("") || "<p>Vous n'avez pas encore de certificat.</p>";
}

// --- SYSTEME DE PARRAINAGE (authentifié) ---
async function loadReferrals() {
    try {
        const points = await apiCall("/user/points");
        const referrals = await apiCall("/my-referrals");

        const rewardedCount = referrals.filter((r) => r.reward_triggered_at).length;
        const pendingCount = referrals.length - rewardedCount;

        const infoEl = document.getElementById("referral-info");
        infoEl.innerHTML = `
            <div class="card">
                <strong>Points cumulés (parrainage)</strong>
                <h3>${points.loyalty_points} points</h3>
                <p><small>Partagez votre code : <strong>${points.referral_code}</strong></small></p>
                <p><small>Total filleuls : ${points.referrals_count} · Récompensés : ${rewardedCount} · En attente : ${pendingCount}</small></p>
            </div>
        `;

        const listEl = document.getElementById("referral-list");
        listEl.innerHTML =
            referrals
                .map((r) => {
                    const isActive = r.reward_triggered_at !== null;
                    return `
            <div class="card">
                <strong>${r.referred.name}</strong>
                <span class="badge ${isActive ? "active" : "inactive"}">
                    ${isActive ? "Actif — récompense déclenchée" : "Inactif — en attente"}
                </span>
                <p><small>Parrainé le ${new Date(r.created_at.replace(" ", "T")).toLocaleDateString()}</small></p>
                ${r.reward_triggered_at ? `<p><small>reward_triggered_at : ${new Date(r.reward_triggered_at).toLocaleString()}</small></p>` : "<p><small>reward_triggered_at : null</small></p>"}
            </div>
        `;
                })
                .join("") || "<p>Vous n'avez pas encore de filleuls.</p>";
    } catch (err) {
        showMessage(err.message, "error");
    }
}

// --- VERIFICATION CERTIFICAT (public) ---
async function verifyCertificate(uuid) {
    showSection("verify-section");
    document.querySelector("#verify-form input[name='uuid']").value = uuid;
    const resultEl = document.getElementById("verify-result");
    try {
        const data = await apiCall(`/verify/${uuid}`);
        resultEl.innerHTML = `
            <div class="card">
                ✅ Certificat valide<br>
                <strong>${data.certificate.student}</strong> — ${data.certificate.course}<br>
                <small>Délivré le ${new Date(data.certificate.issued_at * 1000).toLocaleDateString()}</small>
            </div>`;
    } catch (err) {
        resultEl.innerHTML = `<div class="card">❌ ${err.message}</div>`;
    }
}

document.getElementById("verify-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const uuid = new FormData(e.target).get("uuid");
    const resultEl = document.getElementById("verify-result");
    try {
        const data = await apiCall(`/verify/${uuid}`);
        console.log(data);
        resultEl.innerHTML = `
            <div class="card">
                ✅ Certificat valide<br>
                <strong>${data.certificate.student}</strong> — ${data.certificate.course}<br>
                <small>Délivré le ${new Date(data.certificate.issued_at * 1000).toLocaleDateString()}</small>
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
