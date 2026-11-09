const crypto = require('node:crypto');
const http = require('node:http');
const { timingSafeEqual } = require('node:crypto');
const mysql = require('mysql2/promise');
const { Server } = require('socket.io');

const port = Number(process.env.PORT || 3001);
const secret = process.env.REALTIME_SECRET;
const database = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    database: process.env.DB_NAME || 'forum',
    user: process.env.DB_USER || 'forum_app',
    password: process.env.DB_PASSWORD,
    waitForConnections: true,
    connectionLimit: 10,
    charset: 'utf8mb4',
});
const allowedOrigins = (process.env.APP_ORIGINS || 'http://localhost:8080,http://127.0.0.1:8080')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean);

if (!secret || secret.length < 32) {
    throw new Error('REALTIME_SECRET must contain at least 32 characters.');
}

const httpServer = http.createServer((request, response) => {
    if (request.method === 'GET' && request.url === '/health') {
        database.query('SELECT 1')
            .then(() => {
                response.writeHead(200, { 'content-type': 'application/json' });
                response.end(JSON.stringify({ status: 'ok' }));
            })
            .catch(() => {
                response.writeHead(503, { 'content-type': 'application/json' });
                response.end(JSON.stringify({ status: 'database unavailable' }));
            });
        return;
    }

    if (request.method !== 'POST' || request.url !== '/events') {
        response.writeHead(404);
        response.end();
        return;
    }

    const authorization = request.headers.authorization || '';
    const expected = Buffer.from(`Bearer ${secret}`);
    const received = Buffer.from(authorization);
    if (received.length !== expected.length || !crypto.timingSafeEqual(received, expected)) {
        response.writeHead(401);
        response.end();
        return;
    }

    let body = '';
    request.setEncoding('utf8');
    request.on('data', (chunk) => {
        body += chunk;
        if (Buffer.byteLength(body) > 1024 * 1024) {
            request.destroy();
        }
    });

    request.on('end', () => {
        let event;
        try {
            event = JSON.parse(body);
        } catch {
            response.writeHead(400);
            response.end('Invalid JSON');
            return;
        }

        dispatchInternalEvent(event)
            .then((accepted) => {
                if (!accepted) {
                    response.writeHead(400);
                    response.end('Invalid event');
                    return;
                }
                response.writeHead(204);
                response.end();
            })
            .catch((error) => {
                console.error('Realtime event processing failed:', error);
                response.writeHead(503);
                response.end('Event processing failed');
            });
    });
});

const io = new Server(httpServer, {
    cors: {
        origin(origin, callback) {
            callback(null, !origin || allowedOrigins.includes(origin));
        },
    },
});

io.use((socket, next) => {
    const auth = socket.handshake.auth?.user;
    if (!auth) {
        next();
        return;
    }

    const { userId, expiresAt, signature } = auth;
    if (!Number.isSafeInteger(userId) || userId < 1
        || !Number.isSafeInteger(expiresAt) || expiresAt <= Math.floor(Date.now() / 1000)
        || expiresAt > Math.floor(Date.now() / 1000) + 43200
        || typeof signature !== 'string' || !/^[a-f0-9]{64}$/.test(signature)) {
        next(new Error('Unauthorized'));
        return;
    }

    const expected = crypto.createHmac('sha256', secret).update(`${userId}:${expiresAt}`).digest();
    const received = Buffer.from(signature, 'hex');
    if (!timingSafeEqual(expected, received)) {
        next(new Error('Unauthorized'));
        return;
    }

    socket.data.userId = userId;
    next();
});

io.on('connection', (socket) => {
    socket.on('question:join', (questionId) => {
        const id = Number(questionId);
        if (isPositiveInteger(id)) socket.join(`question:${id}`);
    });

    const userId = socket.data.userId;
    if (!userId) return;

    const userRoom = `user:${userId}`;
    socket.join(userRoom);
    setPresence(userId, 'online').catch((error) => console.error('Could not update online presence:', error));
    sendPresenceSnapshot(socket).catch((error) => console.error('Could not load presence snapshot:', error));
    emitPresence(userId, 'online');

    socket.on('conversation:join', async (conversationId) => {
        const id = Number(conversationId);
        if (!isPositiveInteger(id)) return;
        try {
            if (await isConversationMember(id, userId)) await socket.join(`conversation:${id}`);
        } catch (error) {
            console.error('Could not authorize conversation room:', error);
        }
    });

    socket.on('conversation:leave', (conversationId) => {
        const id = Number(conversationId);
        if (isPositiveInteger(id)) socket.leave(`conversation:${id}`);
    });

    socket.on('typing:set', async (payload) => {
        const conversationId = Number(payload?.conversationId);
        if (!isPositiveInteger(conversationId)) return;
        const typing = payload?.typing === true;
        try {
            if (!(await isConversationMember(conversationId, userId))) return;
            socket.to(`conversation:${conversationId}`).emit('typing:update', {
                conversationId,
                userId,
                name: await getUserName(userId),
                typing,
            });
        } catch (error) {
            console.error('Could not relay typing state:', error);
        }
    });

    socket.on('presence:set', (status) => {
        if (status !== 'online') return;
        setPresence(userId, 'online')
            .then(() => emitPresence(userId, 'online'))
            .catch((error) => console.error('Could not update online presence:', error));
    });

    socket.on('disconnect', async () => {
        try {
            const sockets = await io.in(userRoom).fetchSockets();
            if (sockets.length === 0) {
                await setPresence(userId, 'offline');
                emitPresence(userId, 'offline');
            }
        } catch (error) {
            console.error('Could not update offline presence:', error);
        }
    });
});

