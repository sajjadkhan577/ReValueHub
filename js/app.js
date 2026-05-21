// ReValue Hub application glue. It attaches behavior to the existing HTML only.
const API_BASE = 'api';
const PLACEHOLDER_IMAGE = 'https://lh3.googleusercontent.com/aida-public/AB6AXuD4f83yGKrv7c7-0bfgp2ycKE2srMd0FytN5dTaA3XqR804zib_M9ZtNuYzfA3V4LuYthdImJII5_jXet_280-PzL-HkbcxzlwiwEXRG4iVuPaskmlExwya1v2EhIe4Yy8xyMt7R4C3E3ZC8d4rUmP26qZ2JSA73Q-fzRs_5UjxqmtMyeXdLPsJHuGI0ukjNtQZMKC1QnjdeAxSFvbEJo2ZYFUrGUxfE8yePRA6Z78ohXVupVg2YuZWnV_J1TydDhQ317LEL7Hwej0';
const DEFAULT_AVATAR = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCFK3hyHBvRE5TAliCTcBV-PMBGOtI_LZmJSQprUaY5ycVMpBQnGkhrB22qNmSgDAtkOiV8vroMNGzPYsDLqj1DncZb80kFQLhqQLFth79NwNaEeUWOq2XczMKwknemb3PlrR4G4E5CDTfpefienp7M1Bzp5WXA91GxutTH21i7zCCfLwxNNQJsRy0MZHdMt9paOUy9ELtdE4s5Z8dBqF19exVwVS9bqLa0lCkQCd3JfCRC789a5RFYj4TfXOA9UtA6-BpakRIMZNQ';


let currentUser = null;
let selectedImageFiles = [];
let token = localStorage.getItem('token');
let profileAvatarFile = null;

document.addEventListener('DOMContentLoaded', initApp);

async function initApp() {
  await checkAuth();
  setupNav();
  setupHeaderActions();
  setupForms();
  setupSearchAndFilters();
  setupTypingHeadlines();

  const path = decodeURIComponent(window.location.pathname);
  if (path.includes('browse.html') || path.includes('discovery.html')) {
    loadItems('items-grid');
  } else if (path.includes('item-detail.html')) {
    loadItemDetail();
  } else if (path.includes('dashboard.html')) {
    if (!currentUser) return redirect('register.html');
    loadUserDashboard();
  } else if (path.includes('admin-dashboard.html')) {
    if (!currentUser || currentUser.role !== 'admin') return redirect('register.html');
    loadAdminDashboard();
  } else if (path.includes('list-item.html')) {
    if (!currentUser) return redirect('register.html');
    setupImagePicker();
    setupLocationPicker();
  } else if (path.includes('landing.html') || path === '/') {
    loadItems('recent-items-grid', { limit: 4 });
  }

  if (path.includes('login.html') || path.includes('register.html')) {
    if (currentUser) return redirect(currentUser.role === 'admin' ? 'admin-dashboard.html' : 'dashboard.html');
  }
}

function setupTypingHeadlines() {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-typing-phrases]').forEach((headline) => {
    let phrases = [];
    try {
      phrases = JSON.parse(headline.dataset.typingPhrases || '[]');
    } catch (err) {
      phrases = [];
    }

    phrases = phrases.filter(Boolean);
    if (!phrases.length) return;

    if (reduceMotion || phrases.length === 1) {
      headline.textContent = phrases[phrases.length - 1];
      return;
    }

    let phraseIndex = 0;
    let charIndex = 0;
    let deleting = false;
    let resting = false;

    headline.textContent = '';
    headline.classList.add('typing-cursor');

    const typeStep = () => {
      const currentPhrase = phrases[phraseIndex];

      if (resting) {
        resting = false;
        window.setTimeout(typeStep, 1100);
        return;
      }

      if (deleting) {
        charIndex -= 1;
        headline.textContent = currentPhrase.slice(0, charIndex);

        if (charIndex === 0) {
          deleting = false;
          phraseIndex = (phraseIndex + 1) % phrases.length;
          window.setTimeout(typeStep, 250);
          return;
        }
      } else {
        const nextPhrase = phrases[phraseIndex];
        charIndex += 1;
        headline.textContent = nextPhrase.slice(0, charIndex);

        if (charIndex === nextPhrase.length) {
          deleting = true;
          resting = true;
          window.setTimeout(typeStep, 1800);
          return;
        }
      }

      window.setTimeout(typeStep, deleting ? 42 : 62);
    };

    window.setTimeout(typeStep, 1200);
  });
}

function setToken(newToken) {
  token = newToken || null;
  if (token) localStorage.setItem('token', token);
  else localStorage.removeItem('token');
}

