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
        function(event) {

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
                    function() {

                        registrationForm.reset();

                        document
                            .querySelectorAll(
                                ".input-success"
                            )
                            .forEach(
                                function(input) {

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
        function() {

            setTimeout(
                function() {

                    document
                        .querySelectorAll(
                            ".input-error, .input-success"
                        )
                        .forEach(
                            function(input) {

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
                            function(error) {

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