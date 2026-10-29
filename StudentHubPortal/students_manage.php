<?php
// students_manage.php - Student Management Module (Full CRUD, Search & Filter via Fetch API)
require_once __DIR__ . '/auth.php';

// Restrict access: Logged in users only
require_login();

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
            background: var(--bg-surface, #ffffff);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-surface-elevated, #f9fafb);
            color: var(--text-primary);
            font-size: 13.5px;
        }
        .filter-bar input {
            flex: 1;
            min-width: 220px;
        }
        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
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
    <?php render_sidebar('students_manage'); ?>

    <!-- Dynamic Top Navbar -->
    <?php render_top_navbar('Student Management'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="action-header">
            <div>
                <h1>Student Directory</h1>
                <p>Manage, search, filter, edit, and delete student records using asynchronous Fetch API.</p>
            </div>
            <div>
                <button type="button" id="btn-open-add" class="btn">+ Add New Student</button>
            </div>
        </div>

        <!-- Real-Time Feedback Alert -->
        <div id="toast-alert" class="toast-msg" role="status" aria-live="polite"></div>

        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <input type="text" id="search-input" placeholder="Search by name, email, or mobile...">
            <select id="filter-dept">
                <option value="">All Departments</option>
                <option value="Computer Engineering">Computer Engineering</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Computer Science">Computer Science & Engineering</option>
                <option value="Mechanical Engineering">Mechanical Engineering</option>
                <option value="Civil Engineering">Civil Engineering</option>
                <option value="Electrical Engineering">Electrical Engineering</option>
            </select>
            <select id="filter-inst">
                <option value="">All Institutes</option>
                <option value="CSPIT">CSPIT</option>
                <option value="DEPSTAR">DEPSTAR</option>
                <option value="RPCP">RPCP</option>
                <option value="CMPICA">CMPICA</option>
                <option value="I2IM">I2IM</option>
                <option value="PDPIAS">PDPIAS</option>
            </select>
            <button type="button" id="btn-reset-filters" class="btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); font-size: 13px;">Reset</button>
        </div>

        <!-- Students Table Card -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Student Records <span id="record-count" class="badge-count">0</span></h3>
                <span id="sync-status" style="font-size: 12px; color: var(--text-muted);">Synced with MySQL</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Mobile</th>
                            <th>Department</th>
                            <th>Institute</th>
                            <th>Added Date</th>
                            <th style="text-align: right; width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="students-tbody">
                        <tr>
                            <td colspan="8" class="loading-spinner">Loading student records...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal: Add / Edit Student -->
    <div id="student-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modal-title">Add Student</h3>
                <button type="button" class="modal-close-btn" id="modal-close-x" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="student-form">
                    <input type="hidden" id="student-id" name="student_id" value="">
                    <input type="hidden" id="form-action" name="action" value="create">

                    <div id="modal-error" class="toast-msg toast-error" style="margin-bottom: 12px;"></div>

                    <div class="form-group">
                        <label for="first-name">First Name *</label>
                        <input type="text" id="first-name" name="first_name" placeholder="e.g. Smit" required>
                    </div>

                    <div class="form-group">
                        <label for="last-name">Last Name *</label>
                        <input type="text" id="last-name" name="last_name" placeholder="e.g. Mendapara" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="e.g. smit@charusat.edu.in" required>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number (10 digits)</label>
                        <input type="text" id="mobile" name="mobile" placeholder="e.g. 9876543210">
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">-- Select Department --</option>
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Computer Science">Computer Science & Engineering</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="institute">Institute</label>
                        <select id="institute" name="institute">
                            <option value="">-- Select Institute --</option>
                            <option value="CSPIT">CSPIT</option>
                            <option value="DEPSTAR">DEPSTAR</option>
                            <option value="RPCP">RPCP</option>
                            <option value="CMPICA">CMPICA</option>
                            <option value="I2IM">I2IM</option>
                            <option value="PDPIAS">PDPIAS</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn" id="modal-cancel-btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); margin-right: 8px;">Cancel</button>
                        <button type="submit" class="btn" id="modal-submit-btn">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div id="delete-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-box" style="max-width: 420px;">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button type="button" class="modal-close-btn" id="delete-close-x">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete student <strong id="delete-student-name"></strong>?</p>
                <p style="font-size: 12.5px; color: var(--text-muted);">This will remove the student record and any associated event registrations permanently.</p>
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
            const tbody          = document.getElementById('students-tbody');
            const searchInput    = document.getElementById('search-input');
            const filterDept     = document.getElementById('filter-dept');
            const filterInst     = document.getElementById('filter-inst');
            const btnReset       = document.getElementById('btn-reset-filters');
            const recordCount    = document.getElementById('record-count');
            const toastAlert     = document.getElementById('toast-alert');

            // Modal elements
            const studentModal   = document.getElementById('student-modal');
            const studentForm    = document.getElementById('student-form');
            const modalTitle     = document.getElementById('modal-title');
            const modalError     = document.getElementById('modal-error');
            const btnOpenAdd     = document.getElementById('btn-open-add');
            const modalCloseX    = document.getElementById('modal-close-x');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');

            // Delete Modal elements
            const deleteModal    = document.getElementById('delete-modal');
            const deleteNameSpan = document.getElementById('delete-student-name');
            const deleteConfirm  = document.getElementById('delete-confirm-btn');
            const deleteCloseX   = document.getElementById('delete-close-x');
            const deleteCancel   = document.getElementById('delete-cancel-btn');

            let studentToDeleteId = null;
            let debounceTimer = null;

            // Show Toast Alert
            function showToast(message, isSuccess = true) {
                toastAlert.textContent = message;
                toastAlert.className = 'toast-msg ' + (isSuccess ? 'toast-success' : 'toast-error');
                toastAlert.style.display = 'block';
                setTimeout(() => {
                    toastAlert.style.display = 'none';
                }, 4000);
            }

            // 1. Fetch & Render Students Asynchronously
            async function loadStudents() {
                const search = encodeURIComponent(searchInput.value.trim());
                const dept   = encodeURIComponent(filterDept.value);
                const inst   = encodeURIComponent(filterInst.value);

                const url = `api/students.php?search=${search}&department=${dept}&institute=${inst}`;

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="8" class="loading-spinner" style="color: #dc2626;">Error: ${data.message}</td></tr>`;
                        return;
                    }

                    recordCount.textContent = data.count || 0;

                    if (!data.students || data.students.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="8" class="loading-spinner">No student records found matching the criteria.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = data.students.map(s => {
                        const fullName = `${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}`;
                        const dateFormatted = s.created_at ? s.created_at.substring(0, 10) : '—';
                        return `
                            <tr>
                                <td>#${s.student_id}</td>
                                <td><strong>${fullName}</strong></td>
                                <td>${escapeHtml(s.email)}</td>
                                <td>${escapeHtml(s.mobile || '—')}</td>
                                <td>${escapeHtml(s.department || '—')}</td>
                                <td>${escapeHtml(s.institute || '—')}</td>
                                <td>${dateFormatted}</td>
                                <td style="text-align: right;">
                                    <button class="btn btn-sm btn-edit" data-id="${s.student_id}" title="Edit Student">Edit</button>
                                    <button class="btn btn-sm btn-delete" data-id="${s.student_id}" data-name="${fullName}" title="Delete Student">Delete</button>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Attach Edit & Delete button listeners
                    document.querySelectorAll('.btn-edit').forEach(btn => {
                        btn.addEventListener('click', () => openEditModal(btn.dataset.id));
                    });

                    document.querySelectorAll('.btn-delete').forEach(btn => {
                        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id, btn.dataset.name));
                    });

                } catch (err) {
                    console.error("Fetch error:", err);
                    tbody.innerHTML = `<tr><td colspan="8" class="loading-spinner" style="color: #dc2626;">Failed to load records from server.</td></tr>`;
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }

            // Search input debounce (300ms)
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(loadStudents, 300);
            });

            // Filter dropdowns
            filterDept.addEventListener('change', loadStudents);
            filterInst.addEventListener('change', loadStudents);

            btnReset.addEventListener('click', function () {
                searchInput.value = '';
                filterDept.value = '';
                filterInst.value = '';
                loadStudents();
            });

            // 2. Open Add Modal
            btnOpenAdd.addEventListener('click', function () {
                modalTitle.textContent = 'Add New Student';
                document.getElementById('form-action').value = 'create';
                document.getElementById('student-id').value = '';
                studentForm.reset();
                modalError.style.display = 'none';
                studentModal.classList.add('open');
                document.getElementById('first-name').focus();
            });

            // 3. Open Edit Modal (Fetch single student)
            async function openEditModal(id) {
                try {
                    const res = await fetch(`api/students.php?id=${id}`);
                    const data = await res.json();
                    if (!data.success || !data.student) {
                        showToast(data.message || 'Student not found.', false);
                        return;
                    }

                    const s = data.student;
                    modalTitle.textContent = `Edit Student #${s.student_id}`;
                    document.getElementById('form-action').value = 'update';
                    document.getElementById('student-id').value = s.student_id;
                    document.getElementById('first-name').value = s.first_name || '';
                    document.getElementById('last-name').value = s.last_name || '';
                    document.getElementById('email').value = s.email || '';
                    document.getElementById('mobile').value = s.mobile || '';
                    document.getElementById('department').value = s.department || '';
                    document.getElementById('institute').value = s.institute || '';

                    modalError.style.display = 'none';
                    studentModal.classList.add('open');
                } catch (err) {
                    showToast('Failed to load student data.', false);
                }
            }

            function closeModal() {
                studentModal.classList.remove('open');
            }

            modalCloseX.addEventListener('click', closeModal);
            modalCancelBtn.addEventListener('click', closeModal);

            // 4. Save Student (Submit Form via Fetch API)
            studentForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                modalError.style.display = 'none';

                const formData = new FormData(studentForm);
                const payload = Object.fromEntries(formData.entries());

                try {
                    const res = await fetch('api/students.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();

                    if (!data.success) {
                        modalError.textContent = data.message || 'An error occurred.';
                        modalError.style.display = 'block';
                        return;
                    }

                    closeModal();
                    showToast(data.message, true);
                    loadStudents(); // Refresh table without reloading page!
                } catch (err) {
                    modalError.textContent = 'Network or server error. Please try again.';
                    modalError.style.display = 'block';
                }
            });

            // 5. Delete Student Modal & Async Delete
            function openDeleteModal(id, name) {
                studentToDeleteId = id;
                deleteNameSpan.textContent = name;
                deleteModal.classList.add('open');
            }

            function closeDeleteModal() {
                studentToDeleteId = null;
                deleteModal.classList.remove('open');
            }

            deleteCloseX.addEventListener('click', closeDeleteModal);
            deleteCancel.addEventListener('click', closeDeleteModal);

            deleteConfirm.addEventListener('click', async function () {
                if (!studentToDeleteId) return;

                try {
                    const res = await fetch('api/students.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            student_id: studentToDeleteId
                        })
                    });

                    const data = await res.json();
                    closeDeleteModal();

                    if (data.success) {
                        showToast(data.message, true);
                        loadStudents(); // Refresh table asynchronously!
                    } else {
                        showToast(data.message || 'Failed to delete student.', false);
                    }
                } catch (err) {
                    closeDeleteModal();
                    showToast('Network error while deleting.', false);
                }
            });

            // Initial load
            loadStudents();
        });
    </script>
</body>
</html>
