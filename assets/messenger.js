(() => {
    const app = document.querySelector('[data-messenger]');
    if (!app) return;

    const apiUrl = 'api/messenger.php';
    const currentUserId = Number(app.dataset.userId);
    const conversationsList = document.querySelector('[data-conversation-list]');
    const messageList = document.querySelector('[data-message-list]');
    const messageForm = document.querySelector('[data-message-form]');
    const messageInput = document.querySelector('[data-message-input]');
    const dialog = document.querySelector('[data-conversation-dialog]');
    const contactList = document.querySelector('[data-contact-list]');
    const state = {
        csrfToken: app.dataset.csrfToken,
        conversations: [],
        contacts: [],
        currentConversation: null,
        socket: null,
        filter: 'all',
        newType: 'direct',
        selectedMembers: new Set(),
        typingTimer: null,
        searchTimer: null,
        onlineUsers: new Set(),
        socketReady: false,
        typingConversationId: null,
    };

    document.querySelector('[data-new-chat]').addEventListener('click', openConversationDialog);
    document.querySelector('[data-thread-back]').addEventListener('click', closeThreadOnMobile);
    document.querySelector('[data-contact-search]').addEventListener('input', (event) => {
        const search = event.currentTarget.value.trim().toLocaleLowerCase();
        state.conversationSearch = search;
        renderConversations();
    });
    document.querySelector('[data-dialog-search]').addEventListener('input', (event) => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(() => loadContacts(event.currentTarget.value.trim()), 180);
    });
    document.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => setFilter(button.dataset.filter));
    });
    document.querySelectorAll('[data-new-type]').forEach((button) => {
        button.addEventListener('click', () => setNewType(button.dataset.newType));
    });
    document.querySelector('[data-create-conversation]').addEventListener('click', createConversation);
    messageForm.addEventListener('submit', sendMessage);
    messageInput.addEventListener('input', updateTyping);
    messageInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            messageForm.requestSubmit();
        }
    });

    document.addEventListener('click', (event) => {
        const item = event.target.closest('[data-conversation-item]');
        if (item) openConversation(Number(item.dataset.conversationId));
        const contact = event.target.closest('[data-contact-item]');
        if (contact) toggleMember(Number(contact.dataset.userId), contact);
    });

    loadMessenger();

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Une erreur est survenue.');
        return result;
    }

    async function loadMessenger() {
        try {
            const data = await request(apiUrl);
            state.csrfToken = data.csrfToken;
            app.dataset.userId = data.user.id;
            state.conversations = data.conversations;
            state.contacts = data.contacts;
            renderConversations();
            renderContacts(state.contacts);
            connectSocket();

            const requestedId = Number(new URLSearchParams(location.search).get('conversation_id'));
            if (requestedId && state.conversations.some((conversation) => Number(conversation.id) === requestedId)) {
                openConversation(requestedId);
            }
        } catch (error) {
            showError(error.message);
        }
    }

    function renderConversations() {
        conversationsList.replaceChildren();
        if (!state.conversations.length) {
            conversationsList.append(createEmptyMessage('Aucune discussion. Écrivez à un membre pour commencer.'));
            return;
        }

        state.conversations.forEach((conversation) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'conversation-item';
            item.dataset.conversationItem = '';
            item.dataset.conversationId = conversation.id;
            item.dataset.searchText = `${conversation.title || ''} ${conversation.participants || ''} ${conversation.last_message || ''}`.toLocaleLowerCase();
            item.classList.toggle('is-active', Number(state.currentConversation?.id) === Number(conversation.id));

            const avatar = document.createElement('span');
            avatar.className = 'messenger-avatar';
            avatar.textContent = firstLetter(conversation.title || conversation.participants || 'G');
            if (conversation.type === 'group') avatar.classList.add('is-group');

            const copy = document.createElement('span');
            copy.className = 'conversation-copy';
            const title = document.createElement('strong');
            title.textContent = conversation.title || conversation.participants || 'Conversation';
            const preview = document.createElement('span');
            preview.textContent = conversation.last_message || (conversation.type === 'group' ? 'Groupe créé' : 'Démarrer la conversation');
            copy.append(title, preview);

            const meta = document.createElement('span');
            meta.className = 'conversation-meta';
            if (conversation.last_message_at) {
                const time = document.createElement('time');
                time.textContent = formatTime(conversation.last_message_at);
                meta.append(time);
            }
            if (Number(conversation.unread_count) > 0) {
                const unread = document.createElement('span');
                unread.className = 'conversation-unread';
                unread.textContent = conversation.unread_count;
                meta.append(unread);
            }

            item.append(avatar, copy, meta);
            conversationsList.append(item);
        });
        setFilter(state.filter);
    }

    async function openConversation(conversationId) {
        const conversation = state.conversations.find((item) => Number(item.id) === conversationId);
        if (!conversation) return;

        if (state.currentConversation && Number(state.currentConversation.id) !== conversationId) {
            const previousId = Number(state.currentConversation.id);
            state.socket?.emit('typing:set', { conversationId: previousId, typing: false });
            state.socket?.emit('conversation:leave', previousId);
        }
        state.currentConversation = conversation;
        document.querySelector('[data-thread-header]').hidden = false;
        document.querySelector('[data-thread-empty]').hidden = true;
        messageList.hidden = false;
        messageForm.hidden = false;
        document.querySelector('[data-thread-title]').textContent = conversation.title || conversation.participants || 'Conversation';
        document.querySelector('[data-thread-avatar]').textContent = firstLetter(conversation.title || conversation.participants || 'G');
        setThreadStatus(conversation);
        document.querySelectorAll('[data-conversation-item]').forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.conversationId) === conversationId);
        });
        document.querySelector('.messenger-app').classList.add('has-open-thread');
        messageList.replaceChildren(createEmptyMessage('Chargement des messages…'));
        history.replaceState(null, '', `index.php?conversation_id=${conversationId}`);

        try {
            const result = await request(`${apiUrl}?conversation_id=${conversationId}`);
            if (Number(state.currentConversation?.id) !== conversationId) return;
            messageList.replaceChildren();
            if (!result.messages.length) {
                messageList.append(createEmptyMessage('Envoyez le premier message.'));
            } else {
                result.messages.forEach((message) => renderMessage(message));
            }
            messageList.scrollTop = messageList.scrollHeight;
            state.socket?.emit('conversation:join', conversationId);
            markRead(conversationId);
        } catch (error) {
            messageList.replaceChildren(createEmptyMessage(error.message));
        }
        messageInput.focus();
    }

    function renderMessage(message) {
        if (messageList.querySelector(`[data-message-id="${message.id}"]`)) return;
        messageList.querySelector('[data-empty-message]')?.remove();

        const ownMessage = Number(message.sender_id ?? message.senderId) === currentUserId;
        const bubble = document.createElement('article');
        bubble.className = `message-bubble${ownMessage ? ' is-own' : ''}`;
        bubble.dataset.messageId = message.id;

        if (!ownMessage) {
            const sender = document.createElement('strong');
            sender.className = 'message-sender';
            sender.textContent = message.sender_name ?? message.senderName;
            bubble.append(sender);
        }
        const body = document.createElement('p');
        body.className = 'message-body';
        body.textContent = message.body;
        const meta = document.createElement('div');
        meta.className = 'message-meta';
        const time = document.createElement('time');
        time.dateTime = message.date_creation ?? message.createdAt;
        time.textContent = formatTime(message.date_creation ?? message.createdAt);
        meta.append(time);
        if (ownMessage) {
            const receipt = document.createElement('span');
            receipt.className = 'message-receipt';
            receipt.dataset.messageReceipt = '';
            receipt.textContent = Number(message.read_count ?? message.readCount) > 0 ? '✓✓' : '✓';
            receipt.setAttribute('aria-label', Number(message.read_count ?? message.readCount) > 0 ? 'Lu' : 'Envoyé');
            meta.append(receipt);
        }
        bubble.append(body, meta);
        messageList.append(bubble);
        messageList.scrollTop = messageList.scrollHeight;
    }

    async function sendMessage(event) {
        event.preventDefault();
        if (!state.currentConversation) return;
        const body = messageInput.value.trim();
        if (!body) return;

        const submit = messageForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            const result = await request(apiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': state.csrfToken },
                body: JSON.stringify({
                    action: 'send_message',
                    conversationId: Number(state.currentConversation.id),
                    body,
                }),
            });
            renderMessage(result.message);
            messageInput.value = '';
            messageInput.style.height = '';
            loadMessenger().then(() => {
                state.currentConversation = state.conversations.find((item) => Number(item.id) === Number(result.message.conversationId)) || state.currentConversation;
                renderConversations();
            });
            state.socket?.emit('typing:set', { conversationId: Number(state.currentConversation.id), typing: false });
        } catch (error) {
            showError(error.message);
        } finally {
            submit.disabled = false;
            messageInput.focus();
        }
    }

    async function markRead(conversationId) {
        try {
            await request(apiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': state.csrfToken },
                body: JSON.stringify({ action: 'mark_read', conversationId }),
            });
            const conversation = state.conversations.find((item) => Number(item.id) === conversationId);
            if (conversation) conversation.unread_count = 0;
            renderConversations();
        } catch (error) {
            showError(error.message);
        }
    }

    function connectSocket() {
        const config = window.FORUM_REALTIME;
        if (state.socketReady || !config?.auth || !window.ForumSocketReady) return;
        state.socketReady = true;
        window.ForumSocketReady.then((socket) => {
            if (!socket) {
                state.socketReady = false;
                return;
            }
            state.socket = socket;
            socket.on('connect', () => {
                socket.emit('presence:set', 'online');
                if (state.currentConversation) socket.emit('conversation:join', Number(state.currentConversation.id));
            });
            socket.on('message:new', (message) => {
                if (Number(state.currentConversation?.id) === Number(message.conversationId)) {
                    renderMessage(message);
                    markRead(Number(message.conversationId));
                } else {
                    loadMessenger();
                }
            });
            socket.on('conversation:new', () => loadMessenger());
            socket.on('presence:snapshot', (presence) => {
                presence.forEach(updatePresence);
                updateThreadPresence();
            });
            socket.on('presence:update', updatePresence);
            socket.on('typing:update', ({ conversationId, userId, typing, name }) => {
                if (Number(state.currentConversation?.id) === Number(conversationId) && Number(userId) !== currentUserId) {
                    document.querySelector('[data-typing-indicator]').textContent = typing ? `${name} écrit…` : '';
                }
            });
            socket.on('message:read', ({ conversationId, userId, messageId }) => {
                if (Number(userId) === currentUserId || Number(state.currentConversation?.id) !== Number(conversationId)) return;
                document.querySelectorAll('[data-message-id]').forEach((message) => {
                    if (Number(message.dataset.messageId) <= Number(messageId)) {
                        const receipt = message.querySelector('[data-message-receipt]');
                        if (receipt) {
                            receipt.textContent = '✓✓';
                            receipt.setAttribute('aria-label', 'Lu');
                        }
                    }
                });
            });
            socket.on('notification:new', () => loadMessenger());
            socket.on('connect_error', () => showError('Connexion temps réel indisponible. Les messages peuvent être envoyés, mais la présence est temporairement indisponible.'));
        });
    }

    function updateTyping() {
        if (!state.currentConversation || !state.socket) return;
        const conversationId = Number(state.currentConversation.id);
        if (state.typingConversationId && state.typingConversationId !== conversationId) {
            state.socket.emit('typing:set', { conversationId: state.typingConversationId, typing: false });
        }
        state.typingConversationId = conversationId;
        state.socket.emit('typing:set', {
            conversationId,
            typing: messageInput.value.length > 0,
        });
        window.clearTimeout(state.typingTimer);
        state.typingTimer = window.setTimeout(() => {
            state.socket.emit('typing:set', { conversationId, typing: false });
            if (state.typingConversationId === conversationId) state.typingConversationId = null;
        }, 1200);
        messageInput.style.height = 'auto';
        messageInput.style.height = `${Math.min(messageInput.scrollHeight, 140)}px`;
    }

    async function openConversationDialog() {
        state.selectedMembers.clear();
        document.querySelector('[data-dialog-search]').value = '';
        document.querySelector('[data-group-title]').value = '';
        setNewType('direct');
        renderContacts(state.contacts);
        dialog.showModal();
    }

    function setNewType(type) {
        state.newType = type;
        state.selectedMembers.clear();
        document.querySelectorAll('[data-new-type]').forEach((button) => {
            const active = button.dataset.newType === type;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        document.querySelector('[data-group-title-field]').hidden = type !== 'group';
        document.querySelector('[data-create-conversation]').textContent = type === 'group' ? 'Créer le groupe' : 'Démarrer la discussion';
        renderContacts(state.contacts);
        updateCreateButton();
    }

    async function loadContacts(search = '') {
        try {
            const result = await request(`${apiUrl}?search=${encodeURIComponent(search)}`);
            state.contacts = result.contacts;
            renderContacts(result.contacts);
        } catch (error) {
            showError(error.message);
        }
    }

    function renderContacts(contacts) {
        if (!contactList) return;
        contactList.replaceChildren();
        if (!contacts.length) {
            contactList.append(createEmptyMessage('Aucun membre trouvé.'));
            return;
        }
        contacts.forEach((contact) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'contact-item';
            item.dataset.contactItem = '';
            item.dataset.userId = contact.id;
            item.classList.toggle('is-selected', state.selectedMembers.has(Number(contact.id)));
            const avatar = document.createElement('span');
            avatar.className = 'messenger-avatar';
            avatar.textContent = firstLetter(contact.pseudo);
            const name = document.createElement('span');
            name.className = 'contact-name';
            name.textContent = contact.pseudo;
            const presence = document.createElement('span');
            presence.className = `presence-dot${contact.presence === 'online' ? ' is-online' : ''}`;
            presence.setAttribute('aria-label', contact.presence === 'online' ? 'En ligne' : 'Hors ligne');
            if (contact.presence === 'online') state.onlineUsers.add(Number(contact.id));
            item.append(avatar, name, presence);
            contactList.append(item);
        });
    }

    function toggleMember(userId, item) {
        if (state.newType === 'direct') {
            state.selectedMembers.clear();
            state.selectedMembers.add(userId);
        } else if (state.selectedMembers.has(userId)) {
            state.selectedMembers.delete(userId);
        } else {
            state.selectedMembers.add(userId);
        }
        document.querySelectorAll('[data-contact-item]').forEach((contact) => {
            contact.classList.toggle('is-selected', state.selectedMembers.has(Number(contact.dataset.userId)));
        });
        updateCreateButton();
    }

    async function createConversation() {
        const button = document.querySelector('[data-create-conversation]');
        button.disabled = true;
        try {
            const result = await request(apiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': state.csrfToken },
                body: JSON.stringify({
                    action: 'create_conversation',
                    type: state.newType,
                    memberIds: [...state.selectedMembers],
                    title: document.querySelector('[data-group-title]').value.trim(),
                }),
            });
            dialog.close();
            await loadMessenger();
            await openConversation(Number(result.conversationId));
        } catch (error) {
            const message = document.querySelector('[data-dialog-error]');
            message.textContent = error.message;
            message.hidden = false;
            updateCreateButton();
        }
    }

    function updateCreateButton() {
        const button = document.querySelector('[data-create-conversation]');
        const enoughMembers = state.newType === 'direct'
            ? state.selectedMembers.size === 1
            : state.selectedMembers.size >= 2 && document.querySelector('[data-group-title]').value.trim().length > 0;
        button.disabled = !enoughMembers;
    }

    function setFilter(filter) {
        state.filter = filter;
        document.querySelectorAll('[data-filter]').forEach((button) => {
            const active = button.dataset.filter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', String(active));
        });
        document.querySelectorAll('[data-conversation-item]').forEach((item) => {
            const conversation = state.conversations.find((entry) => Number(entry.id) === Number(item.dataset.conversationId));
            item.hidden = (filter === 'unread' && Number(conversation?.unread_count) === 0)
                || !item.dataset.searchText.includes(state.conversationSearch || '');
        });
    }

    function updatePresence({ userId, status }) {
        const numericId = Number(userId);
        if (status === 'online') state.onlineUsers.add(numericId);
        else state.onlineUsers.delete(numericId);
        const contact = document.querySelector(`[data-contact-item][data-user-id="${numericId}"] .presence-dot`);
        if (contact) contact.classList.toggle('is-online', status === 'online');
        updateThreadPresence();
    }

    function updateThreadPresence(conversation = state.currentConversation) {
        const status = document.querySelector('[data-thread-status]');
        if (!status || !conversation) return;
        if (conversation.type === 'group') {
            status.textContent = conversation.participants || 'Groupe';
            return;
        }
        const participantIds = String(conversation.participant_ids || '')
            .split(',')
            .map(Number)
            .filter(Boolean);
        status.textContent = participantIds.some((id) => state.onlineUsers.has(id)) ? 'En ligne' : 'Hors ligne';
    }

    function setThreadStatus(conversation) {
        updateThreadPresence(conversation);
    }

    function closeThreadOnMobile() {
        if (state.currentConversation) {
            const conversationId = Number(state.currentConversation.id);
            state.socket?.emit('typing:set', { conversationId, typing: false });
            state.socket?.emit('conversation:leave', conversationId);
        }
        document.querySelector('.messenger-app').classList.remove('has-open-thread');
        history.replaceState(null, '', 'index.php');
    }

    function firstLetter(value) {
        return Array.from(value || '?')[0].toLocaleUpperCase();
    }

    function formatTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(date);
    }

    function createEmptyMessage(text) {
        const message = document.createElement('p');
        message.className = 'messenger-empty-message';
        message.dataset.emptyMessage = '';
        message.textContent = text;
        return message;
    }

    function showError(text) {
        const target = document.querySelector('[data-messenger-error]');
        if (!target) return;
        target.textContent = text;
        target.hidden = false;
        window.setTimeout(() => { target.hidden = true; }, 5000);
    }

    document.querySelector('[data-group-title]').addEventListener('input', updateCreateButton);
})();
