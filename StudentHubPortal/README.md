# Student Dashboard Hub

A simple Student Dashboard built using HTML5 and CSS3 for a college assignment.

## Project Structure

```
student-dashboard/
│
├── index.html          (Dashboard)
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
│   ├── style.css       (Base styles, theme tokens, forms, tables)
│   ├── layout.css      (Sidebar, navbar, grid layout)
│   └── pages.css       (Page-specific styles)
│
├── js/
│   └── theme.js        (Dark/Light mode switcher with localStorage persistence & tab sync)
│
├── images/
│   └── Profile.jpg
│
└── README.md
```

## Features

- **Persistent Dark & Light Mode**: Toggle theme from the navbar on any page or from Settings, with instant state persistence across all pages and open tabs via `localStorage`.
- 10 pages with consistent sidebar and navbar navigation
- Simple and clean modern design with smooth transitions
- Responsive layout using basic media queries
- Semantic HTML5 elements
- Placeholder data for all sections
- CSS separated into logical files

## Technologies Used

- HTML5
- CSS3 (Variables, Transitions, Flexbox)
- JavaScript (Theme Engine, Web Storage API, Event Listeners)