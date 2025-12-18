<?php
/**
 * Simple WebSocket Server for Real-time Notifications
 * This server handles real-time notifications for the AgriSea Marketplace
 */

class WebSocketServer {
    private $host;
    private $port;
    private $clients;
    private $sockets;
    private $pdo;

    public function __construct($host = 'localhost', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
        $this->clients = [];
        $this->sockets = [];
        
        // Initialize database connection
        try {
            $this->pdo = new PDO(
                'mysql:host=localhost;dbname=agrisea_enhanced;charset=utf8mb4',
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function start() {
        // Create TCP/IP socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        // Bind socket to host and port
        if (!socket_bind($socket, $this->host, $this->port)) {
            die("Could not bind socket: " . socket_strerror(socket_last_error()));
        }
        
        // Listen for connections
        if (!socket_listen($socket, 5)) {
            die("Could not listen on socket: " . socket_strerror(socket_last_error()));
        }
        
        // Add master socket to sockets array
        $this->sockets[] = $socket;
        
        echo "WebSocket server started on ws://{$this->host}:{$this->port}\n";
        
        while (true) {
            // Prepare sockets for select
            $read = $this->sockets;
            $write = null;
            $except = null;
            
            // Select sockets with timeout
            if (socket_select($read, $write, $except, 0, 10000) < 1) {
                continue;
            }
            
            // Check for new connections
            if (in_array($socket, $read)) {
                $newSocket = socket_accept($socket);
                if ($newSocket !== false) {
                    $this->connect($newSocket);
                    $key = array_search($socket, $read);
                    unset($read[$key]);
                }
            }
            
            // Handle existing clients
            foreach ($read as $clientSocket) {
                $data = socket_read($clientSocket, 1024);
                
                if ($data === false || strlen($data) === 0) {
                    // Client disconnected
                    $this->disconnect($clientSocket);
                } else {
                    // Process client data
                    $this->processData($clientSocket, $data);
                }
            }
            
            // Check for new notifications periodically
            $this->checkForNewNotifications();
        }
        
        socket_close($socket);
    }

    private function connect($socket) {
        // Perform WebSocket handshake
        $headers = socket_read($socket, 1024);
        $this->performHandshake($socket, $headers);
        
        // Add client to clients array
        $clientId = uniqid();
        $this->clients[$clientId] = [
            'socket' => $socket,
            'user_id' => null,
            'connected_at' => time()
        ];
        
        $this->sockets[] = $socket;
        
        echo "Client {$clientId} connected\n";
    }

    private function disconnect($socket) {
        // Find and remove client
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $socket) {
                unset($this->clients[$clientId]);
                break;
            }
        }
        
        // Remove socket from sockets array
        $key = array_search($socket, $this->sockets);
        if ($key !== false) {
            unset($this->sockets[$key]);
        }
        
        socket_close($socket);
        echo "Client disconnected\n";
    }

    private function performHandshake($socket, $headers) {
        // Extract WebSocket key from headers
        preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches);
        if (!isset($matches[1])) {
            socket_close($socket);
            return;
        }
        
        $key = $matches[1];
        $magicString = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $acceptKey = base64_encode(sha1($key . $magicString, true));
        
        // Send handshake response
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        
        socket_write($socket, $upgrade, strlen($upgrade));
    }

    private function processData($socket, $data) {
        // Decode WebSocket frame
        $decodedData = $this->decodeWebSocketFrame($data);
        
        if ($decodedData === false) {
            return;
        }
        
        // Parse JSON message
        $message = json_decode($decodedData, true);
        if ($message === null) {
            return;
        }
        
        // Handle different message types
        switch ($message['type']) {
            case 'authenticate':
                $this->authenticateClient($socket, $message);
                break;
            case 'ping':
                $this->sendToClient($socket, ['type' => 'pong']);
                break;
            default:
                // Echo back unknown messages
                $this->sendToClient($socket, $message);
        }
    }

    private function authenticateClient($socket, $message) {
        // Validate authentication token
        $token = $message['token'] ?? '';
        $userId = $this->validateAuthToken($token);
        
        if ($userId !== false) {
            // Find client and update user_id
            foreach ($this->clients as $clientId => &$client) {
                if ($client['socket'] === $socket) {
                    $client['user_id'] = $userId;
                    $this->sendToClient($socket, [
                        'type' => 'auth_success',
                        'message' => 'Authentication successful'
                    ]);
                    break;
                }
            }
        } else {
            $this->sendToClient($socket, [
                'type' => 'auth_error',
                'message' => 'Invalid authentication token'
            ]);
        }
    }

