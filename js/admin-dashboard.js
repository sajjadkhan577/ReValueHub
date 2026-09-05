let currentEditingItemId = null;



document.addEventListener('DOMContentLoaded', async () => {
    console.log('Admin Dashboard Initializing...');

    // Check admin access first
    const token = localStorage.getItem('adminToken');
    if (!token) {
        window.location.href = 'admin-login.html';
        return;
    }

    try {
        const res = await fetch('api/auth/me.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
            const data = await res.json();
            if (!data.user || data.user.role !== 'admin') {
                // Not an admin — redirect to admin login (don't keep the non-admin token)
                localStorage.removeItem('adminToken');
                window.location.href = 'admin-login.html';
                return;
            }
            window.adminUser = data.user;
            // Populate header avatar immediately on page load so it persists after refresh
            populateHeaderAvatar(data.user);
        } else {
            window.location.href = 'admin-login.html';
            return;
        }
    } catch (err) {
        console.error('Auth error:', err);
        window.location.href = 'admin-login.html';
        return;
    }

    applyTheme();
    initAdminUI();
    fetchStats();
    fetchPendingItems();
    fetchAllUsers();
});

/**
 * Populates the header avatar/icon based on the given user object.
 * Called on page load and after profile save so the avatar persists across refreshes.
 */
function populateHeaderAvatar(user) {
    if (!user) return;
    const headerAvatar = document.getElementById('header-admin-avatar');
    const headerIcon = document.getElementById('header-admin-icon');
    if (!headerAvatar || !headerIcon) return;

    if (user.avatar) {
        headerAvatar.src = user.avatar;
        headerAvatar.classList.remove('hidden');
        headerIcon.classList.add('hidden');
    } else {
        headerAvatar.classList.add('hidden');
        headerIcon.classList.remove('hidden');
    }
}

function initAdminUI() {
    const activeLink = document.querySelector('.nav-link[onclick*="dashboard"]');
    if (activeLink) {
        activeLink.classList.add('text-blue-600', 'bg-blue-50', 'font-semibold');
        activeLink.classList.remove('text-slate-600');
    }
}

function showAdminSection(sectionId, element) {
    document.querySelectorAll('.admin-view').forEach(view => view.classList.add('hidden'));
    const target = document.getElementById(sectionId + '-view');
    if (target) {
        target.classList.remove('hidden');
        document.getElementById('header-title').textContent = sectionId.charAt(0).toUpperCase() + sectionId.slice(1) + ' Control';
    }
    
    if (element && element.classList.contains('nav-link')) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('text-blue-600', 'bg-blue-50', 'font-semibold');
            link.classList.add('text-slate-600');
        });
        element.classList.add('text-blue-600', 'bg-blue-50', 'font-semibold');
        element.classList.remove('text-slate-600');
    }

    if (sectionId === 'items') fetchAdminInventory();
    if (sectionId === 'requests') fetchAdminRequests();
    if (sectionId === 'users') fetchAllUsers();
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
    updateThemeIcon(isDark);
}

function applyTheme() {
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    const isDark = savedTheme === 'dark';
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    updateThemeIcon(isDark);
}

function updateThemeIcon(isDark) {
    const icon = document.getElementById('theme-icon');
    if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
}

function toggleNotifications() {
    alert('Notifications: \n1. New user registration\n2. 3 items pending approval\n3. System update completed');
    document.getElementById('notif-badge')?.classList.add('hidden');
}

