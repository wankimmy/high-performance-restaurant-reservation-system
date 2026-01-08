import express from 'express';
import cors from 'cors';
import makeWASocket, {
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    Browsers,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const app = express();
const PORT = process.env.WHATSAPP_PORT || 3001;

app.use(cors());
app.use(express.json());

// Store for active socket connections
let socket = null;
let qrCode = null;
let connectionStatus = 'disconnected';
let connectionState = null;

// Auth state directory
const authStateDir = join(__dirname, 'auth_state');

// Ensure auth state directory exists
if (!existsSync(authStateDir)) {
    mkdirSync(authStateDir, { recursive: true });
}

// Logger
const logger = pino({
    level: 'info',
    transport: {
        target: 'pino-pretty',
        options: {
            colorize: true,
        },
    },
});

/**
 * Initialize WhatsApp connection
 */
async function connectToWhatsApp() {
    try {
        const { state, saveCreds } = await useMultiFileAuthState(authStateDir);
        const { version } = await fetchLatestBaileysVersion();

        socket = makeWASocket({
            version,
            printQRInTerminal: true,
            auth: state,
            browser: Browsers.macOS('Desktop'),
            logger: pino({ level: 'silent' }),
            getMessage: async (key) => {
                return {
                    conversation: 'Message not found',
                };
            },
        });

        socket.ev.on('creds.update', saveCreds);

        socket.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCode = qr;
                qrcode.generate(qr, { small: true });
                connectionStatus = 'qr_ready';
                logger.info('QR Code generated');
            }

            if (connection === 'close') {
                const error = lastDisconnect?.error;
                const statusCode = error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                logger.info(
                    `Connection closed due to ${error}, reconnecting: ${shouldReconnect}`
                );

                connectionStatus = 'disconnected';
                qrCode = null;

                if (shouldReconnect) {
                    connectToWhatsApp();
                }
            } else if (connection === 'open') {
                connectionStatus = 'connected';
                qrCode = null;
                logger.info('WhatsApp connected successfully');
            }

            connectionState = update;
        });

        socket.ev.on('messages.upsert', (m) => {
            logger.info('New message received', m);
        });

        return socket;
    } catch (error) {
        logger.error('Error connecting to WhatsApp:', error);
        connectionStatus = 'error';
        throw error;
    }
}

/**
 * Send message via WhatsApp
 */
async function sendMessage(jid, message) {
    if (!socket || connectionStatus !== 'connected') {
        throw new Error('WhatsApp is not connected');
    }

    try {
        // Format JID (phone number)
        const formattedJid = jid.includes('@s.whatsapp.net')
            ? jid
            : `${jid.replace(/[^0-9]/g, '')}@s.whatsapp.net`;

        await socket.sendMessage(formattedJid, { text: message });
        logger.info(`Message sent to ${formattedJid}`);
        return { success: true, jid: formattedJid };
    } catch (error) {
        logger.error('Error sending message:', error);
        throw error;
    }
}

// API Routes

/**
 * Get connection status
 */
app.get('/api/status', (req, res) => {
    res.json({
        status: connectionStatus,
        hasQr: !!qrCode,
        connected: connectionStatus === 'connected',
    });
});

/**
 * Get QR code
 */
app.get('/api/qr', (req, res) => {
    if (qrCode) {
        res.json({ qr: qrCode });
    } else if (connectionStatus === 'connected') {
        res.json({ message: 'Already connected' });
    } else {
        res.status(404).json({ error: 'QR code not available' });
    }
});

/**
 * Connect/Reconnect WhatsApp
 */
app.post('/api/connect', async (req, res) => {
    try {
        if (connectionStatus === 'connected') {
            return res.json({
                success: true,
                message: 'Already connected',
                status: connectionStatus,
            });
        }

        await connectToWhatsApp();
        res.json({
            success: true,
            message: 'Connection initiated',
            status: connectionStatus,
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

/**
 * Disconnect WhatsApp
 */
app.post('/api/disconnect', async (req, res) => {
    try {
        if (socket) {
            await socket.end();
            socket = null;
            connectionStatus = 'disconnected';
            qrCode = null;
        }
        res.json({
            success: true,
            message: 'Disconnected successfully',
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

/**
 * Send message
 */
app.post('/api/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                error: 'Phone number and message are required',
            });
        }

        const result = await sendMessage(phone, message);
        res.json({
            success: true,
            ...result,
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

/**
 * Health check
 */
app.get('/health', (req, res) => {
    res.json({ status: 'ok', service: 'whatsapp-baileys' });
});

// Start server
app.listen(PORT, () => {
    logger.info(`WhatsApp Baileys service running on port ${PORT}`);
    
    // Auto-connect if auth state exists
    if (existsSync(join(authStateDir, 'creds.json'))) {
        logger.info('Auth state found, attempting to connect...');
        connectToWhatsApp();
    }
});
