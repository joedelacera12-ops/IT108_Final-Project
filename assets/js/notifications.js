/**
 * Real-time Notification System for AgriSea Marketplace
 * Handles real-time updates for order status, delivery updates, and other notifications
 */

// Import WebSocketClient if available
let WebSocketClient;
try {
    // In a real implementation, you might need to adjust this path
    // For now, we'll assume it's available globally
} catch (e) {
    console.warn('WebSocketClient not available, falling back to polling');
}

class NotificationManager {
    constructor() {
        this.notificationInterval = null;
        this.isPolling = false;
        this.lastNotificationCount = 0;
        this.websocketClient = null;
        this.useWebSockets = false;
        this.init();
    }
    
    init() {
        // Check if user is logged in
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
            // Try to initialize WebSocket connection
            this.initWebSocket();
            
            // Fallback to polling if WebSocket is not available
            if (!this.useWebSockets) {
                this.startPolling();
            }
            
            this.setupEventListeners();
        }
    }
    
    initWebSocket() {
        // Check if WebSocket support is available
        if (typeof WebSocket !== 'undefined') {
            try {
                // Get auth token from session storage or cookies
                const authToken = this.getAuthToken();
                
                if (authToken) {
                    // Initialize WebSocket client
                    this.websocketClient = new WebSocketClient('ws://localhost:8080', authToken);
                    
                    // Set up callbacks
                    this.websocketClient.onNotification((notification) => {
                        this.handleRealTimeNotification(notification);
                    });
                    
                    this.websocketClient.onConnect(() => {
                        console.log('Connected to real-time notification service');
                        this.useWebSockets = true;
                        // Stop polling if it was running
                        this.stopPolling();
                    });
                    
                    this.websocketClient.onDisconnect(() => {
                        console.log('Disconnected from real-time notification service');
                        this.useWebSockets = false;
                        // Fall back to polling
                        this.startPolling();
                    });
                    
                    // Connect to WebSocket server
                    this.websocketClient.connect();
                }
            } catch (error) {
                console.error('Failed to initialize WebSocket connection:', error);
                this.useWebSockets = false;
            }
        }
    }
    
    getAuthToken() {
        // Try to get auth token from various sources
        // This is a simplified implementation - in a real app, you'd need to securely store and retrieve the token
        
        // Check for a meta tag with auth token (you would need to add this to your HTML)
        const metaTag = document.querySelector('meta[name="auth-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        // Check localStorage (if you store it there)
        try {
            return localStorage.getItem('auth_token');
        } catch (e) {
            // localStorage not available
        }
        
        // Return null if no token found
        return null;
    }
    
    handleRealTimeNotification(notification) {
        // Update UI with new notification
        this.updateNotificationUI([notification], this.lastNotificationCount + 1);
        
        // Play notification sound
        this.playNotificationSound();
        
        // Update notification count
        this.lastNotificationCount++;
        
        // Show browser notification if supported
        this.showBrowserNotification(notification);
    }
    
    showBrowserNotification(notification) {
        // Check if browser notifications are supported and permitted
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico'
            });
        }
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.checkNotifications(); // Initial check
        
        // Poll every 30 seconds for new notifications
        this.notificationInterval = setInterval(() => {
            this.checkNotifications();
        }, 30000);
    }
    
    stopPolling() {
        if (this.notificationInterval) {
            clearInterval(this.notificationInterval);
            this.notificationInterval = null;
        }
        this.isPolling = false;
    }
    
    async checkNotifications() {
        try {
            const response = await fetch('/ecommerce_farmers_fishers/api/get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationUI(data.notifications, data.count);
                
                // Play sound if new notifications arrived
                if (data.count > this.lastNotificationCount && this.lastNotificationCount > 0) {
                    this.playNotificationSound();
                }
                
                this.lastNotificationCount = data.count;
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }
    
    updateNotificationUI(notifications, count) {
        // Update notification badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Update dropdown if it exists
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            if (notifications.length > 0) {
                let html = '';
                notifications.forEach(notification => {
                    html += `
                        <li class="notification-item unread">
                            <div class="d-flex">
                                <div class="notification-icon bg-light">
                                    <i class="fas ${notification.icon || 'fa-bell'}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${notification.title}</h6>
                                    <p class="mb-1 small">${notification.message}</p>
                                    <small class="text-muted">${notification.created_at || new Date().toLocaleString()}</small>
                                </div>
                            </div>
                        </li>
                    `;
                });
                notificationList.innerHTML = html;
            } else {
                notificationList.innerHTML = `
                    <li class="text-center p-3">
                        <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                        <p class="mb-0 text-muted">No new notifications</p>
                    </li>
                `;
            }
        }
    }
    
    playNotificationSound() {
        // Create audio element if it doesn't exist
        let audio = document.getElementById('notificationSound');
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'notificationSound';
            audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFd2xqZ2VlZ2lucHZ8g4eLjY6QkpSWmJqcnqCipKWnqaqrrK2ur7CxsrO0tba3uLm6u7y9vr/AwcLDxMXGx8jJysvMzc7P0NHS09TV1tfY2drb3N3e3+Dh4uPk5ebn6Onq6+zt7u/w8fLz9PX29/j5+vv8/f7/AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8gISIjJCUmJygpKissLS4vMDEyMzQ1Njc4OTo7PD0+P0BBQkNERUZHSElKS0xNTk9QUVJTVFVWV1hZWltcXV5fYGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6e3x9fn+AgYKDhIWGh4iJiouMjY6PkJGSk5SVlpeYmZqbnJ2en6ChoqOkpaanqKmqq6ytrq+wsbKztLW2t7i5uru8vb6/wMHCw8TFxsfIycrLzM3Oz9DR0tPU1dbX2Nna29zd3t/g4eLj5OXm5+jp6uvs7e7v8PHy8/T19vf4+fr7/P3+/wABAgMEBQYHCAkKCwwNDg8QERITFBUWFxgZGhscHR4fICEiIyQlJicoKSorLC0uLzAxMjM0NTY3ODk6Ozw9Pj9AQUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVpbXF1eX2BhYmNkZWZnaGlqa2xtbm9wcXJzdHV2d3h5ent8fX5/gIGCg4SFhoeIiYqLjI2Oj5CRkpOUlZaXmJmam5ydnp+goaKjpKWmp6ipqqusra6vsLGys7S1tre4ubq7vL2+v8DBwsPExcbHyMnKy8zNzs/Q0dLT1NXW19jZ2tvc3d7f4OHi4+Tl5ufo6err7O3u7/Dx8vP09fb3+Pn6+/z9/v8AAQIDBAUGBwgJCgsMDQ4PEBESExQVFhcYGRobHB0eHyAhIiMkJSYnKCkqKywtLi8wMTIzNDU2Nzg5Ojs8PT4/QEFCQ0RFRkdISUpLTE1OT1BRUlNUVVZXWFlaW1xdXl9gYWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXp7fH1+f4CBgoOEhYaHiImKi4yNjo+QkZKTlJWWl5iZmpucnZ6foKGio6SlpqeoqaqrrK2ur7CxsrO0tba3uLm6u7y9vr/AwcLDxMXGx8jJysvMzc7P0NHS09TV1tfY2drb3N3e3+Dh4uPk5ebn6Onq6+zt7u/w8fLz9PX29/j5+vv8/f7/AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8gISIjJCUmJygpKissLS4vMDEyMzQ1Njc4OTo7PD0+P0BBQkNERUZHSElKS0xNTk9QUVJTVFVWV1hZWltcXV5fYGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6e3x9fn+AgYKDhIWGh4iJiouMjY6PkJGSk5SVlpeYmZqbnJ2en6ChoqOkpaanqKmqq6ytrq+wsbKztLW2t7i5uru8vb6/wMHCw8TFxsfIycrLzM3Oz9DR0tPU1dbX2Nna29zd3t/g4eLj5OXm5+jp6uvs7e7v8PHy8/T19vf4+fr7/P3+/w==';
            document.body.appendChild(audio);
        }
        
        // Play the sound
        try {
            audio.play().catch(e => {
                // Ignore errors in playing sound
            });
        } catch (e) {
            // Ignore errors
        }
    }
    
    setupEventListeners() {
        // Handle marking notifications as read
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-read')) {
                const notificationId = e.target.getAttribute('data-id');
                this.markAsRead(notificationId);
            }
        });
        
        // Handle marking all as read
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
        
        // Request notification permissions on page load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('/ecommerce_farmers_fishers/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({id: notificationId})
            });
            
            const data = await response.json();
            if (data.success) {
                // Refresh notifications
                if (this.useWebSockets) {
                    // With WebSockets, we might not need to refresh
                    // But we'll still update the UI to mark as read
                } else {
                    this.checkNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('/ecommerce_farmers_fishers/mark_all_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                // Refresh notifications
                if (this.useWebSockets) {
                    // With WebSockets, we might not need to refresh
                } else {
                    this.checkNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}