async function fetchStats() {
    try {
        const response = await fetch(`api/stats.php?t=${Date.now()}`);
        const data = await response.json();
        
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setVal('admin-total-users', data.users || '0');
        setVal('admin-pending-items', data.items_pending || '0');
        setVal('admin-total-requests', data.requests || '0');
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
}

async function fetchPendingItems() {
    try {
        const response = await fetch(`api/pending_items.php?t=${Date.now()}`);
        let dbItems = [];
        try { dbItems = await response.json(); } catch(e) {}
        
        const tbody = document.getElementById('pending-items-tbody');
        if (!tbody) return;

        const allItems = dbItems;

        if (allItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-slate-500">No pending items.</td></tr>';
            return;
        }

        tbody.innerHTML = allItems.map(item => `
            <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="openItemModal('${item.id}')">
                <td class="py-4">
                    <div class="flex items-center gap-3">
                        <img class="w-10 h-10 rounded-lg object-cover" src="${item.image_url || 'assets/placeholder.png'}">
                        <span class="font-bold text-sm">${item.title}</span>
                    </div>
                </td>
                <td class="py-4 text-sm">${item.donor_name}</td>
                <td class="py-4"><span class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-bold">${item.category}</span></td>
                <td class="py-4">
                    <div class="flex gap-2">
                        <button onclick="event.stopPropagation(); updateItemStatus('${item.id}', 'approved')" class="text-secondary hover:scale-110 transition-transform"><span class="material-symbols-outlined">check_circle</span></button>
                        <button onclick="event.stopPropagation(); updateItemStatus('${item.id}', 'rejected')" class="text-error hover:scale-110 transition-transform"><span class="material-symbols-outlined">cancel</span></button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error fetching pending items:', error);
    }
}

async function fetchAllUsers() {
    try {
        const response = await fetch(`api/admin/users.php?t=${Date.now()}`);
        const users = await response.json();
        const tbody = document.getElementById('admin-users-tbody');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td class="px-6 py-4 font-bold text-sm">${user.name}</td>
                <td class="px-6 py-4 text-sm">${user.email}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 ${user.role === 'admin' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-600'} rounded text-xs font-bold capitalize">${user.role}</span></td>
                <td class="px-6 py-4"><span class="px-2 py-1 bg-green-50 text-green-600 rounded text-xs font-bold">${user.status}</span></td>
                <td class="px-6 py-4 text-sm text-slate-500">${new Date(user.created_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error fetching users:', error);
    }
}

async function updateItemStatus(targetId, status) {
    alert(`Attempting to ${status} item ${targetId}`);
    console.log(`Updating item ${targetId} to status ${status}`);
    try {
        const response = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: targetId, status: status })
        });
        const result = await response.json();
        if (response.ok) {
            alert(result.message || `Item ${status}!`);
            closeItemModal();
            fetchPendingItems();
            fetchAdminInventory();
            fetchStats();
        } else {
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Network error while updating status.');
    }
}

async function fetchAdminInventory() {
    const grid = document.getElementById('admin-items-grid');
    if (!grid) return;

    try {
        const res = await fetch(`api/items.php?status=all&t=${Date.now()}`);
        let dbItems = [];
        try { dbItems = await res.json(); } catch(e) {}
        
        const allItems = dbItems;

        grid.innerHTML = allItems.map(item => `
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm cursor-pointer hover:shadow-md transition-all" onclick="openItemModal('${item.id}')">
                <div class="relative h-32">
                    <img class="w-full h-full object-cover" src="${item.image_url || 'assets/placeholder.png'}">
                    <div class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase ${item.status === 'approved' ? 'bg-green-500 text-white' : 'bg-blue-500 text-white'}">
                        ${item.status}
                    </div>
                </div>
                <div class="p-3">
                    <h5 class="font-bold text-sm truncate">${item.title}</h5>
                    <p class="text-xs text-slate-500">${item.category}</p>
                </div>
            </div>
        `).join('');
    } catch (err) {
        grid.innerHTML = '<p class="col-span-full text-center text-error">Failed to load items.</p>';
    }
}

async function openItemModal(itemId) {
    currentEditingItemId = itemId;
    let item = null;

    try {
        const res = await fetch(`api/items.php?id=${itemId}&t=${Date.now()}`);
        if (res.ok) {
            item = await res.json();
        } else {
            alert('Item not found or already deleted.');
            fetchAdminInventory();
            return;
        }
    } catch (err) {
        console.error('Error fetching item details:', err);
        alert('Error loading item details.');
        return;
    }

    try {
        if (item) {
            document.getElementById('modal-item-image').src = item.image_url || 'assets/placeholder.png';
            document.getElementById('modal-item-title').textContent = item.title;
            document.getElementById('modal-item-category').textContent = item.category;
            document.getElementById('modal-item-desc').value = item.description || '';
            document.getElementById('modal-item-location').value = item.location || '';
            document.getElementById('modal-item-donor').textContent = item.donor_name || 'System User';
            
            const statusBadge = document.getElementById('modal-item-status-badge');
            if (statusBadge) {
                statusBadge.textContent = item.status;
                statusBadge.className = `px-3 py-1 rounded-full text-xs font-bold uppercase ${item.status === 'approved' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'}`;
            }
            
            const approveBtn = document.getElementById('modal-approve-btn');
            if (approveBtn) {
                approveBtn.className = item.status === 'approved' ? 'hidden' : 'flex-1 bg-secondary text-white py-3 rounded-xl font-bold hover:opacity-90 transition-all shadow-md shadow-secondary/20';
                approveBtn.onclick = () => {
                    console.log('Approve button clicked');
                    updateItemStatus(itemId, 'approved');
                };
            }

            const deleteBtn = document.getElementById('modal-delete-btn');
            if (deleteBtn) {
                deleteBtn.onclick = () => deleteAdminItem(itemId);
            }

            const saveBtn = document.getElementById('modal-save-btn');
            if (saveBtn) {
                saveBtn.onclick = () => saveAdminItemEdit(itemId);
            }

            document.getElementById('item-modal').classList.remove('hidden');
        }
    } catch (err) {
        console.error('Error rendering modal:', err);
        alert('Internal UI Error: ' + err.message);
    }
}

function closeItemModal() {
    document.getElementById('item-modal').classList.add('hidden');
    currentEditingItemId = null;
}

async function saveAdminItemEdit(itemId) {
    alert('Saving changes for item ' + itemId);
    const desc = document.getElementById('modal-item-desc').value;
    const location = document.getElementById('modal-item-location').value;



    try {
        const res = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: itemId, description: desc, location: location, action: 'edit' })
        });
        const result = await res.json();
        if (res.ok) {
            alert(result.message || 'Changes saved!');
            fetchAdminInventory();
            fetchPendingItems();
            closeItemModal();
        } else {
            alert('Error: ' + (result.message || 'Failed to save changes'));
        }
    } catch (err) {
        console.error('Error saving edit:', err);
        alert('Network error while saving changes.');
    }
}