httpServer.listen(port, '0.0.0.0', () => {
    console.log(`Forum realtime gateway listening on port ${port}`);
});

async function dispatchInternalEvent(event) {
    if (event?.type === 'question:new' && isQuestion(event.question)) {
            io.emit('question:new', event.question);
        return true;
    }
    if (event?.type === 'reply:new' && isReply(event.reply)) {
            io.emit('reply:created', { questionId: event.reply.questionId });
            io.to(`question:${event.reply.questionId}`).emit('reply:new', event.reply);
        return true;
    }
    if (event?.type === 'notification:new' && isNotification(event.notification)) {
            io.to(`user:${event.notification.userId}`).emit('notification:new', event.notification);
        return true;
    }
    if (event?.type === 'notification:read' && isNotificationRead(event)) {
            io.to(`user:${event.userId}`).emit('notification:read', event);
        return true;
    }

    if (event?.type === 'conversation:new' && isPositiveInteger(event.conversationId)) {
        const members = await getConversationMembers(event.conversationId);
        if (members.length < 2) return false;
        io.to(members.map((id) => `user:${id}`)).emit('conversation:new', {
            conversationId: event.conversationId,
        });
        return true;
    }

    if (event?.type === 'message:new' && isMessage(event.message)) {
        const members = await getConversationMembers(event.message.conversationId);
        if (!members.includes(event.message.senderId)) return false;
        io.to(members.map((id) => `user:${id}`)).emit('message:new', event.message);
        return true;
    }

    if (event?.type === 'message:read' && isMessageRead(event)) {
        if (!(await isConversationMember(event.conversationId, event.userId))) return false;
        const [rows] = await database.execute(
            'SELECT id FROM messages WHERE conversation_id = ? AND id = ?',
            [event.conversationId, event.messageId]
        );
        if (rows.length === 0) return false;
        io.to(`conversation:${event.conversationId}`).emit('message:read', event);
        return true;
    }

    return false;
}

async function isConversationMember(conversationId, userId) {
    const [rows] = await database.execute(
        'SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1',
        [conversationId, userId]
    );
    return rows.length > 0;
}

async function getConversationMembers(conversationId) {
    const [rows] = await database.execute(
        'SELECT user_id FROM conversation_members WHERE conversation_id = ?',
        [conversationId]
    );
    return rows.map((row) => Number(row.user_id));
}

async function getUserName(userId) {
    const [rows] = await database.execute('SELECT pseudo FROM users WHERE id = ?', [userId]);
    return rows[0]?.pseudo || 'Membre';
}

async function setPresence(userId, status) {
    await database.execute(
        'INSERT INTO user_presence (user_id, status, last_seen_at) VALUES (?, ?, CURRENT_TIMESTAMP) ' +
        'ON DUPLICATE KEY UPDATE status = VALUES(status), last_seen_at = CURRENT_TIMESTAMP',
        [userId, status]
    );
}

async function sendPresenceSnapshot(socket) {
    const [rows] = await database.execute('SELECT user_id, status FROM user_presence');
    socket.emit('presence:snapshot', rows.map((row) => ({
        userId: Number(row.user_id),
        status: row.status,
    })));
}

function emitPresence(userId, status) {
    for (const socket of io.sockets.sockets.values()) {
        if (socket.data.userId) {
            socket.emit('presence:update', { userId, status });
        }
    }
}

function isPositiveInteger(value) {
    return Number.isSafeInteger(value) && value > 0;
}

function isMessage(message) {
    return isPositiveInteger(message?.id)
        && isPositiveInteger(message?.conversationId)
        && isPositiveInteger(message?.senderId)
        && typeof message?.senderName === 'string'
        && typeof message?.body === 'string'
        && typeof message?.createdAt === 'string';
}

function isMessageRead(event) {
    return isPositiveInteger(event?.conversationId)
        && isPositiveInteger(event?.userId)
        && isPositiveInteger(event?.messageId);
}

function isQuestion(question) {
    return Number.isSafeInteger(question?.id)
        && question.id > 0
        && typeof question.title === 'string'
        && typeof question.content === 'string'
        && typeof question.author === 'string'
        && typeof question.url === 'string';
}

function isReply(reply) {
    return Number.isSafeInteger(reply?.id)
        && reply.id > 0
        && Number.isSafeInteger(reply?.questionId)
        && reply.questionId > 0
        && typeof reply.content === 'string'
        && typeof reply.author === 'string';
}

function isNotification(notification) {
    return Number.isSafeInteger(notification?.id)
        && notification.id > 0
        && Number.isSafeInteger(notification?.userId)
        && notification.userId > 0
        && typeof notification.title === 'string'
        && typeof notification.message === 'string'
        && typeof notification.dateLabel === 'string'
        && typeof notification.url === 'string'
        && (/^\/question-detail\.php\?id=\d+$/.test(notification.url)
            || /^\/index\.php\?conversation_id=\d+$/.test(notification.url));
}

function isNotificationRead(event) {
    return Number.isSafeInteger(event?.userId)
        && event.userId > 0
        && (event.notificationId === null || (Number.isSafeInteger(event.notificationId) && event.notificationId > 0));
}
