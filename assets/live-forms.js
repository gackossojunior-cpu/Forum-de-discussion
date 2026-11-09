document.querySelectorAll('form[data-async-publish]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;

        const submit = event.submitter || form.querySelector('[type="submit"]');
        const status = form.querySelector('[data-form-status]');
        const body = new FormData(form);
        const submitName = submit?.getAttribute('name');
        if (submitName) body.set(submitName, submit.value || '1');

        if (submit) submit.disabled = true;
        if (status) {
            status.hidden = true;
            status.replaceChildren();
        }

        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'La publication a échoué.');

            if (form.dataset.asyncPublish === 'reply' && result.reply) {
                window.ForumRealtime?.addReply(result.reply);
            }
            form.reset();
            showStatus(status, result, form.dataset.asyncPublish);
        } catch (error) {
            showStatus(status, { error: error.message }, form.dataset.asyncPublish, true);
        } finally {
            if (submit) submit.disabled = false;
        }
    });
});

function showStatus(status, result, type, failed = false) {
    if (!status) return;
    status.hidden = false;
    status.className = `alert ${failed ? 'alert-danger' : 'alert-success'}`;
    status.setAttribute('role', failed ? 'alert' : 'status');
    status.replaceChildren();

    if (failed) {
        status.textContent = result.error;
        return;
    }

    if (type === 'question' && result.question?.url) {
        status.append(document.createTextNode('Votre question est publiée. '));
        const link = document.createElement('a');
        link.href = result.question.url;
        link.textContent = 'Voir la question';
        status.append(link);
    } else {
        status.textContent = 'Votre réponse est publiée.';
    }
}