async function deleteAdminItem(itemId) {
    alert('Attempting to delete item ' + itemId);
    try {
        const res = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: itemId, action: 'delete' })
        });
        const result = await res.json();
        if (res.ok) {
            alert(result.message || 'Item deleted!');
            fetchAdminInventory();
            fetchPendingItems();
            fetchStats();
            closeItemModal();
        } else {
            alert('Error: ' + (result.message || 'Failed to delete item'));
        }
    } catch (err) {
        console.error('Error deleting item:', err);
        alert('Network error while deleting item.');
    }
}

async function fetchAdminRequests() {
    const tbody = document.getElementById('user-requests-tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center">Loading requests...</td></tr>';
    try {
        const response = await fetch(`api/requests.php?t=${Date.now()}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}` }
        });
        const requests = await response.json();
        
        if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-slate-500">No requests found.</td></tr>';
            return;
        }

        window.adminRequestsById = Object.fromEntries(requests.map(req => [String(req.id), req]));
        tbody.innerHTML = requests.map(req => `
            <tr>
                <td class="px-6 py-4">
                    <p class="text-sm font-bold">${req.requester_name || 'User #' + req.requester_id}</p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm">${req.item_title}</p>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-bold uppercase">${req.status}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button type="button" data-request-id="${req.id}" class="manage-request-btn text-primary hover:underline text-xs font-bold">Manage</button>
                </td>
            </tr>
        `).join('');
        tbody.querySelectorAll('.manage-request-btn').forEach(button => {
            button.addEventListener('click', () => {
                const request = window.adminRequestsById?.[String(button.dataset.requestId)];
                if (request) openRequestModal(request);
            });
        });
    } catch (error) {
        console.error('Error fetching requests:', error);
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-error">Failed to load requests.</td></tr>';
    }
}

function openRequestModal(request) {
    const modal = document.getElementById('request-modal');
    if (!modal) return;

    modal.dataset.requestId = request.id;
    document.getElementById('request-modal-id').textContent = `Request #${request.id}`;
    document.getElementById('request-modal-requester').textContent = request.requester_name || `User #${request.requester_id}`;
    document.getElementById('request-modal-item').textContent = request.item_title || `Item #${request.item_id}`;
    document.getElementById('request-modal-date').textContent = request.created_at ? new Date(request.created_at).toLocaleString() : 'Unknown';
    document.getElementById('request-modal-status').value = request.status || 'open';
    modal.classList.remove('hidden');
}

function closeRequestModal() {
    document.getElementById('request-modal')?.classList.add('hidden');
}