function redirect(url) {
  // If url starts with / and we are in a subdirectory, it might fail.
  // We'll strip leading slash to make it relative to the current folder if it's an HTML file.
  if (url.startsWith('/') && url.endsWith('.html')) {
    url = url.substring(1);
  }
  window.location.href = url;
}

function escapeHtml(value = '') {
  return String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[char]));
}

function formatDate(date) {
  return date ? new Date(date).toLocaleDateString() : '';
}

async function apiRequest(endpoint, options = {}) {
  // Defensive check for token string values
  const effectiveToken = (token === 'null' || token === 'undefined') ? null : token;

  const headers = { ...(options.headers || {}) };
  if (!(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
  if (effectiveToken) headers.Authorization = `Bearer ${effectiveToken}`;

  // Strip leading slash from endpoint if present to keep it relative to API_BASE
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;

  // Ensure the endpoint has .php extension for Apache
  const phpEndpoint = cleanEndpoint.includes('?') 
    ? cleanEndpoint.replace('?', '.php?') 
    : `${cleanEndpoint}.php`;

  const url = `${API_BASE}/${phpEndpoint}`;
  console.log(`API Request: ${url}`);

  const res = await fetch(url, { ...options, headers });
  if (res.status === 204) return null;

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw data;
  return data;
}

async function checkAuth() {
  if (!token) return updateUI();
  try {
    const res = await apiRequest('auth/me');
    currentUser = res.user;
  } catch (err) {
    setToken(null);
    currentUser = null;
  }
  updateUI();
}

function updateUI() {
  document.querySelectorAll('[data-logged-in]').forEach((el) => el.style.display = currentUser ? '' : 'none');
  document.querySelectorAll('[data-logged-out]').forEach((el) => el.style.display = currentUser ? 'none' : '');
  document.querySelectorAll('[data-admin-only]').forEach((el) => el.style.display = currentUser?.role === 'admin' ? '' : 'none');
  document.querySelectorAll('[data-user-name]').forEach((el) => el.textContent = currentUser?.name || '');
  document.querySelectorAll('[data-user-email]').forEach((el) => el.textContent = currentUser?.email || '');

  const avatar = getUserAvatar();
  document.querySelectorAll('[data-user-avatar]').forEach((img) => img.src = avatar);
  document.querySelectorAll('img[alt*="User avatar"], img[alt*="User profile"]').forEach((img) => img.src = avatar);

  const nameHeader = document.getElementById('user-name');
  if (nameHeader && currentUser) nameHeader.textContent = `Welcome back, ${currentUser.name}`;

  const dashboardLink = document.getElementById('dashboard-link');
  if (dashboardLink) dashboardLink.href = currentUser?.role === 'admin' ? 'admin-dashboard.html' : 'dashboard.html';
}

function getUserAvatar() {
  const localAvatar = localStorage.getItem('profileAvatar');
  return currentUser?.avatar || localAvatar || DEFAULT_AVATAR;
}

function setupNav() {
  document.querySelectorAll('[data-action="logout"]').forEach((btn) => {
    btn.onclick = (e) => {
      e.preventDefault();
      logout();
    };
  });

  document.querySelectorAll('a[href="#"]').forEach((a) => {
    const text = a.textContent.trim().toLowerCase();
    if (text === 'browse') a.href = 'browse.html';
    if (text === 'lend' || text === 'give item' || text === 'list new item') a.href = 'list-item.html';
    if (text === 'home' || text === 'revalue hub' || text === 'communityshare') a.href = 'landing.html';
    if (text.includes('privacy')) a.href = 'privacy.html';
    if (text.includes('terms')) a.href = 'terms.html';
  });

  document.querySelectorAll('button').forEach((button) => {
    const text = button.textContent.trim().toLowerCase();
    if (['donate', 'create listing', 'give item'].includes(text)) {
      button.addEventListener('click', () => redirect(currentUser ? 'list-item.html' : 'login.html'));
    }
  });
}

function setupHeaderActions() {
  setupNotifications();
  setupProfileMenu();
}

function setupNotifications() {
  document.querySelectorAll('[data-icon="notifications"]').forEach((icon) => {
    const button = icon.closest('button') || icon;
    button.setAttribute('role', 'button');
    button.setAttribute('tabindex', '0');
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleNotificationPopup(button);
    });
    button.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleNotificationPopup(button);
      }
    });
  });

  document.addEventListener('click', () => closeFloatingPopups());
}

function setupProfileMenu() {
  document.querySelectorAll('img[alt*="User avatar"], img[alt*="User profile"], [data-profile-trigger]').forEach((avatar) => {
    const trigger = avatar.closest('button') || avatar.parentElement || avatar;
    trigger.classList.add('cursor-pointer');
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('tabindex', '0');
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      if (document.getElementById('settings-section')) {
        document.getElementById('settings-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('profile-name')?.focus();
      } else {
        toggleProfilePopup(trigger);
      }
    });
  });
}

