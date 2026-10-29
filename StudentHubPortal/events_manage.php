<?php
// events_manage.php - Admin Event Management Module (Poster Upload, File Validation & Async Operations)
require_once __DIR__ . '/auth.php';

// Restrict access: Only administrators can access event management
require_login('admin');

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Admin Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
            background: var(--bg-surface, #ffffff);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .filter-bar input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-surface-elevated, #f9fafb);
            color: var(--text-primary);
            font-size: 13.5px;
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
        }
        .poster-thumb {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: #eee;
            display: block;
        }
        .poster-placeholder {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-surface-elevated, #f1f5f9);
            color: var(--text-muted);
            border-radius: 6px;
            border: 1px dashed var(--border-color);
            font-size: 10px;
            text-align: center;
            line-height: 1.2;
        }
        .preview-box {
            margin-top: 10px;
            display: none;
            text-align: center;
        }
        .preview-box img {
            max-width: 140px;
            max-height: 140px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            object-fit: cover;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #2563eb;
            color: white;
            border: none;
            margin-right: 6px;
        }
        .btn-edit:hover {
            background-color: #1d4ed8;
        }
        .btn-delete {
            background-color: #dc2626;
            color: white;
            border: none;
        }
        .btn-delete:hover {
            background-color: #b91c1c;
        }
        .badge-reg {
            display: inline-block;
            background: #10b981;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 10px;
        }
        .toast-msg {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13.5px;
            display: none;
            transition: all 0.3s ease;
        }
        .toast-success {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        [data-theme="dark"] .toast-success {
            background-color: rgba(6, 95, 70, 0.25);
            border-color: #059669;
            color: #6ee7b7;
        }
        .toast-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        [data-theme="dark"] .toast-error {
            background-color: rgba(153, 27, 27, 0.25);
            border-color: #dc2626;
            color: #fca5a5;
        }
        .loading-spinner {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
        }
        .badge-count {
            background: var(--primary, #2563eb);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 6px;
        }
    </style>
</head>
<body>

    <!-- Dynamic Sidebar Menu -->
    <?php render_sidebar('events_manage'); ?>

    <!-- Dynamic Top Navbar -->
    <?php render_top_navbar('Event Management'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="action-header">
            <div>
                <h1>Campus Events Management</h1>
                <p>Create, update, and remove campus events with poster uploads (file type & size validated).</p>
            </div>
            <div>
                <button type="button" id="btn-open-add" class="btn">+ Create New Event</button>
            </div>
        </div>

        <!-- Real-Time Toast Alert -->
        <div id="toast-alert" class="toast-msg" role="status" aria-live="polite"></div>

        <!-- Search Bar -->
        <div class="filter-bar">
            <input type="text" id="search-input" placeholder="Search events by title, location, or description...">
            <button type="button" id="btn-reset-search" class="btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); font-size: 13px;">Clear</button>
        </div>

        <!-- Events Table Card -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Scheduled Events <span id="event-count" class="badge-count">0</span></h3>
                <span style="font-size: 12px; color: var(--text-muted);">Asynchronous updates via Fetch API</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px;">Poster</th>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Description</th>
                            <th>Registrations</th>
                            <th style="text-align: right; width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="events-tbody">
                        <tr>
                            <td colspan="7" class="loading-spinner">Loading events...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal: Add / Edit Event -->
    <div id="event-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <h3 id="modal-title">Create New Event</h3>
                <button type="button" class="modal-close-btn" id="modal-close-x" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="event-form" enctype="multipart/form-data">
                    <input type="hidden" id="event-id" name="event_id" value="">
                    <input type="hidden" id="form-action" name="action" value="create">

                    <div id="modal-error" class="toast-msg toast-error" style="margin-bottom: 12px;"></div>

                    <div class="form-group">
                        <label for="event-title">Event Title *</label>
                        <input type="text" id="event-title" name="title" placeholder="e.g. Annual Hackathon 2027" required>
                    </div>

                    <div class="form-group">
                        <label for="event-date">Event Date *</label>
                        <input type="date" id="event-date" name="event_date" required>
                    </div>

                    <div class="form-group">
                        <label for="event-location">Location / Venue *</label>
                        <input type="text" id="event-location" name="location" placeholder="e.g. Auditorium, CSPIT Campus" required>
                    </div>

                    <div class="form-group">
                        <label for="event-desc">Event Description</label>
                        <textarea id="event-desc" name="description" rows="3" placeholder="Brief summary of event rules, highlights, or schedule..."></textarea>
                    </div>

                    <!-- Poster Upload with File Validation -->
                    <div class="form-group">
                        <label for="event-poster">Event Poster Image (Max 2MB: JPG, PNG, WEBP)</label>
                        <input type="file" id="event-poster" name="poster" accept=".jpg,.jpeg,.png,.webp">
                        <span id="poster-error" class="error-msg"></span>

                        <div id="preview-box" class="preview-box">
                            <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Poster Preview:</p>
                            <img id="poster-preview-img" src="" alt="Poster preview">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn" id="modal-cancel-btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); margin-right: 8px;">Cancel</button>
                        <button type="submit" class="btn" id="modal-submit-btn">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm Delete Event -->
    <div id="delete-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-box" style="max-width: 420px;">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button type="button" class="modal-close-btn" id="delete-close-x">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete event <strong id="delete-event-title"></strong>?</p>
                <p style="font-size: 12.5px; color: var(--text-muted);">This action will delete the event, its uploaded poster file, and any student registrations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" id="delete-cancel-btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); margin-right: 8px;">Cancel</button>
                <button type="button" class="btn btn-delete" id="delete-confirm-btn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script src="js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody          = document.getElementById('events-tbody');
            const searchInput    = document.getElementById('search-input');
            const btnReset       = document.getElementById('btn-reset-search');
            const eventCount     = document.getElementById('event-count');
            const toastAlert     = document.getElementById('toast-alert');

            // Modal elements
            const eventModal     = document.getElementById('event-modal');
            const eventForm      = document.getElementById('event-form');
            const modalTitle     = document.getElementById('modal-title');
            const modalError     = document.getElementById('modal-error');
            const btnOpenAdd     = document.getElementById('btn-open-add');
            const modalCloseX    = document.getElementById('modal-close-x');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');

            // Poster file & preview
            const posterInput    = document.getElementById('event-poster');
            const posterError    = document.getElementById('poster-error');
            const previewBox     = document.getElementById('preview-box');
            const previewImg     = document.getElementById('poster-preview-img');

            // Delete modal elements
            const deleteModal    = document.getElementById('delete-modal');
            const deleteTitle    = document.getElementById('delete-event-title');
            const deleteConfirm  = document.getElementById('delete-confirm-btn');
            const deleteCloseX   = document.getElementById('delete-close-x');
            const deleteCancel   = document.getElementById('delete-cancel-btn');

            let eventToDeleteId = null;
            let debounceTimer = null;

            function showToast(message, isSuccess = true) {
                toastAlert.textContent = message;
                toastAlert.className = 'toast-msg ' + (isSuccess ? 'toast-success' : 'toast-error');
                toastAlert.style.display = 'block';
                setTimeout(() => { toastAlert.style.display = 'none'; }, 4000);
            }

            // 1. Fetch & Render Events Asynchronously
            async function loadEvents() {
                const search = encodeURIComponent(searchInput.value.trim());
                try {
                    const res = await fetch(`api/events.php?search=${search}`);
                    const data = await res.json();

                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="7" class="loading-spinner" style="color: #dc2626;">Error: ${data.message}</td></tr>`;
                        return;
                    }

                    eventCount.textContent = data.count || 0;

                    if (!data.events || data.events.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" class="loading-spinner">No events found. Click "+ Create New Event" to add one.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = data.events.map(ev => {
                        const posterHtml = ev.poster
                            ? `<img src="${escapeHtml(ev.poster)}" alt="Poster" class="poster-thumb">`
                            : `<div class="poster-placeholder">No Poster</div>`;

                        const shortDesc = ev.description ? (ev.description.length > 55 ? escapeHtml(ev.description.substring(0, 55)) + '...' : escapeHtml(ev.description)) : '—';
                        const regCount = ev.registration_count || 0;

                        return `
                            <tr>
                                <td>${posterHtml}</td>
                                <td><strong>${escapeHtml(ev.title)}</strong></td>
                                <td>${escapeHtml(ev.event_date)}</td>
                                <td>${escapeHtml(ev.location)}</td>
                                <td style="font-size: 12.5px; color: var(--text-secondary);">${shortDesc}</td>
                                <td><span class="badge-reg">${regCount} registered</span></td>
                                <td style="text-align: right;">
                                    <button class="btn btn-sm btn-edit" data-id="${ev.event_id}" title="Edit Event">Edit</button>
                                    <button class="btn btn-sm btn-delete" data-id="${ev.event_id}" data-title="${escapeHtml(ev.title)}" title="Delete Event">Delete</button>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Attach button listeners
                    document.querySelectorAll('.btn-edit').forEach(btn => {
                        btn.addEventListener('click', () => openEditModal(btn.dataset.id));
                    });

                    document.querySelectorAll('.btn-delete').forEach(btn => {
                        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id, btn.dataset.title));
                    });

                } catch (err) {
                    console.error("Fetch error:", err);
                    tbody.innerHTML = `<tr><td colspan="7" class="loading-spinner" style="color: #dc2626;">Failed to load events from server.</td></tr>`;
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }

            // Search debounce
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(loadEvents, 300);
            });

            btnReset.addEventListener('click', function () {
                searchInput.value = '';
                loadEvents();
            });

            // 2. Client-side Poster Validation & Preview
            posterInput.addEventListener('change', function () {
                posterError.classList.remove('show');
                const file = this.files[0];
                if (!file) {
                    previewBox.style.display = 'none';
                    return;
                }

                // File size check: Max 2MB = 2097152 bytes
                if (file.size > 2 * 1024 * 1024) {
                    posterError.textContent = 'File size exceeds 2MB limit. Please choose a smaller image.';
                    posterError.classList.add('show');
                    this.value = '';
                    previewBox.style.display = 'none';
                    return;
                }

                // File type check
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    posterError.textContent = 'Invalid file format. Allowed formats: JPG, PNG, WEBP.';
                    posterError.classList.add('show');
                    this.value = '';
                    previewBox.style.display = 'none';
                    return;
                }

                // Live preview using FileReader
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewBox.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });

            // 3. Open Add Modal
            btnOpenAdd.addEventListener('click', function () {
                modalTitle.textContent = 'Create New Event';
                document.getElementById('form-action').value = 'create';
                document.getElementById('event-id').value = '';
                eventForm.reset();
                previewBox.style.display = 'none';
                posterError.classList.remove('show');
                modalError.style.display = 'none';
                eventModal.classList.add('open');
                document.getElementById('event-title').focus();
            });

            // 4. Open Edit Modal (Fetch single event)
            async function openEditModal(id) {
                try {
                    const res = await fetch(`api/events.php?id=${id}`);
                    const data = await res.json();
                    if (!data.success || !data.event) {
                        showToast(data.message || 'Event not found.', false);
                        return;
                    }

                    const ev = data.event;
                    modalTitle.textContent = `Edit Event #${ev.event_id}`;
                    document.getElementById('form-action').value = 'update';
                    document.getElementById('event-id').value = ev.event_id;
                    document.getElementById('event-title').value = ev.title || '';
                    document.getElementById('event-date').value = ev.event_date || '';
                    document.getElementById('event-location').value = ev.location || '';
                    document.getElementById('event-desc').value = ev.description || '';

                    posterInput.value = '';
                    posterError.classList.remove('show');
                    if (ev.poster) {
                        previewImg.src = ev.poster;
                        previewBox.style.display = 'block';
                    } else {
                        previewBox.style.display = 'none';
                    }

                    modalError.style.display = 'none';
                    eventModal.classList.add('open');
                } catch (err) {
                    showToast('Failed to load event data.', false);
                }
            }

            function closeModal() {
                eventModal.classList.remove('open');
            }

            modalCloseX.addEventListener('click', closeModal);
            modalCancelBtn.addEventListener('click', closeModal);

            // 5. Submit Event Form (Multipart FormData via Fetch API)
            eventForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                modalError.style.display = 'none';

                const formData = new FormData(eventForm);

                try {
                    const res = await fetch('api/events.php', {
                        method: 'POST',
                        body: formData // Browser sets multipart/form-data boundary automatically!
                    });

                    const data = await res.json();

                    if (!data.success) {
                        modalError.textContent = data.message || 'An error occurred.';
                        modalError.style.display = 'block';
                        return;
                    }

                    closeModal();
                    showToast(data.message, true);
                    loadEvents(); // Refresh event list asynchronously!
                } catch (err) {
                    modalError.textContent = 'Server or network error. Please try again.';
                    modalError.style.display = 'block';
                }
            });

            // 6. Delete Modal & Async Delete
            function openDeleteModal(id, title) {
                eventToDeleteId = id;
                deleteTitle.textContent = title;
                deleteModal.classList.add('open');
            }

            function closeDeleteModal() {
                eventToDeleteId = null;
                deleteModal.classList.remove('open');
            }

            deleteCloseX.addEventListener('click', closeDeleteModal);
            deleteCancel.addEventListener('click', closeDeleteModal);

            deleteConfirm.addEventListener('click', async function () {
                if (!eventToDeleteId) return;

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('event_id', eventToDeleteId);

                try {
                    const res = await fetch('api/events.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();
                    closeDeleteModal();

                    if (data.success) {
                        showToast(data.message, true);
                        loadEvents(); // Refresh event list asynchronously!
                    } else {
                        showToast(data.message || 'Failed to delete event.', false);
                    }
                } catch (err) {
                    closeDeleteModal();
                    showToast('Network error while deleting.', false);
                }
            });

            // Initial load
            loadEvents();
        });
    </script>
</body>
</html>