    private function validateAuthToken($token) {
        try {
            // Query database to validate token
            $stmt = $this->pdo->prepare("
                SELECT u.id 
                FROM users u 
                WHERE u.remember_token = ? 
                AND u.remember_token IS NOT NULL 
                AND u.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? $user['id'] : false;
        } catch (Exception $e) {
            error_log("Auth token validation error: " . $e->getMessage());
            return false;
        }
    }

    private function decodeWebSocketFrame($data) {
        // Simple WebSocket frame decoder (basic implementation)
        if (strlen($data) < 2) {
            return false;
        }
        
        $bytes = unpack('C*', $data);
        $secondByte = $bytes[2];
        $isMasked = ($secondByte & 0x80) >> 7;
        $payloadLength = $secondByte & 0x7F;
        
        $maskStart = 2;
        if ($payloadLength === 126) {
            $maskStart = 4;
        } elseif ($payloadLength === 127) {
            $maskStart = 10;
        }
        
        $payloadStart = $maskStart;
        if ($isMasked) {
            $payloadStart += 4;
        }
        
        $payload = substr($data, $payloadStart);
        
        if ($isMasked) {
            $mask = substr($data, $maskStart, 4);
            $payload = $this->unmaskPayload($payload, $mask);
        }
        
        return $payload;
    }

    private function unmaskPayload($payload, $mask) {
        $unmasked = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $unmasked .= $payload[$i] ^ $mask[$i % 4];
        }
        return $unmasked;
    }

    private function encodeWebSocketFrame($data) {
        // Simple WebSocket frame encoder (basic implementation)
        $frameHead = [];
        $frameHead[0] = 129; // FIN + Text frame
        
        $dataLength = strlen($data);
        
        if ($dataLength <= 125) {
            $frameHead[1] = $dataLength;
        } elseif ($dataLength <= 65535) {
            $frameHead[1] = 126;
            $frameHead[2] = ($dataLength >> 8) & 255;
            $frameHead[3] = $dataLength & 255;
        } else {
            $frameHead[1] = 127;
            for ($i = 7; $i >= 0; $i--) {
                $frameHead[$i + 2] = ($dataLength >> (8 * (7 - $i))) & 255;
            }
        }
        
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        
        return implode('', $frameHead) . $data;
    }

    private function sendToClient($socket, $message) {
        $jsonData = json_encode($message);
        $frame = $this->encodeWebSocketFrame($jsonData);
        socket_write($socket, $frame, strlen($frame));
    }

    private function sendToUser($userId, $message) {
        foreach ($this->clients as $client) {
            if ($client['user_id'] == $userId) {
                $this->sendToClient($client['socket'], $message);
            }
        }
    }

    private function broadcast($message) {
        foreach ($this->clients as $client) {
            $this->sendToClient($client['socket'], $message);
        }
    }

    private function checkForNewNotifications() {
        static $lastCheck = 0;
        $now = time();
        
        // Check for new notifications every 5 seconds
        if ($now - $lastCheck < 5) {
            return;
        }
        
        $lastCheck = $now;
        
        try {
            // Get unread notifications for all connected users
            $stmt = $this->pdo->prepare("
                SELECT n.*, u.remember_token
                FROM notifications n
                JOIN users u ON n.user_id = u.id
                WHERE n.is_read = 0
                AND n.created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ORDER BY n.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Send notifications to connected clients
            foreach ($notifications as $notification) {
                $userId = $notification['user_id'];
                $token = $notification['remember_token'];
                
                // Format notification for sending
                $formattedNotification = [
                    'type' => 'notification',
                    'id' => $notification['id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'notification_type' => $notification['type'],
                    'created_at' => $notification['created_at']
                ];
                
                // Send to specific user
                $this->sendToUser($userId, $formattedNotification);
            }
        } catch (Exception $e) {
            error_log("Notification check error: " . $e->getMessage());
        }
    }
}

// Start the WebSocket server
if (php_sapi_name() === 'cli') {
    $server = new WebSocketServer('localhost', 8080);
    $server->start();
}
?>