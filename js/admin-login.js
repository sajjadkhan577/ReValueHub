document.addEventListener('DOMContentLoaded', () => {
    // If already logged in as admin, redirect to admin dashboard
    const token = localStorage.getItem('adminToken');
    if (token) {
        // Quickly check role
        fetch('api/auth/me.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(data => {
            if (data.user && data.user.role === 'admin') {
                window.location.href = 'admin-dashboard.html';
            }
        })
        .catch(err => console.error('Auth check error:', err));
    }

    // Handle password visibility toggle
    const passwordInput = document.getElementById('password');
    const toggleBtn = passwordInput.nextElementSibling;
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleBtn.querySelector('span').textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    }

    const form = document.getElementById('admin-login-form');
    const errorDiv = document.getElementById('admin-login-error');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorDiv.classList.add('hidden');

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const submitBtn = form.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Authenticating...';

        try {
            const res = await fetch('api/auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await res.json();

            if (res.ok) {
                // Verify admin role
                if (data.user && data.user.role === 'admin') {
                    localStorage.setItem('adminToken', data.token);
                    // Also store under 'userToken' so the shared header/nav (app.js)
                    // recognizes the logged-in admin instead of showing a guest/user view.
                    localStorage.setItem('userToken', data.token);
                    window.location.href = 'admin-dashboard.html';
                } else {
                    // Valid user, but not admin
                    errorDiv.textContent = 'Access Denied: This login is for administrators only.';
                    errorDiv.classList.remove('hidden');
                }
            } else {
                errorDiv.textContent = data.message || 'Invalid credentials';
                errorDiv.classList.remove('hidden');
            }
        } catch (err) {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In to Console';
        }
    });
});