async function saveRequestStatus() {
    const modal = document.getElementById('request-modal');
    const requestId = modal?.dataset.requestId;
    const status = document.getElementById('request-modal-status')?.value;
    if (!requestId || !status) return;

    try {
        const response = await fetch('api/requests.php', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('adminToken')}`
            },
            body: JSON.stringify({ id: requestId, status })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Unable to update request');
        closeRequestModal();
        fetchAdminRequests();
        fetchStats();
    } catch (error) {
        alert(error.message || 'Unable to update request.');
    }
}

function logout() {
    localStorage.removeItem('userToken');
    localStorage.removeItem('profileAvatar');
    localStorage.removeItem('adminProfileAvatar');
    window.location.href = 'landing.html';
}

function adminLogout() {
    localStorage.removeItem('adminToken');
    localStorage.removeItem('userToken');
    localStorage.removeItem('adminProfileAvatar');
    localStorage.removeItem('admin-theme');
    window.location.href = 'admin-login.html';
}

// ============================================================
// Volunteer Application Management
// ============================================================

async function fetchVolunteerApplications() {
    const tbody = document.getElementById('volunteer-apps-tbody');
    const countEl = document.getElementById('volunteer-count');
    if (!tbody) return;

    try {
        const response = await fetch('api/admin/volunteer-applications.php', {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}` }
        });
        
        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-error italic">${errData.message || 'Failed to load applications'}</td></tr>`;
            return;
        }

        const applications = await response.json();
        
        if (countEl) {
            const pending = applications.filter(a => a.status === 'pending').length;
            countEl.textContent = `${pending} pending`;
            countEl.className = pending > 0 
                ? 'text-sm font-bold text-amber-600 bg-amber-50 px-3 py-1 rounded-full' 
                : 'text-sm font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full';
        }

        if (!applications.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">No volunteer applications yet.</td></tr>';
            return;
        }

        tbody.innerHTML = applications.map(app => {
            const isPending = app.status === 'pending';
            const isApproved = app.status === 'approved';
            const statusBadge = isPending ? 'bg-amber-50 text-amber-700' : isApproved ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700';
            
            return `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                                ${escapeHtml(app.applicant_name || app.full_name || '?').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">${escapeHtml(app.applicant_name || app.full_name || 'Unknown')}</p>
                                <p class="text-xs text-slate-500">${escapeHtml(app.applicant_email || app.email || '')}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1 max-w-[200px]">
                            ${(app.skills || '').split(',').filter(s => s.trim()).map(skill => 
                                `<span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">${escapeHtml(skill.trim())}</span>`
                            ).join('') || '<span class="text-xs text-slate-400">—</span>'}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${escapeHtml(app.availability || 'Flexible')}</td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase ${statusBadge}">${escapeHtml(app.status)}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">${app.created_at ? new Date(app.created_at).toLocaleDateString() : '—'}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            ${isPending ? `
                                <button onclick="updateVolunteerStatus(${app.id}, 'approved')" class="flex items-center gap-1 px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-100 rounded-lg text-xs font-bold transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Approve
                                </button>
                                <button onclick="updateVolunteerStatus(${app.id}, 'rejected')" class="flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-xs font-bold transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">cancel</span> Reject
                                </button>
                            ` : isApproved ? `
                                <span class="text-xs font-bold text-green-600 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check</span> Approved
                                </span>
                            ` : `
                                <span class="text-xs font-bold text-red-600 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">block</span> Rejected
                                </span>
                            `}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Error fetching volunteer applications:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-error italic">Network error loading applications.</td></tr>';
    }
}

async function updateVolunteerStatus(applicationId, status) {
    if (!confirm(`Are you sure you want to ${status} this application?`)) return;

    try {
        const response = await fetch('api/admin/volunteer-applications.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('adminToken')}`
            },
            body: JSON.stringify({ id: applicationId, status: status })
        });

        const result = await response.json();

        if (response.ok) {
            alert(result.message || `Application ${status}!`);
            fetchVolunteerApplications();
        } else {
            alert('Error: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error updating volunteer status:', error);
        alert('Network error while updating status.');
    }
}

// Helper: escapeHtml for admin dashboard (local copy)
function escapeHtml(value = '') {
    return String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '<',
        '>': '>',
        '"': '"',
        "'": '&#039;'
    }[char]));
}