function closeFloatingPopups() {
  document.querySelectorAll('[data-floating-popup]').forEach((popup) => popup.remove());
}

function placePopup(anchor, popup) {
  document.body.appendChild(popup);
  const rect = anchor.getBoundingClientRect();
  const right = Math.max(16, window.innerWidth - rect.right);
  popup.style.position = 'fixed';
  popup.style.top = `${rect.bottom + 12}px`;
  popup.style.right = `${right}px`;
  popup.style.zIndex = '80';
}

function toggleNotificationPopup(anchor) {
  const existing = document.getElementById('notification-popup');
  closeFloatingPopups();
  if (existing) return;

  const count = document.querySelectorAll('#user-requests-tbody tr').length || 0;
  const popup = document.createElement('div');
  popup.id = 'notification-popup';
  popup.dataset.floatingPopup = 'true';
  popup.className = 'w-80 max-w-[calc(100vw-2rem)] bg-white border border-slate-200 rounded-xl shadow-2xl p-4 text-slate-900';
  popup.innerHTML = `
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-bold text-blue-900">Notifications</h3>
      <span class="text-xs font-bold text-blue-600 bg-blue-50 rounded-full px-2 py-1">${count} updates</span>
    </div>
    <div class="space-y-3">
      <div class="flex gap-3">
        <span class="material-symbols-outlined text-blue-600 bg-blue-50 rounded-lg p-2 h-10">inventory_2</span>
        <div>
          <p class="text-sm font-semibold">Your listings are visible</p>
          <p class="text-xs text-slate-500">People can request items from your active posts.</p>
        </div>
      </div>
      <div class="flex gap-3">
        <span class="material-symbols-outlined text-green-700 bg-green-50 rounded-lg p-2 h-10">person</span>
        <div>
          <p class="text-sm font-semibold">Profile ready</p>
          <p class="text-xs text-slate-500">Keep your name, email and photo updated for trust.</p>
        </div>
      </div>
    </div>
  `;
  popup.addEventListener('click', (e) => e.stopPropagation());
  placePopup(anchor, popup);
}

function toggleProfilePopup(anchor) {
  const existing = document.getElementById('profile-popup');
  closeFloatingPopups();
  if (existing) return;

  const popup = document.createElement('div');
  popup.id = 'profile-popup';
  popup.dataset.floatingPopup = 'true';
  popup.className = 'w-72 max-w-[calc(100vw-2rem)] bg-white border border-slate-200 rounded-xl shadow-2xl p-4 text-slate-900';
  popup.innerHTML = `
    <div class="flex items-center gap-3 mb-4">
      <img src="${escapeHtml(getUserAvatar())}" alt="Profile avatar" class="w-12 h-12 rounded-full object-cover border border-blue-100">
      <div>
        <p class="text-sm font-bold">${escapeHtml(currentUser?.name || 'Guest user')}</p>
        <p class="text-xs text-slate-500">${escapeHtml(currentUser?.email || 'Sign in to manage profile')}</p>
      </div>
    </div>
    <a href="dashboard.html#profile-panel" class="block text-center bg-blue-600 text-white rounded-lg py-2 text-sm font-semibold">Open Profile</a>
  `;
  popup.addEventListener('click', (e) => e.stopPropagation());
  placePopup(anchor, popup);
}

async function login(email, password) {
  const data = await apiRequest('auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password })
  });
  setToken(data.token);
  currentUser = data.user;
  redirect(currentUser.role === 'admin' ? 'admin-dashboard.html' : 'dashboard.html');
}

async function register(name, email, password, phone) {
  const data = await apiRequest('auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password, phone })
  });
  setToken(data.token);
  currentUser = data.user;
  redirect('dashboard.html');
}

async function logout() {
  try {
    await apiRequest('auth/logout', { method: 'POST' });
  } catch (err) {
    // Local logout should still work if the network request fails.
  }
  setToken(null);
  currentUser = null;
  redirect('landing.html');
}

