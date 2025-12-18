/**
 * WebSocket Client for Real-time Notifications
 * This client connects to the WebSocket server and handles real-time notifications
 */

class WebSocketClient {
    constructor(url, authToken) {
        this.url = url;
        this.authToken = authToken;
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000; // 1 second
        this.notificationCallback = null;
        this.onConnectCallback = null;
        this.onDisconnectCallback = null;
    }

    /**
     * Connect to the WebSocket server
     */
    connect() {
        try {
            this.socket = new WebSocket(this.url);
            
            this.socket.onopen = (event) => {
                console.log('WebSocket connection established');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                
                // Authenticate with the server
                this.authenticate();
                
                // Call onConnect callback if provided
                if (this.onConnectCallback) {
                    this.onConnectCallback(event);
                }
            };
            
            this.socket.onmessage = (event) => {
                this.handleMessage(event);
            };
            
            this.socket.onclose = (event) => {
                console.log('WebSocket connection closed');
                this.isConnected = false;
                
                // Call onDisconnect callback if provided
                if (this.onDisconnectCallback) {
                    this.onDisconnectCallback(event);
                }
                
                // Attempt to reconnect
                this.handleReconnect();
            };
            
            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.handleReconnect();
        }
    }

    /**
     * Authenticate with the WebSocket server
     */
    authenticate() {
        if (this.isConnected && this.authToken) {
            this.send({
                type: 'authenticate',
                token: this.authToken
            });
        }
    }

    /**
     * Handle incoming messages
     */
    handleMessage(event) {
        try {
            const message = JSON.parse(event.data);
            
            switch (message.type) {
                case 'auth_success':
                    console.log('WebSocket authentication successful');
                    break;
                    
                case 'auth_error':
                    console.error('WebSocket authentication failed:', message.message);
                    break;
                    
                case 'notification':
                    console.log('New notification received:', message);
                    // Call notification callback if provided
                    if (this.notificationCallback) {
                        this.notificationCallback(message);
                    }
                    break;
                    
                case 'pong':
                    // Pong response to ping
                    break;
                    
                default:
                    console.log('Received unknown message:', message);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }

    /**
     * Send a message to the server
     */
    send(message) {
        if (this.isConnected && this.socket) {
            this.socket.send(JSON.stringify(message));
        } else {
            console.warn('Cannot send message: WebSocket not connected');
        }
    }

    /**
     * Send a ping to keep the connection alive
     */
    ping() {
        this.send({ type: 'ping' });
    }

    /**
     * Handle reconnection logic
     */
    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay * this.reconnectAttempts); // Exponential backoff
        } else {
            console.error('Max reconnection attempts reached. Giving up.');
        }
    }

    /**
     * Disconnect from the WebSocket server
     */
    disconnect() {
        if (this.socket) {
            this.socket.close();
            this.socket = null;
            this.isConnected = false;
        }
    }

    /**
     * Set callback for notifications
     */
    onNotification(callback) {
        this.notificationCallback = callback;
    }

    /**
     * Set callback for connection events
     */
    onConnect(callback) {
        this.onConnectCallback = callback;
    }

    /**
     * Set callback for disconnection events
     */
    onDisconnect(callback) {
        this.onDisconnectCallback = callback;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketClient;
}