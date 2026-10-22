
"use strict";

document.addEventListener("DOMContentLoaded", function () {
    // Load all four JSON files.
    async function loadJSONData() {
        try {
            const [events, notices, faqs] = await Promise.all([
                
                fetch("data/events.json"),
                fetch("data/notices.json"),
                fetch("data/faqs.json")
            ]);

            // Detect missing files or incorrect paths.
            if ( !events.ok ||
                !notices.ok || !faqs.ok) {
                throw new Error(
                    "Could not load one or more JSON files."
                );
            }

         
            const eventData = await events.json();
            const noticeData = await notices.json();
            const faqData = await faqs.json();

            // Render JSON data on the homepage.
            
            renderEvents(eventData);
            renderNotices(noticeData);
            renderFAQs(faqData);

        } catch (error) {
            console.error("JSON loading error:", error);
            showLoadingError();
        }
    }

    // Safely create an element containing text.
    function addText(parent, tag, text, className) {
        const element = document.createElement(tag);
        element.textContent = text || "";

        if (className) {
            element.className = className;
        }

        parent.appendChild(element);
        return element;
    }

    
    // 2. Display campus events.
    
    function renderEvents(events) {
        const container = document.getElementById("events-list");

        if (!container) return;

        container.replaceChildren();

        // Get today's date in YYYY-MM-DD format using local time.
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");
        const today = `${year}-${month}-${day}`;

        // Keep only events happening today or in the future.
        // Sort them from the earliest date to the latest.
        const upcomingEvents = events
            .filter(function (event) {
                return event.date && event.date >= today;
            })
            .sort(function (a, b) {
                return a.date.localeCompare(b.date);
            });

        if (upcomingEvents.length === 0) {
            const message = document.createElement("p");
            message.textContent = "There are no upcoming events.";
            container.appendChild(message);
            return;
        }

        upcomingEvents.forEach(function (event) {
            const card = document.createElement("article");
            card.className = "card event-json-card";

            addText(card, "h3", `${event.icon || "📅"} ${event.title}`);
            addText(card, "p", `Category: ${event.category || "N/A"}`);
            addText(card, "p", `Date: ${event.date}`);
            addText(card, "p", `Time: ${event.time || "N/A"}`);
            addText(card, "p", `Venue: ${event.venue || "N/A"}`);
            addText(card, "p", `Organizer: ${event.organizer || "N/A"}`);
            addText(card, "p", `Fee: ${event.fee || "N/A"}`);
            addText(card, "p", event.description || "");

            container.appendChild(card);
        });
    }
    

    // 3. Display the latest notice in the banner and modal.
    function renderNotices(notices) {
        const bannerText =
            document.getElementById("notice-banner-text");
        const modalTitle =
            document.getElementById("modal-title");
        const modalBody =
            document.getElementById("notice-modal-body");

        if (!notices.length) {
            if (bannerText) bannerText.textContent = "No notices available.";
            if (modalBody) modalBody.textContent = "No notices available.";
            return;
        }

        // Select the first notice in the JSON file.
        const notice = notices[0];

        if (bannerText) {
            bannerText.textContent =
                notice.priority + ": " + notice.title;
        }

        if (modalTitle) {
            modalTitle.textContent = notice.title;
        }

        if (modalBody) {
            modalBody.replaceChildren();

            addText(modalBody, "p",
                "Notice Number: " + notice.noticeNo);
            addText(modalBody, "p",
                "Category: " + notice.category);
            addText(modalBody, "p",
                "Priority: " + notice.priority);
            addText(modalBody, "p",
                "Date: " + notice.date);
            addText(modalBody, "p",
                "Department: " + notice.department);
            addText(modalBody, "p",
                "Issued by: " + notice.issuedBy);
            addText(modalBody, "p", notice.summary);
            addText(modalBody, "p", notice.content);
        }
    }

    // 4. Display FAQs from faqs.json.
    function renderFAQs(faqs) {
        const container = document.getElementById("faq-list");
        if (!container) return;

        container.replaceChildren();

        faqs.forEach(function (faq) {
            const item = document.createElement("div");
            item.className = "faq-item";

            const question = document.createElement("button");
            question.type = "button";
            question.className = "faq-question";
            question.setAttribute("aria-expanded", "false");

            addText(question, "span", faq.question);
            addText(question, "span", "+", "faq-icon");

            const answer = document.createElement("div");
            answer.className = "faq-answer";

            addText(answer, "p", faq.answer);

            item.appendChild(question);
            item.appendChild(answer);
            container.appendChild(item);

            // Keep the existing accordion style and behavior.
            question.addEventListener("click", function () {
                const isOpen = item.classList.contains("active");

                container.querySelectorAll(".faq-item").forEach(
                    function (otherItem) {
                        otherItem.classList.remove("active");

                        const otherButton =
                            otherItem.querySelector(".faq-question");

                        otherButton.setAttribute(
                            "aria-expanded", "false"
                        );

                        otherItem.querySelector(".faq-icon")
                            .textContent = "+";
                    }
                );

                if (!isOpen) {
                    item.classList.add("active");
                    question.setAttribute("aria-expanded", "true");
                    question.querySelector(".faq-icon")
                        .textContent = "−";
                }
            });
        });
    }

    function showLoadingError() {
        [
            "students-list",
            "events-list",
            "faq-list"
        ].forEach(function (id) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent =
                    "Unable to load data. Check the JSON file paths.";
            }
        });

        const banner = document.getElementById("notice-banner-text");
        if (banner) {
            banner.textContent = "Unable to load notices.";
        }

        const body = document.getElementById("notice-modal-body");
        if (body) {
            body.textContent =
                "Notice details could not be loaded. Please check the JSON files.";
        }
    }

    // Start loading data.
    loadJSONData();
});