function setupForms() {
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.onsubmit = async (e) => {
      e.preventDefault();
      const email = loginForm.elements['email']?.value;
      const password = loginForm.elements['password']?.value;
      
      if (!email || !password) {
        return alert('Please enter both email and password.');
      }

      try {
        await login(email, password);
      } catch (err) {
        console.error('Login error:', err);
        alert(err.message || 'Login failed. Please check your credentials.');
      }
    };
  }

  const regForm = document.getElementById('register-form');
  if (regForm) {
    regForm.onsubmit = async (e) => {
      e.preventDefault();
      const name = regForm.elements['name']?.value;
      const email = regForm.elements['email']?.value;
      const password = regForm.elements['password']?.value;
      const phone = regForm.elements['phone']?.value;

      if (!name || !email || !password) {
        return alert('Please fill in all required fields.');
      }

      if (document.getElementById('terms') && !document.getElementById('terms').checked) {
        return alert('Please accept the terms to continue.');
      }

      try {
        await register(name, email, password, phone);
      } catch (err) {
        console.error('Registration error:', err);
        alert(err.message || 'Registration failed. Please try again.');
      }
    };
  }

  const addItemForm = document.getElementById('add-item-form');
  if (addItemForm) {
    addItemForm.onsubmit = async (e) => {
      e.preventDefault();
      const title = document.getElementById('title')?.value;
      const category = document.getElementById('category')?.value;
      
      if (!title || !category) {
        return alert('Please enter both title and category.');
      }

      const payload = new FormData();
      payload.append('title', title);
      payload.append('category', category);
      payload.append('description', document.getElementById('description')?.value || '');
      payload.append('location', document.getElementById('location')?.value || '');
      payload.append('condition', 'good');
      
      if (selectedImageFiles.length > 0) {
        payload.append('image', selectedImageFiles[0]);
      }

      if (category === 'medicine') {
        payload.append('mfgDate', document.getElementById('mfg-date')?.value || '');
        payload.append('expDate', document.getElementById('exp-date')?.value || '');
      }

      try {
        await apiRequest('items', { method: 'POST', body: payload });
        alert('Item listed successfully!');
        selectedImageFiles = [];
        redirect('dashboard.html');
      } catch (err) {
        console.error('Listing error:', err);
        alert(err.message || err.error || 'Failed to list item');
      }
    };
  }

  // Category change listener for medicine fields
  const categorySelect = document.getElementById('category');
  const medicineFields = document.getElementById('medicine-fields');
  if (categorySelect && medicineFields) {
    categorySelect.addEventListener('change', () => {
      if (categorySelect.value === 'medicine') {
        medicineFields.classList.remove('hidden');
      } else {
        medicineFields.classList.add('hidden');
      }
    });
    // Trigger once on load in case it's already selected (e.g. browser back)
    if (categorySelect.value === 'medicine') medicineFields.classList.remove('hidden');
  }

  const profileForm = document.getElementById('profile-form');
  if (profileForm) {
    profileForm.onsubmit = async (e) => {
      e.preventDefault();
      const payload = new FormData();
      payload.append('name', document.getElementById('profile-name').value);
      payload.append('email', document.getElementById('profile-email').value);
      payload.append('bio', document.getElementById('profile-bio')?.value || '');
      if (profileAvatarFile) payload.append('avatar', profileAvatarFile);

      try {
        const res = await apiRequest('auth/profile', { method: 'POST', body: payload });
        currentUser = res.user;
        localStorage.removeItem('profileAvatar');
        profileAvatarFile = null;
        updateUI();
        setProfileStatus('Profile updated.');
      } catch (err) {
        const fallbackAvatar = document.getElementById('profile-avatar-preview')?.src;
        currentUser = {
          ...(currentUser || {}),
          name: document.getElementById('profile-name').value,
          email: document.getElementById('profile-email').value,
          avatar: fallbackAvatar
        };
        if (fallbackAvatar) localStorage.setItem('profileAvatar', fallbackAvatar);
        updateUI();
        setProfileStatus(err.message ? `${err.message}. Preview saved in this browser.` : 'Preview saved in this browser.');
      }
    };
  }
}

function setupImagePicker() {
  const uploadBox = document.getElementById('image-drop-zone');
  const input = document.getElementById('image-input');
  if (!uploadBox || !input) return;

  uploadBox.addEventListener('click', () => input.click());
  uploadBox.addEventListener('dragover', (e) => e.preventDefault());
  uploadBox.addEventListener('drop', (e) => {
    e.preventDefault();
    addSelectedImages(e.dataTransfer.files);
  });
  input.addEventListener('change', () => addSelectedImages(input.files));
}

function setupLocationPicker() {
  const input = document.getElementById('location');
  const preview = document.getElementById('location-map-preview');
  if (!input || !preview) return;

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const val = input.value.trim();
      if (!val) return;
      const encoded = encodeURIComponent(val);
      preview.innerHTML = `
        <iframe 
          width="100%" 
          height="100%" 
          frameborder="0" 
          style="border:0" 
          src="https://maps.google.com/maps?q=${encoded}&t=&z=13&ie=UTF8&iwloc=&output=embed" 
          allowfullscreen>
        </iframe>
      `;
    }, 800);
  });
}

