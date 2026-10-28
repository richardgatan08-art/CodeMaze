// ── toggle sign-in / sign-up panels ──────────────────────────────────
const container = document.getElementById('container');
const registerBtn = document.getElementById('registerBtn');
const loginBtn2 = document.getElementById('loginBtn2');

if (registerBtn) registerBtn.addEventListener('click', () => container.classList.add('register-mode'));
if (loginBtn2) loginBtn2.addEventListener('click', () => container.classList.remove('register-mode'));

// ── toast helper ─────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    toast.className = 'toast ' + type + ' show';
    icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 3500);
}

// ── generic POST helper ───────────────────────────────────────────────
async function postForm(url, formData, btn) {
    btn.classList.add('loading');
    btn.textContent = 'Please wait…';
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        const data = await res.json();
        return data;
    } catch (e) {
        return { success: false, error: 'Network error. Is XAMPP running?' };
    } finally {
        btn.classList.remove('loading');
    }
}

// ── LOGIN ─────────────────────────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('email', document.getElementById('loginEmail').value.trim());
    fd.append('password', document.getElementById('loginPassword').value);

    const data = await postForm('../php/auth.php', fd, btn);
    if (data.success) {
        showToast('Login successful! Redirecting…');
        setTimeout(() => window.location.href = data.redirect || 'admin.php', 800);
    } else {
        btn.textContent = 'LOGIN';
        showToast(data.error || 'Login failed.', 'error');
    }
});

// ── REGISTER ──────────────────────────────────────────────────────────
document.getElementById('signupForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('signupBtn');
    const fd = new FormData();
    fd.append('action', 'register');
    fd.append('email', document.getElementById('signupEmail').value.trim());
    fd.append('password', document.getElementById('signupPassword').value);

    const data = await postForm('../php/auth.php', fd, btn);
    btn.textContent = 'Register';
    if (data.success) {
        showToast('Account created! You can now log in.');
        document.getElementById('signupForm').reset();
        // Switch to login panel
        setTimeout(() => container.classList.remove('register-mode'), 1200);
    } else {
        showToast(data.error || 'Registration failed.', 'error');
    }
});