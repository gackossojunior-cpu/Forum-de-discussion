(() => {
    const config = window.FORUM_REALTIME;
    if (!config) return;

    const socketUrl = config.url || `${window.location.protocol}//${window.location.hostname}:3001`;
    const socketScript = document.createElement('script');
    socketScript.src = `${socketUrl}/socket.io/socket.io.js`;
    socketScript.async = true;
    window.ForumSocketReady = new Promise((resolve) => {
        socketScript.onerror = () => {
            console.error('Impossible de charger le client Socket.IO.');
            resolve(null);
        };
        socketScript.onload = () => {
            if (typeof window.io !== 'function') {
                resolve(null);
                return;
            }
            const socket = window.io(socketUrl, {
                transports: ['websocket', 'polling'],
                auth: config.auth ? { user: config.auth } : {},
            });
            window.ForumRealtime = { addReply };

            if (config.roomId) socket.emit('question:join', config.roomId);
            socket.on('question:new', addQuestion);
            socket.on('reply:new', addReply);
            socket.on('reply:created', ({ questionId }) => updateReplyCount(questionId));
            socket.on('notification:new', receiveNotification);
            socket.on('notification:read', markNotificationsRead);
            resolve(socket);
        };
    });
    document.head.append(socketScript);

    const soundButton = document.querySelector('[data-sound-toggle]');
    const soundStorageKey = 'forum-notification-sound';
    let soundEnabled = readSoundPreference();
    let audioContext = null;
    updateSoundButton();

    document.addEventListener('pointerdown', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });
    soundButton?.addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        try {
            localStorage.setItem(soundStorageKey, soundEnabled ? 'on' : 'off');
        } catch {
            // The preference remains active for the current page.
        }
        updateSoundButton();
        if (soundEnabled) {
            unlockAudio();
            playNotificationSound();
        }
    });

    document.querySelector('[data-mark-all-read-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const submit = form.querySelector('[type="submit"]');
        if (submit) submit.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'Impossible de marquer les notifications comme lues.');

            document.querySelectorAll('.notification-item.is-unread').forEach((item) => {
                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-badge')?.remove();
            });
            updateNotificationBadge(0);
            form.hidden = true;
            showNotificationFeedback('Toutes les notifications sont marquées comme lues.');
        } catch (error) {
            showNotificationFeedback(error.message, true);
        } finally {
            if (submit) submit.disabled = false;
        }
    });

    function addQuestion(question) {
        const container = document.querySelector('.forum-home');
        if (!container || document.querySelector(`[data-question-id="${question.id}"]`)) return;

        const search = (container.dataset.search || '').trim().toLocaleLowerCase();
        const categoryId = Number(container.dataset.category || 0);
        if (search && !`${question.title} ${question.content}`.toLocaleLowerCase().includes(search)) return;
        if (categoryId && categoryId !== Number(question.categoryId)) return;

        let list = document.querySelector('#question-list');
        if (!list) {
            list = document.createElement('div');
            list.id = 'question-list';
            container.append(list);
        }
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        const card = document.createElement('article');
        card.className = 'question-card';
        card.dataset.questionId = question.id;

        const header = document.createElement('div');
        header.className = 'question-header';
        const heading = document.createElement('div');
        const title = document.createElement('h3');
        title.className = 'question-title';
        const titleLink = document.createElement('a');
        titleLink.href = question.url;
        titleLink.textContent = question.title;
        title.append(titleLink);

        const meta = document.createElement('div');
        meta.className = 'question-meta';
        meta.append(
            createMeta('fa-user-circle', question.author, `user-profile.php?id=${question.userId}`),
            createMeta('fa-calendar', question.dateLabel),
            createMeta('fa-comments', '0 réponse', null, `question-detail.php?id=${question.id}`)
        );
        heading.append(title, meta);

        if (question.category) {
            const badge = document.createElement('span');
            badge.className = 'category-badge';
            badge.textContent = question.category;
            header.append(heading, badge);
        } else {
            header.append(heading);
        }

        const content = document.createElement('p');
        content.className = 'question-content';
        const characters = Array.from(question.content);
        content.textContent = characters.slice(0, 200).join('') + (characters.length > 200 ? '...' : '');

        const actions = document.createElement('div');
        actions.className = 'question-actions';
        const view = document.createElement('a');
        view.href = question.url;
        view.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i> ';
        view.append(document.createTextNode('Voir la question'));
        actions.append(view);

        card.append(header, content, actions);
        list.prepend(card);
        updateQuestionCount(1);
    }

    function addReply(reply) {
        const list = document.querySelector('#replies-list');
        if (!list || Number(list.dataset.questionId) !== Number(reply.questionId)) return;
        if (document.querySelector(`[data-reply-id="${reply.id}"]`)) return;

        document.querySelector('#replies-empty-state')?.remove();

        const card = document.createElement('article');
        card.className = 'response-card';
        card.dataset.replyId = reply.id;

        const meta = document.createElement('div');
        meta.className = 'response-meta';
        const author = document.createElement('strong');
        const authorLink = document.createElement('a');
        authorLink.href = reply.profileUrl;
        authorLink.textContent = reply.author;
        author.append(authorLink);
        const date = document.createElement('span');
        date.textContent = reply.dateLabel;
        meta.append(author, date);

        const content = document.createElement('div');
        content.className = 'response-content';
        content.textContent = reply.content;
        content.style.whiteSpace = 'pre-line';

        const votes = document.createElement('div');
        votes.className = 'response-live-votes';
        votes.textContent = 'J’aime 0 · Je n’aime pas 0';
        card.append(meta, content, votes);
        list.prepend(card);

        const count = document.querySelector('[data-reply-count]');
        if (count) count.textContent = String(Number(count.textContent || 0) + 1);
    }

    function createMeta(iconName, text, href = null, replyCountUrl = null) {
        const item = document.createElement('span');
        item.className = 'meta-item';
        const icon = document.createElement('i');
        icon.className = `fas ${iconName}`;
        icon.setAttribute('aria-hidden', 'true');
        item.append(icon);

        const label = href ? document.createElement('a') : document.createElement('span');
        if (href) label.href = href;
        if (replyCountUrl) {
            label.dataset.replyCountFor = replyCountUrl.split('=').pop();
            label.textContent = text;
        } else {
            label.textContent = text;
        }
        item.append(label);
        return item;
    }

    function updateReplyCount(questionId) {
        const id = Number(questionId);
        document.querySelectorAll(`[data-question-id="${id}"] [data-reply-count-for="${id}"]`).forEach((label) => {
            const count = Number(label.dataset.count || 0) + 1;
            label.dataset.count = String(count);
            label.textContent = `${count} réponse${count > 1 ? 's' : ''}`;
        });
    }

    function updateQuestionCount(change) {
        const count = document.querySelector('.result-count');
        if (!count) return;
        const total = Number.parseInt(count.textContent, 10) + change;
        count.textContent = `${total} sujet${total > 1 ? 's' : ''}`;
    }

    function receiveNotification(notification) {
        updateNotificationBadge(1);
        addNotificationToList(notification);
        if (soundEnabled) playNotificationSound();

        if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
            try {
                const browserNotification = new Notification(notification.title, {
                    body: notification.message,
                    tag: `forum-notification-${notification.id}`,
                });
                browserNotification.onclick = () => {
                    window.focus();
                    window.location.href = notification.url;
                    browserNotification.close();
                };
            } catch (error) {
                console.warn('Impossible d’afficher l’alerte navigateur.', error);
            }
        }
    }

    function addNotificationToList(notification) {
        const emptyState = document.querySelector('[data-notifications-empty]');
        if (!emptyState && !document.querySelector('[data-notification-list]')) return;
        emptyState?.remove();

        let list = document.querySelector('[data-notification-list]');
        if (!list) {
            list = document.createElement('div');
            list.className = 'notification-list';
            list.dataset.notificationList = '';
            list.setAttribute('role', 'list');
            list.setAttribute('aria-live', 'polite');
            list.setAttribute('aria-relevant', 'additions');
            document.querySelector('.page-heading')?.after(list);
        }
        if (list.querySelector(`[data-notification-id="${notification.id}"]`)) return;

        const item = document.createElement('article');
        item.className = 'notification-item is-unread';
        item.dataset.notificationId = notification.id;
        item.setAttribute('role', 'listitem');
        const icon = document.createElement('div');
        icon.className = 'notification-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="fas fa-bell"></i>';

        const body = document.createElement('div');
        body.className = 'notification-body';
        const titleRow = document.createElement('div');
        titleRow.className = 'notification-title-row';
        const title = document.createElement('h2');
        title.className = 'notification-title';
        title.textContent = notification.title;
        const badge = document.createElement('span');
        badge.className = 'notification-badge';
        badge.textContent = 'Nouveau';
        titleRow.append(title, badge);
        const message = document.createElement('p');
        message.className = 'notification-message';
        const link = document.createElement('a');
        link.href = notification.url;
        link.textContent = notification.message;
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                await fetch('notifications.php', {
                    method: 'POST',
                    body: new URLSearchParams({ mark_one: String(notification.id) }),
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
            } catch (error) {
                console.error('Impossible de marquer la notification comme lue.', error);
            } finally {
                window.location.href = link.href;
            }
        });
        message.append(link);
        const time = document.createElement('small');
        time.className = 'notification-time';
        time.textContent = notification.dateLabel;
        body.append(titleRow, message, time);
        item.append(icon, body);
        list.prepend(item);

        const markReadForm = document.querySelector('[data-mark-all-read-form]');
        if (markReadForm) markReadForm.hidden = false;
    }

    function updateNotificationBadge(change) {
        document.querySelectorAll('[data-notification-count]').forEach((badge) => {
            const count = change === 0
                ? 0
                : Number.parseInt(badge.textContent || '0', 10) + change;
            badge.textContent = String(count);
            badge.hidden = count < 1;
            badge.setAttribute('aria-label', `${count} notification${count === 1 ? '' : 's'} non lue${count === 1 ? '' : 's'}`);
        });
    }

    function markNotificationsRead({ notificationId }) {
        const selector = notificationId
            ? `[data-notification-id="${notificationId}"]`
            : '.notification-item.is-unread';
        const items = [...document.querySelectorAll(selector)].filter((item) => item.classList.contains('is-unread'));
        items.forEach((item) => {
            item.classList.remove('is-unread');
            item.classList.add('is-read');
            item.querySelector('.notification-badge')?.remove();
        });
        updateNotificationBadge(notificationId ? -items.length : 0);
    }

    function unlockAudio() {
        if (!soundEnabled || !('AudioContext' in window || 'webkitAudioContext' in window)) return;
        try {
            const AudioContextConstructor = window.AudioContext || window.webkitAudioContext;
            audioContext ??= new AudioContextConstructor();
            if (audioContext.state === 'suspended') {
                audioContext.resume().catch((error) => console.warn('Le navigateur n’a pas activé la sonnerie.', error));
            }
        } catch (error) {
            console.warn('La sonnerie n’est pas prise en charge par ce navigateur.', error);
        }
    }

    function playNotificationSound() {
        if (!soundEnabled || !audioContext || audioContext.state !== 'running') return;
        const start = audioContext.currentTime;
        [880, 1174.66].forEach((frequency, index) => {
            const oscillator = audioContext.createOscillator();
            const volume = audioContext.createGain();
            const time = start + index * 0.13;
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            volume.gain.setValueAtTime(0.0001, time);
            volume.gain.exponentialRampToValueAtTime(0.12, time + 0.015);
            volume.gain.exponentialRampToValueAtTime(0.0001, time + 0.11);
            oscillator.connect(volume);
            volume.connect(audioContext.destination);
            oscillator.start(time);
            oscillator.stop(time + 0.12);
        });
    }

    function readSoundPreference() {
        try {
            return localStorage.getItem(soundStorageKey) !== 'off';
        } catch {
            return true;
        }
    }

    function updateSoundButton() {
        if (!soundButton) return;
        soundButton.setAttribute('aria-pressed', String(soundEnabled));
        soundButton.setAttribute(
            'aria-label',
            soundEnabled ? 'Désactiver la sonnerie des notifications' : 'Activer la sonnerie des notifications'
        );
        soundButton.querySelector('[data-sound-label]').textContent = soundEnabled ? 'Sonnerie activée' : 'Sonnerie désactivée';
        soundButton.querySelector('[data-sound-icon]').className = `fas ${soundEnabled ? 'fa-volume-high' : 'fa-volume-xmark'}`;
    }

    function showNotificationFeedback(message, isError = false) {
        const feedback = document.querySelector('[data-notification-feedback]');
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = `notification-feedback${isError ? ' is-error' : ''}`;
        feedback.hidden = false;
    }
})();