function addSelectedImages(files) {
  if (!files) return;
  const maxImages = 10;
  const currentCount = selectedImageFiles.length;
  const availableSlots = maxImages - currentCount;
  const filesToAdd = Array.from(files).slice(0, availableSlots);
  selectedImageFiles.push(...filesToAdd);
  updateImagePreviews();
}

function removeSelectedImage(index) {
  selectedImageFiles.splice(index, 1);
  updateImagePreviews();
}

function updateImagePreviews() {
  const grid = document.getElementById('image-preview-grid');
  if (!grid) return;
  grid.innerHTML = '';

  selectedImageFiles.forEach((file, index) => {
    const div = document.createElement('div');
    div.className = 'aspect-square rounded-lg bg-surface-container relative overflow-hidden group';
    div.innerHTML = `
      <img class="w-full h-full object-cover" src="${URL.createObjectURL(file)}" alt="Preview ${index + 1}">
      <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
        <button class="material-symbols-outlined text-white" data-icon="delete" type="button" onclick="removeSelectedImage(${index})">delete</button>
      </div>
    `;
    grid.appendChild(div);
  });

  // Add empty slots up to 10
  const totalSlots = 10;
  const emptySlots = totalSlots - selectedImageFiles.length;
  for (let i = 0; i < emptySlots; i++) {
    const div = document.createElement('div');
    div.className = 'aspect-square rounded-lg border-2 border-dashed border-outline-variant flex items-center justify-center cursor-pointer';
    div.innerHTML = '<span class="material-symbols-outlined text-outline" data-icon="add">add</span>';
    div.addEventListener('click', () => document.getElementById('image-input').click());
    grid.appendChild(div);
  }
}

function setupSearchAndFilters() {
  if (!decodeURIComponent(window.location.pathname).includes('browse.html')) return;

  const searchInput = document.querySelector('nav input[placeholder*="Search"]');
  const categoryLinks = document.querySelectorAll('aside nav a');
  const resetButton = Array.from(document.querySelectorAll('aside button')).find((btn) => btn.textContent.trim().toLowerCase().includes('reset'));
  const state = { search: '', category: '', location: '' };
  let timer;

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        state.search = searchInput.value.trim();
        loadItems('items-grid', state);
      }, 250);
    });
  }

  categoryLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      state.category = link.textContent.trim().toLowerCase().replace('garden', 'home').replace('tools', 'other');
      loadItems('items-grid', state);
    });
  });

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      state.search = '';
      state.category = '';
      state.location = '';
      if (searchInput) searchInput.value = '';
      loadItems('items-grid');
    });
  }
}

async function loadItems(containerId, filters = {}) {
  const container = document.getElementById(containerId);
  console.log('loadItems called for', containerId);
  if (!container) return;

  try {
    const qs = new URLSearchParams(Object.entries(filters).filter(([, value]) => value)).toString();
    const items = await apiRequest(`/items${qs ? `?${qs}` : ''}`);

    if (!items.length) {
      container.innerHTML = '<p class="col-span-full text-center text-on-surface-variant">No items found.</p>';
      return;
    }

    container.innerHTML = items.map(itemCard).join('');
    console.log(`loaded ${items.length} items into ${containerId}`);

    // Add delegated click handler so clicking anywhere on a card navigates to details
    if (!container._cardClickHandlerInstalled) {
      container.addEventListener('click', (e) => {
        // ignore clicks on interactive elements
        if (e.target.closest('a, button, input, textarea, select, label')) return;
        const card = e.target.closest('[data-item-id]');
        if (card) {
          const id = card.getAttribute('data-item-id');
          console.log('card clicked, id=', id);
          if (id) window.location.href = `item-detail.html?id=${encodeURIComponent(id)}`;
        }
      });
      container._cardClickHandlerInstalled = true;
      console.log('installed card click delegation on', containerId);
    }
  } catch (err) {
    container.innerHTML = '<p class="col-span-full text-center text-error">Error loading items.</p>';
  }
}

