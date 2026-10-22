
"use strict";

document.addEventListener("DOMContentLoaded", async function () {
    const profileName = document.getElementById("profile-name");

    // Do nothing if this page doesn't contain the profile table.
    if (!profileName) return;

    // Keep this function outside try/catch so both can use it.
    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent =
                value !== undefined && value !== null && value !== ""
                    ? value
                    : "Not available";
        }
    }

    async function loadStudentJSON() {
        const paths = [
            "JSON/Student.json",
            "data/students.json"
        ];

        let lastError;

        for (const path of paths) {
            try {
                const response = await fetch(path);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${path}`);
                }

                return await response.json();
            } catch (error) {
                lastError = error;
                console.warn("Could not load student file:", path);
            }
        }

        throw lastError || new Error("Student JSON could not be loaded.");
    }

    try {
        const data = await loadStudentJSON();

        const students = Array.isArray(data) ? data : data.students;

        if (!Array.isArray(students) || students.length === 0) {
            throw new Error("The student JSON contains no student records.");
        }

        // Use the first student only.
        const student = students[0];

        setText("profile-name", student.name);
        setText("profile-roll", student.rollNo);
        setText(
            "profile-program",
            student.department ? "B.Tech " + student.department : ""
        );

        setText(
            "profile-department",
            student.department
                ? student.department +
                    (student.departmentCode
                        ? " (" + student.departmentCode + ")"
                        : "")
                : ""
        );

        setText(
            "profile-semester",
            student.semester !== undefined
                ? student.semester + " (Odd Term)"
                : ""
        );

        setText("profile-division", student.division);
        setText("profile-batch", student.batch);
        setText("profile-email", student.email);
        setText("profile-phone", student.phone);

        // Keep the existing academic-year, institute and university values.
        const profileImage = document.getElementById("profile-photo");

        if (profileImage && student.avatar) {
            profileImage.src = student.avatar;
            profileImage.alt = "Profile photo of " + (student.name || "student");
        }

        console.log("First student profile loaded successfully:", student.name);
    } catch (error) {
        console.error("Profile data loading error:", error);
        setText("profile-name", "Unable to load student data");
    }
});
