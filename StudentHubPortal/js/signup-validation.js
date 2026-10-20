document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signup-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Get input elements
        const firstName = document.getElementById('first-name');
        const lastName = document.getElementById('last-name');
        const email = document.getElementById('email');
        const genderRadios = document.querySelectorAll('input[name="gender"]');
        const department = document.getElementById('department');
        const mobile = document.getElementById('mobile');
        const institute = document.getElementById('institute');
        const academicYear = document.getElementById('academic-year');
        const password = document.getElementById('password');

        // Get error elements
        const firstNameErr = document.getElementById('first-name-error');
        const lastNameErr = document.getElementById('last-name-error');
        const emailErr = document.getElementById('email-error');
        const genderErr = document.getElementById('gender-error');
        const departmentErr = document.getElementById('department-error');
        const mobileErr = document.getElementById('mobile-error');
        const instituteErr = document.getElementById('institute-error');
        const academicYearErr = document.getElementById('academic-year-error');
        const passwordErr = document.getElementById('password-error');

        let isValid = true;

        // 1. First Name Validation
        if (!firstName.value.trim()) {
            firstName.classList.add('input-error');
            firstNameErr.classList.add('show');
            isValid = false;
        } else {
            firstName.classList.remove('input-error');
            firstNameErr.classList.remove('show');
        }

        // 2. Last Name Validation
        if (!lastName.value.trim()) {
            lastName.classList.add('input-error');
            lastNameErr.classList.add('show');
            isValid = false;
        } else {
            lastName.classList.remove('input-error');
            lastNameErr.classList.remove('show');
        }

        // 3. Email Validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailPattern.test(email.value.trim())) {
            email.classList.add('input-error');
            emailErr.classList.add('show');
            isValid = false;
        } else {
            email.classList.remove('input-error');
            emailErr.classList.remove('show');
        }

        // 4. Gender Validation
        let genderSelected = false;
        genderRadios.forEach(function (radio) {
            if (radio.checked) genderSelected = true;
        });
        if (!genderSelected) {
            genderErr.classList.add('show');
            isValid = false;
        } else {
            genderErr.classList.remove('show');
        }

        // 5. Department Validation
        if (!department.value) {
            department.classList.add('input-error');
            departmentErr.classList.add('show');
            isValid = false;
        } else {
            department.classList.remove('input-error');
            departmentErr.classList.remove('show');
        }

        // 6. Mobile Number Validation (10 digits)
        const mobilePattern = /^\d{10}$/;
        if (!mobile.value.trim() || !mobilePattern.test(mobile.value.trim())) {
            mobile.classList.add('input-error');
            mobileErr.classList.add('show');
            isValid = false;
        } else {
            mobile.classList.remove('input-error');
            mobileErr.classList.remove('show');
        }

        // 7. Institute Validation
        if (!institute.value) {
            institute.classList.add('input-error');
            instituteErr.classList.add('show');
            isValid = false;
        } else {
            institute.classList.remove('input-error');
            instituteErr.classList.remove('show');
        }

        // 8. Academic Year Validation
        if (!academicYear.value) {
            academicYear.classList.add('input-error');
            academicYearErr.classList.add('show');
            isValid = false;
        } else {
            academicYear.classList.remove('input-error');
            academicYearErr.classList.remove('show');
        }

        // 9. Password Validation (min 6 chars)
        if (!password.value || password.value.length < 6) {
            password.classList.add('input-error');
            passwordErr.classList.add('show');
            isValid = false;
        } else {
            password.classList.remove('input-error');
            passwordErr.classList.remove('show');
        }

        // Final submission check
        if (isValid) {
            alert('Registration Successful! Please login with your credentials.');
            window.location.href = 'login.html';
        }
    });
});