function itemCard(item) {
  const condition = escapeHtml((item.condition || 'good').replace('_', ' '));
  return `
    <a href="item-detail.html?id=${item.id}" data-item-id="${item.id}" onclick="if(!event.target.closest('button,input,textarea,select,label')) window.location.href='item-detail.html?id=${item.id}'" class="block bg-white rounded-2xl overflow-hidden border border-outline-variant hover:shadow-xl transition-all group flex flex-col h-full no-underline text-inherit cursor-pointer">
      <div class="relative h-56 shrink-0">
        <span class="absolute top-3 left-3 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-label-sm font-label-sm text-primary z-10">${condition}</span>
        <img src="${escapeHtml(item.image_url || PLACEHOLDER_IMAGE)}" alt="${escapeHtml(item.title)}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
      </div>
      <div class="p-md flex flex-col flex-grow">
        <div class="flex justify-between items-start mb-2">
          <h4 class="font-headline-md text-headline-md leading-tight">${escapeHtml(item.title)}</h4>
        </div>
        <p class="text-body-sm font-body-sm text-on-surface-variant mb-2 flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">location_on</span> ${escapeHtml(item.location)}
        </p>
        <p class="text-body-sm font-body-sm text-on-surface-variant mb-md line-clamp-2">${escapeHtml(item.description || '')}</p>

        <div class="mt-auto pt-md border-t border-outline-variant flex items-center justify-between">
          <div class="flex items-center gap-2">
            <img src="${escapeHtml(item.donor_avatar || DEFAULT_AVATAR)}" class="w-8 h-8 rounded-full object-cover border border-primary/10">
            <span class="text-xs font-bold text-on-surface truncate max-w-[100px]">${escapeHtml(item.donor_name || 'Donor')}</span>
          </div>
          <span class="bg-primary text-on-primary px-4 py-2 rounded-lg text-xs font-bold">View</span>
        </div>
      </div>
    </a>
  `;
}



async function loadItemDetail() {
  const itemId = new URLSearchParams(window.location.search).get('id');
  if (!itemId) return redirect('browse.html');

  try {
    const item = await apiRequest(`items?id=${itemId}`);
    renderItemDetail(item);
    
    const requestBtn = document.getElementById('request-btn');
    if (requestBtn) {
      requestBtn.onclick = () => requestItem(itemId);
    }
    
    // Load related items (recent ones for now)
    loadItems('related-items-grid', { limit: 4 });
  } catch (err) {
    alert('Item not found');
    redirect('browse.html');
  }
}

async function requestItem(itemId) {
  if (!token) {
    alert('Join ReValue Hub to request items! Please create an account to continue.');
    return redirect('register.html');
  }

  try {
    const res = await apiRequest('requests', {
      method: 'POST',
      body: JSON.stringify({ item_id: itemId })
    });
    alert(res.message || 'Request sent successfully!');
  } catch (err) {
    alert(err.message || 'Error sending request');
  }
}

function renderItemDetail(item, isFallback = false) {
  document.getElementById('detail-title').textContent = item.title;
  document.getElementById('detail-category').textContent = item.category;
  document.getElementById('detail-description').textContent = item.description;
  document.getElementById('detail-location').textContent = item.location;
  document.getElementById('detail-owner').textContent = item.donor_name || 'ReValue member';
  
  const donorAvatar = document.getElementById('detail-donor-avatar');
  if (donorAvatar) {
    donorAvatar.src = item.donor_avatar || DEFAULT_AVATAR;
  }
  
  const viewProfileBtn = document.getElementById('view-profile-btn');
  if (viewProfileBtn && item.donor_id) {
    viewProfileBtn.onclick = () => redirect(`profile.html?userId=${item.donor_id}`);
  }

  document.getElementById('detail-condition').textContent = (item.condition || 'good').replace('_', ' ');
  document.getElementById('detail-posted').textContent = formatDate(item.created_at);

  // Hide dummy fields
  document.querySelectorAll('li').forEach(li => {
    if (li.textContent.includes('Brand') || li.textContent.includes('Color')) {
      li.style.display = 'none';
    }
  });
  
  const imgUrl = item.image_url || PLACEHOLDER_IMAGE;
  document.getElementById('detail-image').src = imgUrl;

  const thumbContainer = document.getElementById('detail-thumbnails');
  if (thumbContainer) {
    thumbContainer.innerHTML = `
      <div class="aspect-square rounded-2xl overflow-hidden border-2 border-primary cursor-pointer shadow-sm">
        <img class="w-full h-full object-cover" src="${imgUrl}">
      </div>
    `;
  }

  const mapContainer = document.getElementById('map-container');
  if (mapContainer && item.location) {
    const encodedLocation = encodeURIComponent(item.location);
    mapContainer.innerHTML = `
      <div class="relative w-full h-full cursor-pointer group" onclick="window.open('https://www.google.com/maps/search/?api=1&query=${encodedLocation}', '_blank')">
        <iframe 
          width="100%" 
          height="100%" 
          frameborder="0" 
          style="border:0; min-height: 160px;" 
          src="https://maps.google.com/maps?q=${encodedLocation}&hl=en&z=14&output=embed" 
          allowfullscreen>
        </iframe>
        <div class="absolute inset-0 bg-transparent group-hover:bg-black/5 transition-colors"></div>
        <div class="absolute bottom-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-primary opacity-0 group-hover:opacity-100 transition-opacity shadow-sm border border-outline-variant flex items-center gap-1">
          <span class="material-symbols-outlined text-[12px]">open_in_new</span> Click to open Google Maps
        </div>
      </div>
    `;
  }

  const requestBtn = document.getElementById('request-btn');
  if (requestBtn) {
    requestBtn.onclick = async () => {
      if (!currentUser) return redirect('register.html');
      if (false) {
        // Removed demo logic
      }
      try {
        await apiRequest('requests', {
          method: 'POST',
          body: JSON.stringify({ item_id: item.id })
        });
        alert('Request sent successfully!');
      } catch (err) {
        alert(err.message || 'Failed to send request.');
      }
    };
  }
}