// Initialize admin settings form with current user data and avatar upload
function initAdminSettings() {
    const nameInput = document.getElementById('admin-settings-name');
    const emailInput = document.getElementById('admin-settings-email');
    const nameDisplay = document.getElementById('admin-settings-name-display');
    const avatarPreview = document.getElementById('admin-settings-avatar-preview');
    const avatarFallback = document.getElementById('admin-settings-avatar-fallback');
    const avatarInput = document.getElementById('admin-settings-avatar-input');
    const form = document.getElementById('admin-settings-form');
    const statusEl = document.getElementById('admin-settings-status');

    // Get current admin user data (fall back to window.currentUser from app.js or localStorage)
    const token = localStorage.getItem('adminToken');
    let adminUser = window.currentUser || null;

    // Fetch the latest user data from the API
    async function loadAdminProfile() {
        if (!token) return;
        try {
            const res = await fetch('api/auth/me.php', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            if (data.user) {
                adminUser = data.user;
                window.currentUser = data.user;
                populateSettings(data.user);
            }
        } catch (err) {
            console.warn('Could not fetch admin profile, using cached data:', err);
            if (adminUser) populateSettings(adminUser);
        }
    }

    function populateSettings(user) {
        if (nameInput) nameInput.value = user.name || '';
        if (emailInput) emailInput.value = user.email || '';
        if (nameDisplay) nameDisplay.textContent = user.name || 'Admin';

        // Show avatar if available, otherwise show icon fallback
        const avatarUrl = user.avatar;

        if (avatarUrl && avatarPreview) {
            avatarPreview.src = avatarUrl;
            avatarPreview.classList.remove('hidden');
            if (avatarFallback) avatarFallback.classList.add('hidden');
        } else {
            if (avatarPreview) avatarPreview.classList.add('hidden');
            if (avatarFallback) avatarFallback.classList.remove('hidden');
        }

        // Always sync header avatar via shared helper
        populateHeaderAvatar(user);
    }

    // Handle avatar file selection
    let selectedAvatarFile = null;
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            const headerAvatar = document.getElementById('header-admin-avatar');
            const headerIcon = document.getElementById('header-admin-icon');

            if (!file) {
                // If user cancels and no avatar was set before, we revert to fallback
                const existingAvatar = adminUser && adminUser.avatar ? adminUser.avatar : null;
                if (!existingAvatar) {
                    if (avatarPreview) avatarPreview.classList.add('hidden');
                    if (avatarFallback) avatarFallback.classList.remove('hidden');
                    if (headerAvatar) headerAvatar.classList.add('hidden');
                    if (headerIcon) headerIcon.classList.remove('hidden');
                }
                selectedAvatarFile = null;
                return;
            }
            selectedAvatarFile = file;

            // Show preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                if (avatarPreview) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('hidden');
                    if (avatarFallback) avatarFallback.classList.add('hidden');
                }
                if (headerAvatar) {
                    headerAvatar.src = e.target.result;
                    headerAvatar.classList.remove('hidden');
                    if (headerIcon) headerIcon.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // Handle form submission
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const payload = new FormData();
            payload.append('name', nameInput ? nameInput.value : '');
            payload.append('email', emailInput ? emailInput.value : '');
            payload.append('bio', '');
            if (selectedAvatarFile) payload.append('avatar', selectedAvatarFile);

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
            }

            try {
                const res = await fetch('api/auth/profile.php', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` },
                    body: payload
                });
                const data = await res.json();

                if (res.ok && data.user) {
                    window.currentUser = data.user;
                    adminUser = data.user;
                    // Save avatar locally too
                    if (data.user.avatar) {
                        localStorage.setItem('adminProfileAvatar', data.user.avatar);
                    } else if (selectedAvatarFile && avatarPreview) {
                        localStorage.setItem('adminProfileAvatar', avatarPreview.src);
                    }
                    populateSettings(data.user);
                    if (statusEl) {
                        statusEl.textContent = 'Profile updated successfully!';
                        statusEl.classList.remove('hidden');
                        setTimeout(() => statusEl.classList.add('hidden'), 3000);
                    }
                } else {
                    // Even if API fails, save local preview
                    if (selectedAvatarFile && avatarPreview) {
                        localStorage.setItem('adminProfileAvatar', avatarPreview.src);
                    }
                    if (statusEl) {
                        statusEl.textContent = data.message || 'Profile saved locally (preview).';
                        statusEl.className = 'text-sm font-semibold text-amber-600';
                        statusEl.classList.remove('hidden');
                        setTimeout(() => statusEl.classList.add('hidden'), 3000);
                    }
                }
            } catch (err) {
                console.error('Error updating admin profile:', err);
                if (statusEl) {
                    statusEl.textContent = 'Network error. Changes saved as preview.';
                    statusEl.className = 'text-sm font-semibold text-amber-600';
                    statusEl.classList.remove('hidden');
                    setTimeout(() => statusEl.classList.add('hidden'), 3000);
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Admin Profile';
                }
                selectedAvatarFile = null;
            }
        });
    }

    loadAdminProfile();
}

// Extend showAdminSection to also fetch volunteer data when that section is clicked
const origShowAdminSection = window.showAdminSection || showAdminSection;
window.showAdminSection = function(sectionId, element) {
    if (typeof origShowAdminSection === 'function') {
        origShowAdminSection(sectionId, element);
    } else {
        // Fallback to original logic
        document.querySelectorAll('.admin-view').forEach(view => view.classList.add('hidden'));
        const target = document.getElementById(sectionId + '-view');
        if (target) {
            target.classList.remove('hidden');
            document.getElementById('header-title').textContent = sectionId.charAt(0).toUpperCase() + sectionId.slice(1) + ' Control';
        }
        
        if (element && element.classList.contains('nav-link')) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('text-blue-600', 'bg-blue-50', 'font-semibold');
                link.classList.add('text-slate-600');
            });
            element.classList.add('text-blue-600', 'bg-blue-50', 'font-semibold');
            element.classList.remove('text-slate-600');
        }
    }
    
    if (sectionId === 'volunteers') {
        fetchVolunteerApplications();
    }
    if (sectionId === 'settings') {
        initAdminSettings();
        initInviteAdmin();
    }
};

window.fetchVolunteerApplications = fetchVolunteerApplications;
window.updateVolunteerStatus = updateVolunteerStatus;
window.closeInviteModal = closeInviteModal;

window.toggleDarkMode = toggleDarkMode;
window.toggleNotifications = toggleNotifications;
window.logout = logout;
window.adminLogout = adminLogout;
window.updateItemStatus = updateItemStatus;
window.openItemModal = openItemModal;
window.closeItemModal = closeItemModal;

// --- Invite Admin Logic ---
function initInviteAdmin() {
    const inviteBtn = document.getElementById('invite-admin-btn');
    const inviteModal = document.getElementById('invite-admin-modal');
    const inviteForm = document.getElementById('invite-admin-form');
    const statusDiv = document.getElementById('invite-admin-status');

    // Only add listener if we haven't already
    if (inviteBtn && !inviteBtn.hasAttribute('data-init')) {
        inviteBtn.setAttribute('data-init', 'true');
        inviteBtn.addEventListener('click', () => {
            if (inviteModal) inviteModal.classList.remove('hidden');
            if (statusDiv) statusDiv.classList.add('hidden');
        });
    }

    if (inviteForm && !inviteForm.hasAttribute('data-init')) {
        inviteForm.setAttribute('data-init', 'true');
        inviteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('invite-admin-email').value;
            const submitBtn = inviteForm.querySelector('button[type="submit"]');
            
            if (statusDiv) {
                statusDiv.classList.add('hidden');
                statusDiv.classList.remove('bg-error/10', 'text-error', 'bg-green-100', 'text-green-800');
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">sync</span> Sending...';

            try {
                const token = localStorage.getItem('adminToken');
                const res = await fetch('api/auth/invite_admin.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ email })
                });

                const data = await res.json();
                
                if (statusDiv) {
                    statusDiv.textContent = data.message;
                    statusDiv.classList.remove('hidden');
                    
                    if (res.ok) {
                        statusDiv.classList.add('bg-green-100', 'text-green-800');
                        inviteForm.reset();
                        // Close modal after success
                        setTimeout(closeInviteModal, 3000);
                    } else {
                        statusDiv.classList.add('bg-error/10', 'text-error');
                    }
                }
            } catch (err) {
                console.error('Error inviting admin:', err);
                if (statusDiv) {
                    statusDiv.textContent = 'Network error. Please try again.';
                    statusDiv.classList.remove('hidden');
                    statusDiv.classList.add('bg-error/10', 'text-error');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">send</span> Send Invite';
            }
        });
    }
}

function closeInviteModal() {
    const inviteModal = document.getElementById('invite-admin-modal');
    if (inviteModal) inviteModal.classList.add('hidden');
}
