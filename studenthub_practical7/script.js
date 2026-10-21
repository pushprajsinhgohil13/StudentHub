/* =========================================================
   STUDENTHUB - GLOBAL JAVASCRIPT
   ========================================================= */


/* =========================================================
   1. HAMBURGER MENU
   ========================================================= */

const hamburgerBtn =
    document.getElementById("hamburgerBtn");

const mainNav =
    document.getElementById("mainNav");


if (hamburgerBtn && mainNav) {

    hamburgerBtn.addEventListener(
        "click",
        function () {

            mainNav.classList.toggle("active");

        }
    );


    /*
        Close menu after clicking a link
    */

    const navLinks =
        mainNav.querySelectorAll("a");


    navLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                mainNav.classList.remove("active");

            }
        );

    });

}



/* =========================================================
   2. LIGHT / DARK THEME
   ========================================================= */

const themeToggle =
    document.getElementById("themeToggle");


/*
    Load saved theme
*/

const savedTheme =
    localStorage.getItem("studenthubTheme");


if (savedTheme === "dark") {

    document.body.classList.add("dark-mode");

}


function updateThemeButton() {

    if (!themeToggle) {

        return;

    }


    if (
        document.body.classList.contains(
            "dark-mode"
        )
    ) {

        themeToggle.textContent =
            "☀️ Light Mode";

    } else {

        themeToggle.textContent =
            "🌙 Dark Mode";

    }

}


updateThemeButton();



if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        function () {

            document.body.classList.toggle(
                "dark-mode"
            );


            const isDark =
                document.body.classList.contains(
                    "dark-mode"
                );


            if (isDark) {

                localStorage.setItem(
                    "studenthubTheme",
                    "dark"
                );

            } else {

                localStorage.setItem(
                    "studenthubTheme",
                    "light"
                );

            }


            updateThemeButton();

        }
    );

}



/* =========================================================
   3. NOTIFICATION BANNER
   ========================================================= */

const notification =
    document.getElementById("notification");


const notificationMessage =
    document.getElementById(
        "notificationMessage"
    );


const closeNotification =
    document.getElementById(
        "closeNotification"
    );


let notificationTimer;



function showNotification(
    message,
    type = "success"
) {

    if (
        !notification ||
        !notificationMessage
    ) {

        return;

    }


    /*
        Set message
    */

    notificationMessage.textContent =
        message;


    /*
        Remove old notification classes
    */

    notification.classList.remove(
        "success",
        "error",
        "warning"
    );


    /*
        Add notification type
    */

    notification.classList.add(type);


    /*
        Show notification
    */

    notification.classList.add("show");


    /*
        Clear previous timer
    */

    clearTimeout(
        notificationTimer
    );


    /*
        Automatically hide after 3 seconds
    */

    notificationTimer =
        setTimeout(
            function () {

                notification.classList.remove(
                    "show"
                );

            },
            3000
        );

}



if (closeNotification) {

    closeNotification.addEventListener(
        "click",
        function () {

            notification.classList.remove(
                "show"
            );

        }
    );

}



/* =========================================================
   4. CLOSE NOTIFICATION WITH ESC KEY
   ========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            notification
        ) {

            notification.classList.remove(
                "show"
            );

        }

    }
);



/* =========================================================
   5. ACTIVE NAVIGATION LINK
   ========================================================= */

const currentPage =
    window.location.pathname
        .split("/")
        .pop();


const allNavLinks =
    document.querySelectorAll(
        "#mainNav a"
    );


allNavLinks.forEach(function (link) {

    const linkPage =
        link
            .getAttribute("href")
            .split("/")
            .pop();


    if (
        linkPage === currentPage
    ) {

        link.classList.add(
            "active"
        );

    }

});



/* =========================================================
   6. GLOBAL MODAL SUPPORT
   ========================================================= */

const allModals =
    document.querySelectorAll(".modal");