async function loadUserDashboard() {
  try {
    setupProfilePanel();
    const items = await apiRequest('items/user');
    const container = document.getElementById('user-items-grid');
    if (!container) return;

    updateDashboardCounts(items.length);
    loadUserRequests();

    if (!items.length) {
      container.innerHTML = '<p class="col-span-full text-center text-slate-500">No items listed yet.</p>';
      return;
    }

    container.innerHTML = items.map(userItemCard).join('') + `
      <a href="list-item.html" class="border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center p-md text-slate-400 hover:border-blue-300 hover:text-blue-500 hover:bg-blue-50 transition-all cursor-pointer">
        <span class="material-symbols-outlined text-4xl mb-2" data-icon="add_circle">add_circle</span>
        <span class="font-label-md">List New Item</span>
      </a>
    `;
    // Delegated click handler for the user items grid
    if (!container._cardClickHandlerInstalled) {
      container.addEventListener('click', (e) => {
        if (e.target.closest('a, button, input, textarea, select, label')) return;
        const card = e.target.closest('[data-item-id]');
        if (card) {
          const id = card.getAttribute('data-item-id');
          if (id) window.location.href = `item-detail.html?id=${encodeURIComponent(id)}`;
        }
      });
      container._cardClickHandlerInstalled = true;
    }
  } catch (err) {
    console.error('Error loading dashboard items:', err);
  }
}

function setupProfilePanel() {
  const panel = document.getElementById('settings-section');
  if (!panel) return;

  const nameInput = document.getElementById('profile-name');
  const emailInput = document.getElementById('profile-email');
  const preview = document.getElementById('profile-avatar-preview');
  const input = document.getElementById('profile-avatar-input');
  const uploadButton = document.getElementById('profile-avatar-button');

  if (nameInput) nameInput.value = currentUser?.name || '';
  if (emailInput) emailInput.value = currentUser?.email || '';
  if (preview) preview.src = getUserAvatar();

  uploadButton?.addEventListener('click', () => input?.click());
  input?.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    profileAvatarFile = file;
    const reader = new FileReader();
    reader.onload = () => {
      const nextSrc = reader.result;
      if (preview) preview.src = nextSrc;
      document.querySelectorAll('[data-user-avatar]').forEach((img) => img.src = nextSrc);
    };
    reader.readAsDataURL(file);
  });
}

function setProfileStatus(message) {
  const status = document.getElementById('profile-status');
  if (!status) return;
  status.textContent = message;
  status.classList.remove('hidden');
  window.setTimeout(() => status.classList.add('hidden'), 3500);
}

