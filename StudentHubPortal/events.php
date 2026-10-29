<?php
// events.php - Student Campus Events View & Registration
require_once __DIR__ . '/auth.php';

// Restrict access: Logged-in users only
require_login();

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Events - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }
        .event-poster-wrapper {
            width: 100%;
            height: 160px;
            background: var(--bg-surface-elevated, #f1f5f9);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }
        .event-poster-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .event-poster-empty {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
        }
        .event-card-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .event-date-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            align-self: flex-start;
        }
        [data-theme="dark"] .event-date-badge {
            background: rgba(55, 48, 163, 0.4);
            color: #a5b4fc;
        }
        .event-card h3 {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: var(--text-heading);
        }
        .event-location {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .event-desc {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
        }
        .toast-msg {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13.5px;
            display: none;
        }
        .toast-success {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .toast-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .btn-registered {
            background-color: #10b981 !important;
            cursor: default !important;
        }
    </style>
</head>
<body>

    <!-- Dynamic Sidebar Menu -->
    <?php render_sidebar('events'); ?>

    <!-- Dynamic Top Navbar -->
    <?php render_top_navbar('Campus Events'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Campus Events</h1>
            <p>Explore upcoming workshops, technical symposiums, and cultural events.</p>
        </div>

        <div id="toast-alert" class="toast-msg"></div>

        <div id="events-container" class="events-grid">
            <p style="color: var(--text-muted);">Loading upcoming events...</p>
        </div>
    </main>

    <script src="js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container  = document.getElementById('events-container');
            const toastAlert = document.getElementById('toast-alert');

            function showToast(message, isSuccess = true) {
                toastAlert.textContent = message;
                toastAlert.className = 'toast-msg ' + (isSuccess ? 'toast-success' : 'toast-error');
                toastAlert.style.display = 'block';
                setTimeout(() => { toastAlert.style.display = 'none'; }, 4000);
            }

            async function loadEvents() {
                try {
                    const res = await fetch('api/events.php');
                    const data = await res.json();

                    if (!data.success || !data.events || data.events.length === 0) {
                        container.innerHTML = '<p style="color: var(--text-muted);">No campus events are currently scheduled.</p>';
                        return;
                    }

                    container.innerHTML = data.events.map(ev => {
                        const posterHtml = ev.poster
                            ? `<img src="${escapeHtml(ev.poster)}" alt="Event Poster">`
                            : `<span class="event-poster-empty">No Poster Available</span>`;

                        return `
                            <div class="event-card">
                                <div class="event-poster-wrapper">
                                    ${posterHtml}
                                </div>
                                <div class="event-card-body">
                                    <span class="event-date-badge">📅 ${escapeHtml(ev.event_date)}</span>
                                    <h3>${escapeHtml(ev.title)}</h3>
                                    <div class="event-location">📍 ${escapeHtml(ev.location)}</div>
                                    <p class="event-desc">${escapeHtml(ev.description || 'No description provided.')}</p>
                                    <button class="btn btn-register" data-id="${ev.event_id}" style="width: 100%;">
                                        Register for Event
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');

                    document.querySelectorAll('.btn-register').forEach(btn => {
                        btn.addEventListener('click', () => registerForEvent(btn));
                    });

                } catch (err) {
                    container.innerHTML = '<p style="color: #dc2626;">Failed to load events. Please try again later.</p>';
                }
            }

            async function registerForEvent(button) {
                const eventId = button.dataset.id;
                button.disabled = true;
                button.textContent = 'Registering...';

                const formData = new FormData();
                formData.append('action', 'register');
                formData.append('event_id', eventId);

                try {
                    const res = await fetch('api/events.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        button.textContent = '✓ Registered';
                        button.classList.add('btn-registered');
                        showToast(data.message, true);
                    } else {
                        button.disabled = false;
                        button.textContent = 'Register for Event';
                        showToast(data.message, false);
                    }
                } catch (err) {
                    button.disabled = false;
                    button.textContent = 'Register for Event';
                    showToast('Network error while registering.', false);
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }

            loadEvents();
        });
    </script>
</body>
</html>