allModals.forEach(function (modal) {


    /*
        Click outside modal
    */

    modal.addEventListener(
        "click",
        function (event) {

            if (
                event.target === modal
            ) {

                modal.classList.remove(
                    "show"
                );

            }

        }
    );


    /*
        Escape key
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {

                modal.classList.remove(
                    "show"
                );

            }

        }
    );

});



/* =========================================================
   7. FAQ COLLAPSIBLE
   ========================================================= */

/*
    Works with:

    <button class="faq-question">
        Question
    </button>

    <div class="faq-answer">
        Answer
    </div>
*/


const faqQuestions =
    document.querySelectorAll(
        ".faq-question"
    );


faqQuestions.forEach(function (question) {

    question.addEventListener(
        "click",
        function () {

            const answer =
                question.nextElementSibling;


            /*
                Close other FAQ answers
            */

            faqQuestions.forEach(
                function (otherQuestion) {

                    if (
                        otherQuestion !== question
                    ) {

                        const otherAnswer =
                            otherQuestion
                                .nextElementSibling;


                        if (otherAnswer) {

                            otherAnswer.style.display =
                                "none";

                        }


                        otherQuestion.classList.remove(
                            "active"
                        );

                    }

                }
            );


            /*
                Toggle current answer
            */

            if (
                answer.style.display ===
                "block"
            ) {

                answer.style.display =
                    "none";

                question.classList.remove(
                    "active"
                );

            } else {

                answer.style.display =
                    "block";

                question.classList.add(
                    "active"
                );

            }

        }
    );

});



/* =========================================================
   8. IMAGE / CONTENT SLIDER
   ========================================================= */

/*
    Expected structure:

    <div class="slider">

        <div class="slide">...</div>
        <div class="slide">...</div>
        <div class="slide">...</div>

        <button class="prev-slide">...</button>
        <button class="next-slide">...</button>

    </div>
*/


const slides =
    document.querySelectorAll(
        ".slide"
    );


const previousSlide =
    document.querySelector(
        ".prev-slide"
    );


const nextSlide =
    document.querySelector(
        ".next-slide"
    );


let currentSlide =
    0;



function displaySlide(index) {

    if (
        slides.length === 0
    ) {

        return;

    }


    /*
        Keep index inside range
    */

    if (
        index >= slides.length
    ) {

        currentSlide = 0;

    }


    if (
        index < 0
    ) {

        currentSlide =
            slides.length - 1;

    }


    /*
        Hide all slides
    */

    slides.forEach(function (slide) {

        slide.style.display =
            "none";

    });


    /*
        Show current slide
    */

    slides[currentSlide].style.display =
        "block";

}


if (
    slides.length > 0
) {

    displaySlide(
        currentSlide
    );


    if (nextSlide) {

        nextSlide.addEventListener(
            "click",
            function () {

                currentSlide++;

                displaySlide(
                    currentSlide
                );

            }
        );

    }


    if (previousSlide) {

        previousSlide.addEventListener(
            "click",
            function () {

                currentSlide--;

                displaySlide(
                    currentSlide
                );

            }
        );

    }


    /*
        Automatic slider
    */

    setInterval(
        function () {

            currentSlide++;

            displaySlide(
                currentSlide
            );

        },
        5000
    );

}



/* =========================================================
   9. GENERIC MODAL OPEN BUTTON
   ========================================================= */

/*
    Example:

    <button data-modal="myModal">
        Open
    </button>

    <div id="myModal" class="modal">
        ...
    </div>
*/


const modalOpenButtons =
    document.querySelectorAll(
        "[data-modal]"
    );


modalOpenButtons.forEach(
    function (button) {

        button.addEventListener(
            "click",
            function () {

                const modalId =
                    button.getAttribute(
                        "data-modal"
                    );


                const modal =
                    document.getElementById(
                        modalId
                    );


                if (modal) {

                    modal.classList.add(
                        "show"
                    );

                }

            }
        );

    }
);



/* =========================================================
   10. GENERIC MODAL CLOSE BUTTON
   ========================================================= */

const modalCloseButtons =
    document.querySelectorAll(
        ".modal-close"
    );


modalCloseButtons.forEach(
    function (button) {

        button.addEventListener(
            "click",
            function () {

                const modal =
                    button.closest(
                        ".modal"
                    );


                if (modal) {

                    modal.classList.remove(
                        "show"
                    );

                }

            }
        );

    }
);



/* =========================================================
   11. PAGE LOAD MESSAGE
   ========================================================= */

console.log(
    "StudentHub JavaScript loaded successfully."
);
/* =========================================
   EXAM ALERT POPUP
========================================= */

const examPopup =
    document.getElementById("examPopup");

const closeExamPopup =
    document.getElementById("closeExamPopup");

const examOkButton =
    document.getElementById("examOkButton");


/*
    Show popup after 2 seconds
*/

if (examPopup) {

    setTimeout(function () {

        examPopup.classList.add("show");

    }, 2000);

}


/*
    Close popup
*/

if (closeExamPopup) {

    closeExamPopup.addEventListener(
        "click",
        function () {

            examPopup.classList.remove("show");

        }
    );

}


/*
    Got it button
*/

if (examOkButton) {

    examOkButton.addEventListener(
        "click",
        function () {

            examPopup.classList.remove("show");

        }
    );

}


/*
    Close when clicking outside popup
*/

if (examPopup) {

    examPopup.addEventListener(
        "click",
        function (event) {

            if (event.target === examPopup) {

                examPopup.classList.remove("show");

            }

        }
    );

}
/* =========================================================
   PRACTICAL 5
   STUDENT REGISTRATION FORM VALIDATION
   ========================================================= */


/* =========================================
   GET REGISTRATION FORM
========================================= */

const registrationForm =
    document.getElementById("registrationForm");


/*
    Only execute this code if the
    registration page is open.
*/

if (registrationForm) {


    /* =====================================
       GET FORM ELEMENTS
    ===================================== */

    const studentName =
        document.getElementById("studentName");

    const studentEmail =
        document.getElementById("studentEmail");

    const studentMobile =
        document.getElementById("studentMobile");

    const studentPassword =
        document.getElementById("studentPassword");

    const confirmPassword =
        document.getElementById("confirmPassword");

    const course =
        document.getElementById("course");

    const year =
        document.getElementById("year");

    const terms =
        document.getElementById("terms");

    const successMessage =
        document.getElementById(
            "registrationSuccess"
        );


    /* =====================================
       REGULAR EXPRESSIONS
    ===================================== */

    /*
        Name:
        Letters and spaces only
    */

    const nameRegex =
        /^[A-Za-z ]{3,50}$/;


    /*
        Email:
        Basic valid email format
    */

    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    /*
        Indian mobile number:
        Starts from 6-9
        Total 10 digits
    */

    const mobileRegex =
        /^[6-9][0-9]{9}$/;


    /*
        Password:
        Minimum 8 characters
        At least:
        1 uppercase
        1 lowercase
        1 number
        1 special character
    */

    const passwordRegex =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;



    /* =====================================
       ERROR MESSAGE FUNCTION
    ===================================== */

    function showError(
        input,
        errorId,
        message
    ) {

        const errorElement =
            document.getElementById(errorId);


        input.classList.add(
            "input-error"
        );

        input.classList.remove(
            "input-success"
        );


        errorElement.textContent =
            message;

    }



    /* =====================================
       SUCCESS FIELD
    ===================================== */

    function showValid(
        input,
        errorId
    ) {

        const errorElement =
            document.getElementById(errorId);


        input.classList.remove(
            "input-error"
        );

        input.classList.add(
            "input-success"
        );


        errorElement.textContent =
            "";

    }



    /* =====================================
       NAME VALIDATION
    ===================================== */

    function validateName() {

        const name =
            studentName.value.trim();


        if (name === "") {

            showError(
                studentName,
                "nameError",
                "Please enter your full name."
            );

            return false;

        }


        if (!nameRegex.test(name)) {

            showError(
                studentName,
                "nameError",
                "Name must contain only letters and spaces."
            );

            return false;

        }


        showValid(
            studentName,
            "nameError"
        );

        return true;

    }



    /* =====================================
       EMAIL VALIDATION
    ===================================== */

    function validateEmail() {

        const email =
            studentEmail.value.trim();


        if (email === "") {

            showError(
                studentEmail,
                "emailError",
                "Please enter your email address."
            );

            return false;

        }


        if (!emailRegex.test(email)) {

            showError(
                studentEmail,
                "emailError",
                "Please enter a valid email address."
            );

            return false;

        }


        showValid(
            studentEmail,
            "emailError"
        );

        return true;

    }



    /* =====================================
       MOBILE VALIDATION
    ===================================== */

    function validateMobile() {

        const mobile =
            studentMobile.value.trim();


        if (mobile === "") {

            showError(
                studentMobile,
                "mobileError",
                "Please enter your mobile number."
            );

            return false;

        }


        if (!mobileRegex.test(mobile)) {

            showError(
                studentMobile,
                "mobileError",
                "Enter a valid 10-digit mobile number."
            );

            return false;

        }


        showValid(
            studentMobile,
            "mobileError"
        );

        return true;

    }



    /* =====================================
       PASSWORD VALIDATION
    ===================================== */

    function validatePassword() {

        const password =
            studentPassword.value;


        if (password === "") {

            showError(
                studentPassword,
                "passwordError",
                "Please enter a password."
            );

            return false;

        }


        if (!passwordRegex.test(password)) {

            showError(
                studentPassword,
                "passwordError",
                "Password must have 8+ characters, uppercase, lowercase, number and special character."
            );

            return false;

        }


        showValid(
            studentPassword,
            "passwordError"
        );

        return true;

    }



    /* =====================================
       CONFIRM PASSWORD
    ===================================== */

    function validateConfirmPassword() {

        const password =
            studentPassword.value;

        const confirm =
            confirmPassword.value;


        if (confirm === "") {

            showError(
                confirmPassword,
                "confirmPasswordError",
                "Please confirm your password."
            );

            return false;

        }


        if (password !== confirm) {

            showError(
                confirmPassword,
                "confirmPasswordError",
                "Passwords do not match."
            );

            return false;

        }


        showValid(
            confirmPassword,
            "confirmPasswordError"
        );

        return true;

    }



    /* =====================================
       COURSE
    ===================================== */

    function validateCourse() {

        if (course.value === "") {

            showError(
                course,
                "courseError",
                "Please select your course."
            );

            return false;

        }


        showValid(
            course,
            "courseError"
        );

        return true;

    }



    /* =====================================
       YEAR
    ===================================== */

    function validateYear() {

        if (year.value === "") {

            showError(
                year,
                "yearError",
                "Please select your year."
            );

            return false;

        }


        showValid(
            year,
            "yearError"
        );

        return true;

    }



    /* =====================================
       GENDER
    ===================================== */

    function validateGender() {

        const selectedGender =
            document.querySelector(
                'input[name="gender"]:checked'
            );


        const error =
            document.getElementById(
                "genderError"
            );


        if (!selectedGender) {

            error.textContent =
                "Please select your gender.";

            return false;

        }


        error.textContent =
            "";

        return true;

    }



    /* =====================================
       TERMS
    ===================================== */

    function validateTerms() {

        const error =
            document.getElementById(
                "termsError"
            );


        if (!terms.checked) {

            error.textContent =
                "You must accept the Terms & Conditions.";

            return false;

        }


        error.textContent =
            "";

        return true;

    }



    /* =====================================
       FORM SUBMIT
    ===================================== */

    registrationForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            /*
                Validate every field
            */

            const nameValid =
                validateName();

            const emailValid =
                validateEmail();

            const mobileValid =
                validateMobile();

            const passwordValid =
                validatePassword();

            const confirmValid =
                validateConfirmPassword();

            const courseValid =
                validateCourse();

            const yearValid =
                validateYear();

            const genderValid =
                validateGender();

            const termsValid =
                validateTerms();


            /*
                Check final result
            */

            if (
                nameValid &&
                emailValid &&
                mobileValid &&
                passwordValid &&
                confirmValid &&
                courseValid &&
                yearValid &&
                genderValid &&
                termsValid
            ) {


                /*
                    Success
                */

                successMessage.textContent =
                    "Registration successful! Your StudentHub account has been created.";

                successMessage.classList.add(
                    "show"
                );


                /*
                    Notification if available
                */

                if (
                    typeof showNotification ===
                    "function"
                ) {

                    showNotification(
                        "Registration successful!",
                        "success"
                    );

                }


                /*
                    Form reset after successful
                    registration
                */

                setTimeout(
                    function () {

                        registrationForm.reset();

                        document
                            .querySelectorAll(
                                ".input-success"
                            )
                            .forEach(
                                function (input) {

                                    input.classList.remove(
                                        "input-success"
                                    );

                                }
                            );

                    },
                    1500
                );


            } else {

                successMessage.textContent =
                    "Please correct the errors highlighted above.";

                successMessage.classList.add(
                    "show"
                );

            }

        }
    );



    /* =====================================
       REAL-TIME VALIDATION
    ===================================== */

    studentName.addEventListener(
        "blur",
        validateName
    );


    studentEmail.addEventListener(
        "blur",
        validateEmail
    );


    studentMobile.addEventListener(
        "blur",
        validateMobile
    );


    studentPassword.addEventListener(
        "blur",
        validatePassword
    );


    confirmPassword.addEventListener(
        "blur",
        validateConfirmPassword
    );


    course.addEventListener(
        "change",
        validateCourse
    );


    year.addEventListener(
        "change",
        validateYear
    );


    terms.addEventListener(
        "change",
        validateTerms
    );



    /* =====================================
       RESET
    ===================================== */

    registrationForm.addEventListener(
        "reset",
        function () {

            setTimeout(
                function () {

                    document
                        .querySelectorAll(
                            ".input-error, .input-success"
                        )
                        .forEach(
                            function (input) {

                                input.classList.remove(
                                    "input-error",
                                    "input-success"
                                );

                            }
                        );


                    document
                        .querySelectorAll(
                            ".form-error"
                        )
                        .forEach(
                            function (error) {

                                error.textContent =
                                    "";

                            }
                        );


                    successMessage.classList.remove(
                        "show"
                    );


                },
                0
            );

        }
    );

}
/* =========================================================
   PRACTICAL 6
   FETCH API + DYNAMIC EVENT RENDERING
   SEARCH + CATEGORY FILTER + SORT + PAGINATION
   EVENT REGISTRATION
   ========================================================= */