async function loadUserRequests() {
  const tbody = document.getElementById('user-requests-tbody');
  if (!tbody) return;

  try {
    const requests = await apiRequest('requests/user');
    if (!requests.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="px-lg py-4 text-center text-slate-500">No requests yet.</td></tr>';
      return;
    }

    tbody.innerHTML = requests.map((request) => `
      <tr class="hover:bg-slate-50/50 transition-colors">
        <td class="px-lg py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
              <span class="material-symbols-outlined" data-icon="inventory_2">inventory_2</span>
            </div>
            <span class="font-label-md text-slate-900">${escapeHtml(request.Item?.title || 'Requested item')}</span>
          </div>
        </td>
        <td class="px-lg py-4 text-body-sm text-slate-500">Request</td>
        <td class="px-lg py-4">
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">${escapeHtml(request.status)}</span>
        </td>
        <td class="px-lg py-4 text-body-sm text-slate-500">${formatDate(request.created_at)}</td>
        <td class="px-lg py-4 text-right">
          <a href="item-detail.html?id=${request.Item?.id || ''}" class="text-blue-600 font-label-md hover:underline">Details</a>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error('Error loading requests:', err);
  }
}

function updateDashboardCounts(count) {
  const cards = document.querySelectorAll('section.grid h3');
  if (cards[0]) cards[0].textContent = String(count).padStart(2, '0');
}

function userItemCard(item) {
  return `
    <a href="item-detail.html?id=${item.id}" data-item-id="${item.id}" onclick="if(!event.target.closest('button,input,textarea,select,label')) window.location.href='item-detail.html?id=${item.id}'" class="block bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-all no-underline text-inherit">
      <div class="h-48 relative overflow-hidden">
        <img alt="${escapeHtml(item.title)}" class="w-full h-full object-cover" src="${escapeHtml(item.image_url || PLACEHOLDER_IMAGE)}">
        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-blue-600 shadow-sm">${escapeHtml(item.category)}</div>
      </div>
      <div class="p-md">
        <h5 class="font-headline-md text-slate-900 mb-1">${escapeHtml(item.title)}</h5>
        <div class="flex items-center gap-2 text-slate-500 mb-4">
          <span class="material-symbols-outlined text-[18px]" data-icon="location_on">location_on</span>
          <span class="text-body-sm">${escapeHtml(item.location)}</span>
        </div>
        <div class="flex gap-2">
          <span class="flex-grow bg-blue-50 text-blue-600 font-label-md py-2 rounded-lg text-center">Details</span>
          <button onclick="deleteItem(${item.id})" class="p-2 border border-slate-200 rounded-lg text-slate-400 hover:text-error hover:border-error transition-colors">
            <span class="material-symbols-outlined" data-icon="delete">delete</span>
          </button>
        </div>
      </div>
    </a>
  `;
}

async function loadAdminDashboard() {
  try {
    const stats = await apiRequest('admin/stats');
    document.getElementById('stat-users').textContent = stats.users;
    document.getElementById('stat-items').textContent = stats.items;

    const users = await apiRequest('admin/users');
    const userContainer = document.getElementById('admin-users-tbody');
    if (userContainer) {
      userContainer.innerHTML = users.map((user) => `
        <tr>
          <td class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center font-bold text-xs text-slate-600">${escapeHtml(user.name).slice(0, 2).toUpperCase()}</div>
              <div>
                <p class="text-sm font-semibold text-slate-900">${escapeHtml(user.name)}</p>
                <p class="text-xs text-slate-500">${escapeHtml(user.email)}</p>
              </div>
            </div>
          </td>
          <td class="px-6 py-4">
            <span class="flex items-center gap-1.5 text-xs font-bold text-secondary">
              <span class="w-2 h-2 rounded-full bg-secondary"></span> ${escapeHtml(user.role)}
            </span>
          </td>
          <td class="px-6 py-4">${formatDate(user.created_at)}</td>
          <td class="px-6 py-4 text-right">
            <button onclick="deleteUser(${user.id})" class="text-error hover:underline text-sm font-medium">Delete</button>
          </td>
        </tr>
      `).join('');
    }
  } catch (err) {
    console.error('Error loading admin dashboard:', err);
  }
}

window.deleteItem = async (id) => {
  if (!confirm('Are you sure you want to delete this item?')) return;
  try {
    await apiRequest(`/items/${id}`, { method: 'DELETE' });
    loadUserDashboard();
  } catch (err) {
    alert(err.message || 'Error deleting item');
  }
};

window.deleteUser = async (id) => {
  if (!confirm('Are you sure you want to delete this user?')) return;
  try {
    await apiRequest(`/admin/users/${id}`, { method: 'DELETE' });
    loadAdminDashboard();
  } catch (err) {
    alert(err.message || 'Error deleting user');
  }
};

window.ReValue = { login, register, logout, loadItems };

function showSection(sectionId, element) {
  // Hide all sections
  document.querySelectorAll('.dashboard-view').forEach(view => view.classList.add('hidden'));
  
  // Show target section
  const target = document.getElementById(sectionId + '-section');
  if (target) target.classList.remove('hidden');
  
  // Update sidebar active state
  if (element) {
    document.querySelectorAll('.nav-link').forEach(link => {
      link.classList.remove('bg-blue-50', 'text-blue-600', 'font-bold');
      link.classList.add('text-slate-500');
    });
    element.classList.add('bg-blue-50', 'text-blue-600', 'font-bold');
    element.classList.remove('text-slate-500');
  }
}

window.showSection = showSection;

async function checkNotifications() {
  if (!token) return;
  try {
    const notifs = await apiRequest('notifications');
    const unread = notifs.filter(n => !n.is_read).length;
    const badge = document.getElementById('notif-badge');
    if (badge) {
      if (unread > 0) {
        badge.textContent = unread;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    }
  } catch (err) {
    console.error('Error checking notifications:', err);
  }
}

// Check every 30 seconds
if (token) {
  checkNotifications();
  setInterval(checkNotifications, 30000);
}
