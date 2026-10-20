# Student Dashboard Hub

A simple Student Dashboard built using HTML5 and CSS3 for a college assignment.

## Project Structure

```
student-dashboard/
│
├── home.html           (Home / Landing Page with Login & Sign Up buttons)
├── login.html          (Student Login with demo fill & theme switcher)
├── signup.html         (Student Registration Page with full validation)
├── index.html          (Dashboard Overview)
├── profile.html        (Profile)
├── attendance.html     (Attendance)
├── courses.html        (Courses)
├── assignments.html    (Assignments)
├── exams.html          (Exams)
├── results.html        (Results)
├── timetable.html      (Timetable)
├── fees.html           (Fees)
├── settings.html       (Settings)
│
├── css/
│   ├── style.css       (Base styles, theme tokens, buttons, form validation, tables)
│   ├── layout.css      (Sidebar, navbar, grid layout)
│   └── pages.css       (Page-specific styles, home page, auth cards)
│
├── js/
│   ├── theme.js             (Dark/Light mode switcher with localStorage persistence & tab sync)
│   ├── signup-validation.js (Form validation for Sign Up)
│   └── components.js        (Dynamic UI components: slider, FAQ, modal, banner, hamburger)
│
├── images/
│   ├── Profile.jpg
│   ├── campus_library.jpg   (Education slider image)
│   ├── college_campus.jpg   (Education slider image)
│   └── college_classroom.jpg(Education slider image)
│
└── README.md
```

## Features

- **Home Page (`home.html`)**: Simple home page with portal overview, theme switcher, and direct **Login** and **Sign Up** buttons.
- **Dynamic UI Components (JavaScript)**:
  - **Image / Content Slider**: Interactive carousel on the home page showcasing educational campus infrastructure with next/prev buttons, indicators, and auto-play.
  - **Collapsible FAQ (Accordion)**: Interactive FAQ section where clicking questions expands/collapses answers.
  - **Modal Popup**: Notice dialog popup with circular details, backdrop click dismissal, and ESC key support.
  - **Notification Banner**: Dismissible announcement banner with close button.
  - **Hamburger Menu**: Mobile responsive navigation toggle for smaller screens.
  - **Light/Dark Theme Switcher**: Toggle theme on any page with persistence via `localStorage`.
- **Login Page (`login.html`)**: Simple student login form with input validation and theme switcher.
- **Sign Up Page (`signup.html`)**: Registration form asking for:
  - First Name
  - Last Name
  - Email ID
  - Gender (Radio options)
  - Department (Dropdown)
  - Mobile Number (10 digits)
  - Institute (Dropdown)
  - Academic Year (Dropdown)
  - Password
- **Form Validation (`js/signup-validation.js`)**: JavaScript validation for all required fields, valid email check, 10-digit mobile check, and password length.
- 10 student dashboard pages with consistent sidebar navigation.

## Technologies Used

- HTML5
- CSS3 (Variables, Flexbox)
- JavaScript (Theme Switcher, Dynamic Components, Form Validation)