(function () {

    /* =====================================================
       CHECK WHETHER EVENTS PAGE IS OPEN
       ===================================================== */

    const eventGrid =
        document.getElementById("eventGrid");

    if (!eventGrid) {
        return;
    }


    /* =====================================================
       GET HTML ELEMENTS
       ===================================================== */

    const eventSearch =
        document.getElementById("eventSearch");

    const eventCategory =
        document.getElementById("eventCategory");

    const eventSort =
        document.getElementById("eventSort");

    const noEvents =
        document.getElementById("noEvents");

    const loadingEvents =
        document.getElementById("loadingEvents");

    let pagination =
        document.getElementById("pagination");

    const registeredEventsTable =
        document.getElementById("registeredEvents");

    const noRegisteredEvents =
        document.getElementById("noRegisteredEvents");

    const eventModal =
        document.getElementById("eventModal");

    const eventModalClose =
        document.getElementById("eventModalClose");

    const eventModalDone =
        document.getElementById("eventModalDone");

    const eventModalText =
        document.getElementById("eventModalText");


    /* =====================================================
       VARIABLES
       ===================================================== */

    let allEvents = [];

    let filteredEvents = [];

    let currentPage = 1;

    const eventsPerPage = 2;


    /* =====================================================
       PAGINATION ELEMENT
       ===================================================== */

    if (!pagination) {

        pagination =
            document.createElement("div");

        pagination.id =
            "pagination";

        pagination.style.textAlign =
            "center";

        pagination.style.margin =
            "20px";

        eventGrid.parentNode.appendChild(
            pagination
        );
    }


    /* =====================================================
       FETCH EVENTS.JSON
       ===================================================== */

    function loadEvents() {

        if (loadingEvents) {

            loadingEvents.style.display =
                "block";
        }


        fetch("events.json")

            .then(function (response) {

                if (!response.ok) {

                    throw new Error(
                        "Unable to load events.json"
                    );
                }

                return response.json();
            })


            .then(function (data) {

                console.log(
                    "Events JSON Data:"
                );

                console.log(data);


                /* -----------------------------------------
                   CHECK JSON FORMAT
                   ----------------------------------------- */

                if (!Array.isArray(data)) {

                    throw new Error(
                        "events.json must contain an array"
                    );
                }


                /* -----------------------------------------
                   STORE DATA
                   ----------------------------------------- */

                allEvents =
                    data;

                filteredEvents =
                    data.slice();


                currentPage =
                    1;


                if (loadingEvents) {

                    loadingEvents.style.display =
                        "none";
                }


                renderEvents();

            })


            .catch(function (error) {

                console.error(
                    "Error loading events.json:",
                    error
                );


                if (loadingEvents) {

                    loadingEvents.style.display =
                        "none";
                }


                eventGrid.innerHTML =
                    "<p style='text-align:center; color:red;'>" +
                    "Unable to load events. " +
                    "Please check events.json and Live Server." +
                    "</p>";


                if (noEvents) {

                    noEvents.style.display =
                        "none";
                }


                if (pagination) {

                    pagination.innerHTML =
                        "";
                }

            });

    }


    /* =====================================================
       RENDER EVENTS
       ===================================================== */

    function renderEvents() {

        eventGrid.innerHTML =
            "";


        /* -----------------------------------------
           NO EVENTS
           ----------------------------------------- */

        if (filteredEvents.length === 0) {

            if (noEvents) {

                noEvents.style.display =
                    "block";
            }


            if (pagination) {

                pagination.innerHTML =
                    "";
            }

            return;
        }


        if (noEvents) {

            noEvents.style.display =
                "none";
        }


        /* -----------------------------------------
           CALCULATE PAGINATION
           ----------------------------------------- */

        const startIndex =
            (currentPage - 1) *
            eventsPerPage;


        const endIndex =
            startIndex +
            eventsPerPage;


        const pageEvents =
            filteredEvents.slice(
                startIndex,
                endIndex
            );


        /* -----------------------------------------
           CREATE EVENT CARDS
           ----------------------------------------- */

        pageEvents.forEach(function (event) {

            const card =
                document.createElement("div");


            card.className =
                "card event-card";


            card.setAttribute(
                "data-category",
                event.category
            );


            /* -----------------------------------------
               CREATE EVENT CONTENT
               ----------------------------------------- */

            card.innerHTML = `

                <h3>
                    ${event.title}
                </h3>

                <p>
                    ${event.description}
                </p>

                <p>
                    <strong>Date:</strong>
                    ${event.date}
                </p>

                <p>
                    <strong>Time:</strong>
                    ${event.time}
                </p>

                <p>
                    <strong>Venue:</strong>
                    ${event.venue}
                </p>

                <p>
                    <strong>Category:</strong>
                    ${event.category}
                </p>

                <button
                    class="register-btn"
                    type="button"
                >
                    Register
                </button>

            `;


            /* -----------------------------------------
               REGISTER BUTTON
               ----------------------------------------- */

            const registerButton =
                card.querySelector(
                    ".register-btn"
                );


            registerButton.addEventListener(
                "click",
                function () {

                    registerForEvent(event);

                }
            );


            eventGrid.appendChild(
                card
            );

        });


        /* -----------------------------------------
           CREATE PAGINATION
           ----------------------------------------- */

        createPagination();

    }


    /* =====================================================
       SEARCH + CATEGORY + SORT
       ===================================================== */

    function applyFilters() {

        const searchText =
            eventSearch
                ? eventSearch.value
                    .toLowerCase()
                    .trim()
                : "";


        const selectedCategory =
            eventCategory
                ? eventCategory.value
                : "all";


        const selectedSort =
            eventSort
                ? eventSort.value
                : "default";


        /* -----------------------------------------
           SEARCH + CATEGORY FILTER
           ----------------------------------------- */

        filteredEvents =
            allEvents.filter(
                function (event) {

                    const title =
                        String(
                            event.title || ""
                        ).toLowerCase();


                    const description =
                        String(
                            event.description || ""
                        ).toLowerCase();


                    const matchesSearch =
                        title.includes(
                            searchText
                        ) ||
                        description.includes(
                            searchText
                        );


                    const matchesCategory =
                        selectedCategory ===
                        "all" ||
                        event.category ===
                        selectedCategory;


                    return (
                        matchesSearch &&
                        matchesCategory
                    );

                }
            );


        /* -----------------------------------------
           SORT BY DATE
           ----------------------------------------- */

        if (
            selectedSort ===
            "dateAsc"
        ) {

            filteredEvents.sort(
                function (a, b) {

                    return (
                        new Date(a.date) -
                        new Date(b.date)
                    );

                }
            );

        }


        /* -----------------------------------------
           SORT BY DATE DESCENDING
           ----------------------------------------- */

        else if (
            selectedSort ===
            "dateDesc"
        ) {

            filteredEvents.sort(
                function (a, b) {

                    return (
                        new Date(b.date) -
                        new Date(a.date)
                    );

                }
            );

        }


        /* -----------------------------------------
           RESET PAGE
           ----------------------------------------- */

        currentPage =
            1;


        renderEvents();

    }


    /* =====================================================
       SEARCH EVENT
       ===================================================== */

    if (eventSearch) {

        eventSearch.addEventListener(
            "input",
            function () {

                applyFilters();

            }
        );

    }


    /* =====================================================
       CATEGORY FILTER
       ===================================================== */

    if (eventCategory) {

        eventCategory.addEventListener(
            "change",
            function () {

                applyFilters();

            }
        );

    }


    /* =====================================================
       SORT EVENTS
       ===================================================== */

    if (eventSort) {

        eventSort.addEventListener(
            "change",
            function () {

                applyFilters();

            }
        );

    }


    /* =====================================================
       PAGINATION
       ===================================================== */

    function createPagination() {

        if (!pagination) {
            return;
        }


        pagination.innerHTML =
            "";


        const totalPages =
            Math.ceil(
                filteredEvents.length /
                eventsPerPage
            );


        /* -----------------------------------------
           ONLY ONE PAGE
           ----------------------------------------- */

        if (totalPages <= 1) {
            return;
        }


        /* -----------------------------------------
           PREVIOUS BUTTON
           ----------------------------------------- */

        const previousButton =
            document.createElement(
                "button"
            );


        previousButton.textContent =
            "Previous";


        previousButton.type =
            "button";


        previousButton.disabled =
            currentPage === 1;


        previousButton.addEventListener(
            "click",
            function () {

                if (currentPage > 1) {

                    currentPage--;

                    renderEvents();

                }

            }
        );


        pagination.appendChild(
            previousButton
        );


        /* -----------------------------------------
           PAGE NUMBER BUTTONS
           ----------------------------------------- */

        for (
            let page = 1;
            page <= totalPages;
            page++
        ) {

            const pageButton =
                document.createElement(
                    "button"
                );


            pageButton.textContent =
                page;


            pageButton.type =
                "button";


            pageButton.style.margin =
                "0 5px";


            if (
                page === currentPage
            ) {

                pageButton.style.fontWeight =
                    "bold";

            }


            pageButton.addEventListener(
                "click",
                function () {

                    currentPage =
                        page;

                    renderEvents();

                }
            );


            pagination.appendChild(
                pageButton
            );

        }


        /* -----------------------------------------
           NEXT BUTTON
           ----------------------------------------- */

        const nextButton =
            document.createElement(
                "button"
            );


        nextButton.textContent =
            "Next";


        nextButton.type =
            "button";


        nextButton.disabled =
            currentPage ===
            totalPages;


        nextButton.addEventListener(
            "click",
            function () {

                if (
                    currentPage <
                    totalPages
                ) {

                    currentPage++;

                    renderEvents();

                }

            }
        );


        pagination.appendChild(
            nextButton
        );

    }


    /* =====================================================
       GET REGISTERED EVENTS
       ===================================================== */

    function getRegisteredEvents() {

        const stored =
            localStorage.getItem(
                "studentHubRegisteredEvents"
            );


        if (!stored) {

            return [];

        }


        try {

            const data =
                JSON.parse(stored);


            if (Array.isArray(data)) {

                return data;

            }

        }

        catch (error) {

            console.error(
                "Error reading registered events:",
                error
            );

        }


        return [];

    }


    /* =====================================================
       SAVE REGISTERED EVENTS
       ===================================================== */

    function saveRegisteredEvents(
        events
    ) {

        localStorage.setItem(
            "studentHubRegisteredEvents",
            JSON.stringify(events)
        );

    }


    /* =====================================================
       REGISTER EVENT
       ===================================================== */

    function registerForEvent(event) {

        const registeredEvents =
            getRegisteredEvents();


        /* -----------------------------------------
           CHECK DUPLICATE REGISTRATION
           ----------------------------------------- */

        const alreadyRegistered =
            registeredEvents.some(
                function (item) {

                    return (
                        item.title ===
                        event.title
                    );

                }
            );


        if (alreadyRegistered) {

            showEventModal(
                "You are already registered for " +
                event.title
            );

            return;

        }


        /* -----------------------------------------
           ADD EVENT
           ----------------------------------------- */

        registeredEvents.push({

            title:
                event.title,

            date:
                event.date,

            time:
                event.time,

            venue:
                event.venue,

            category:
                event.category,

            status:
                "Registered"

        });


        /* -----------------------------------------
           SAVE
           ----------------------------------------- */

        saveRegisteredEvents(
            registeredEvents
        );


        /* -----------------------------------------
           UPDATE TABLE
           ----------------------------------------- */

        displayRegisteredEvents();


        /* -----------------------------------------
           SHOW MODAL
           ----------------------------------------- */

        showEventModal(
            "Successfully registered for " +
            event.title
        );

    }


    /* =====================================================
       DISPLAY REGISTERED EVENTS
       ===================================================== */

    function displayRegisteredEvents() {

        if (!registeredEventsTable) {
            return;
        }


        /* -----------------------------------------
           REMOVE OLD ROWS
           ----------------------------------------- */

        const rows =
            registeredEventsTable.querySelectorAll(
                "tr[data-registered-event]"
            );


        rows.forEach(
            function (row) {

                row.remove();

            }
        );


        const registeredEvents =
            getRegisteredEvents();


        /* -----------------------------------------
           NO REGISTERED EVENTS
           ----------------------------------------- */

        if (
            registeredEvents.length ===
            0
        ) {

            if (noRegisteredEvents) {

                noRegisteredEvents.style.display =
                    "table-row";

            }

            return;

        }


        if (noRegisteredEvents) {

            noRegisteredEvents.style.display =
                "none";

        }


        /* -----------------------------------------
           CREATE TABLE ROWS
           ----------------------------------------- */

        registeredEvents.forEach(
            function (event, index) {

                const row =
                    document.createElement(
                        "tr"
                    );


                row.setAttribute(
                    "data-registered-event",
                    "true"
                );


                row.innerHTML = `

                    <td>
                        ${event.title}
                    </td>

                    <td>
                        ${event.status}
                    </td>

                    <td>

                        <button
                            type="button"
                            class="cancel-registration"
                        >
                            Cancel
                        </button>

                    </td>

                `;


                /* -----------------------------------------
                   CANCEL REGISTRATION
                   ----------------------------------------- */

                const cancelButton =
                    row.querySelector(
                        ".cancel-registration"
                    );


                cancelButton.addEventListener(
                    "click",
                    function () {

                        cancelRegistration(
                            index
                        );

                    }
                );


                registeredEventsTable.appendChild(
                    row
                );

            }
        );

    }


    /* =====================================================
       CANCEL REGISTRATION
       ===================================================== */

    function cancelRegistration(
        index
    ) {

        const registeredEvents =
            getRegisteredEvents();


        if (
            index < 0 ||
            index >=
            registeredEvents.length
        ) {

            return;

        }


        const eventName =
            registeredEvents[index].title;


        registeredEvents.splice(
            index,
            1
        );


        saveRegisteredEvents(
            registeredEvents
        );


        displayRegisteredEvents();


        showEventModal(
            "Registration cancelled for " +
            eventName
        );

    }


    /* =====================================================
       EVENT MODAL
       ===================================================== */

    function showEventModal(
        message
    ) {

        if (!eventModal) {
            return;
        }


        if (eventModalText) {

            eventModalText.textContent =
                message;

        }


        eventModal.classList.add(
            "show"
        );

    }


    /* =====================================================
       CLOSE MODAL
       ===================================================== */

    function closeEventModal() {

        if (!eventModal) {
            return;
        }


        eventModal.classList.remove(
            "show"
        );

    }


    if (eventModalClose) {

        eventModalClose.addEventListener(
            "click",
            function () {

                closeEventModal();

            }
        );

    }


    if (eventModalDone) {

        eventModalDone.addEventListener(
            "click",
            function () {

                closeEventModal();

            }
        );

    }


    /* -----------------------------------------
       CLOSE MODAL BY CLICKING OUTSIDE
       ----------------------------------------- */

    if (eventModal) {

        eventModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    eventModal
                ) {

                    closeEventModal();

                }

            }
        );

    }


    /* =====================================================
       INITIAL LOAD
       ===================================================== */

    loadEvents();

    displayRegisteredEvents();


})();