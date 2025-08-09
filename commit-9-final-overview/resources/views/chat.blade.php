<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-container {
            width: 90%;
            max-width: 1200px;
            height: 80vh;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            background: #667eea;
            color: white;
            text-align: center;
        }

        .user-info {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
        }

        .user-info h3 {
            color: #333;
            margin-bottom: 5px;
        }

        .user-info p {
            color: #666;
            font-size: 14px;
        }

        .rooms-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .room-item {
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .room-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .room-item.active {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .room-item h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .room-item p {
            color: #666;
            font-size: 12px;
        }

        .main-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            background: #667eea;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h2 {
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .online-users {
            font-size: 14px;
            opacity: 0.9;
        }

        .logout-button {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .refresh-button {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .refresh-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message.own .message-content {
            background: #667eea;
            color: white;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .message-author {
            font-weight: bold;
            font-size: 14px;
        }

        .message-time {
            font-size: 12px;
            opacity: 0.7;
        }

        .message-text {
            line-height: 1.4;
        }

        .message-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .message-input-form {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .message-input:focus {
            border-color: #667eea;
        }

        .send-button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .send-button:hover {
            background: #5a6fd8;
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 400px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-group input {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-button {
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-button:hover {
            background: #5a6fd8;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div id="loginSection" class="login-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Chat Application</h2>
        <form class="login-form" id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">Login</button>
        </form>
        <p style="text-align: center; margin-top: 20px; color: #666;">
            Don't have an account? <a href="#" id="showRegister">Register</a>
        </p>
    </div>

    <div id="registerSection" class="login-container hidden">
        <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Register</h2>
        <form class="login-form" id="registerForm">
            <div class="form-group">
                <label for="regName">Name:</label>
                <input type="text" id="regName" name="name" required>
            </div>
            <div class="form-group">
                <label for="regEmail">Email:</label>
                <input type="email" id="regEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="regPassword">Password:</label>
                <input type="password" id="regPassword" name="password" required>
            </div>
            <div class="form-group">
                <label for="regPasswordConfirm">Confirm Password:</label>
                <input type="password" id="regPasswordConfirm" name="password_confirmation" required>
            </div>
            <button type="submit" class="login-button">Register</button>
        </form>
        <p style="text-align: center; margin-top: 20px; color: #666;">
            Already have an account? <a href="#" id="showLogin">Login</a>
        </p>
    </div>

    <div id="chatSection" class="chat-container hidden">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Chat Rooms</h3>
            </div>
            <div class="user-info">
                <h3 id="userName">User Name</h3>
                <p id="userEmail">user@example.com</p>
            </div>
            <div class="rooms-list" id="roomsList">
                <!-- Rooms will be loaded here -->
            </div>
        </div>
        
        <div class="main-chat">
            <div class="chat-header">
                <h2 id="currentRoomName">Select a room</h2>
                <div class="header-right">
                    <div class="online-users" id="onlineUsers">0 online</div>
                    <button id="refreshMessagesButton" class="refresh-button">🔄 Refresh</button>
                    <button id="logoutButton" class="logout-button">Logout</button>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <!-- Messages will be loaded here -->
            </div>
            
            <div class="message-input-container">
                <form class="message-input-form" id="messageForm">
                    <input type="text" class="message-input" id="messageInput" placeholder="Type your message..." disabled>
                    <button type="submit" class="send-button" disabled>Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = null;
        let currentRoom = null;
        let pusher = null;
        let channel = null;

        // API base URL
        const API_BASE = '/api';

        // Check for existing token on page load
        document.addEventListener('DOMContentLoaded', function() {
            const token = getToken();
            if (token) {
                // Set the token in axios headers
                setToken(token);
                
                // Try to get current user with existing token
                axios.get(`${API_BASE}/me`)
                    .then(response => {
                                            if (response.data.success) {
                        currentUser = response.data.data.user;
                        showChatInterface();
                        loadRooms();
                        
                        // Restore current room after rooms are loaded
                        setTimeout(() => {
                            const savedRoomId = localStorage.getItem('current_room_id');
                            if (savedRoomId) {
                                console.log('Restoring room with ID:', savedRoomId);
                                restoreCurrentRoom(parseInt(savedRoomId));
                            }
                        }, 1500);
                    }
                    })
                    .catch(error => {
                        console.error('Token validation error:', error);
                        // Token is invalid, clear it
                        clearToken();
                        showLoginInterface();
                    });
            }
        });

        // Axios configuration
        axios.defaults.baseURL = window.location.origin;
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        axios.defaults.headers.common['Accept'] = 'application/json';

        // Utility functions
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function setToken(token) {
            console.log('Setting token:', token);
            localStorage.setItem('auth_token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            console.log('Authorization header set to:', axios.defaults.headers.common['Authorization']);
        }

        function getToken() {
            return localStorage.getItem('auth_token');
        }

        function clearToken() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('current_room_id');
            delete axios.defaults.headers.common['Authorization'];
        }

        function saveCurrentRoom(room) {
            if (room && room.id) {
                localStorage.setItem('current_room_id', room.id.toString());
                console.log('Saved current room ID:', room.id);
            }
        }

        function restoreCurrentRoom(roomId) {
            console.log('Trying to restore room with ID:', roomId);
            const roomsList = document.getElementById('roomsList');
            const roomElements = roomsList.querySelectorAll('.room-item');
            
            // For now, just click the first room to restore some room
            if (roomElements.length > 0) {
                console.log('Clicking first room to restore chat');
                roomElements[0].click();
            }
        }

        // Authentication functions
        async function login(email, password) {
            try {
                console.log('Attempting login...');
                const response = await axios.post(`${API_BASE}/login`, {
                    email,
                    password
                });
                
                console.log('Login response:', response.data);
                
                if (response.data.success) {
                    setToken(response.data.data.token);
                    currentUser = response.data.data.user;
                    console.log('Token set:', getToken());
                    console.log('Authorization header:', axios.defaults.headers.common['Authorization']);
                    showChatInterface();
                    loadRooms();
                }
            } catch (error) {
                console.error('Login error:', error);
                showNotification(error.response?.data?.message || 'Login failed', 'error');
            }
        }

        async function register(name, email, password, passwordConfirmation) {
            try {
                const response = await axios.post(`${API_BASE}/register`, {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation
                });
                
                if (response.data.success) {
                    setToken(response.data.data.token);
                    currentUser = response.data.data.user;
                    showNotification('Registration successful!', 'success');
                    showChatInterface();
                    loadRooms();
                }
            } catch (error) {
                showNotification(error.response?.data?.message || 'Registration failed', 'error');
            }
        }

        async function logout() {
            try {
                await axios.post(`${API_BASE}/logout`);
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                clearToken();
                currentUser = null;
                currentRoom = null;
                showLoginInterface();
            }
        }

        // UI functions
        function showLoginInterface() {
            document.getElementById('loginSection').classList.remove('hidden');
            document.getElementById('registerSection').classList.add('hidden');
            document.getElementById('chatSection').classList.add('hidden');
        }

        function showRegisterInterface() {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('registerSection').classList.remove('hidden');
            document.getElementById('chatSection').classList.add('hidden');
        }

        function showChatInterface() {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('registerSection').classList.add('hidden');
            document.getElementById('chatSection').classList.remove('hidden');
            
            document.getElementById('userName').textContent = currentUser.name;
            document.getElementById('userEmail').textContent = currentUser.email;
        }

        // Room functions
        async function loadRooms() {
            try {
                console.log('Loading rooms...');
                console.log('Token:', getToken());
                console.log('Authorization header:', axios.defaults.headers.common['Authorization']);
                
                const response = await axios.get(`${API_BASE}/rooms`);
                console.log('Rooms response:', response.data);
                
                if (response.data.success) {
                    displayRooms(response.data.data);
                }
            } catch (error) {
                console.error('Error loading rooms:', error);
                console.error('Error response:', error.response);
                showNotification('Failed to load rooms: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        function displayRooms(rooms) {
            const roomsList = document.getElementById('roomsList');
            roomsList.innerHTML = '';
            
            // Handle paginated response - rooms.data contains the actual array
            const roomsArray = rooms.data || rooms;
            
            roomsArray.forEach(room => {
                const roomElement = document.createElement('div');
                roomElement.className = 'room-item';
                roomElement.innerHTML = `
                    <h4>${room.name}</h4>
                    <p>${room.description || 'No description'}</p>
                    <p>Type: ${room.type}</p>
                `;
                roomElement.onclick = () => joinRoom(room);
                roomsList.appendChild(roomElement);
            });
        }

        async function joinRoom(room) {
            try {
                // Check if user is already in the room
                const isAlreadyInRoom = room.users && room.users.some(user => user.id === currentUser.id);
                
                if (!isAlreadyInRoom) {
                    // Try to join the room
                    const response = await axios.post(`${API_BASE}/rooms/${room.id}/join`);
                    if (!response.data.success) {
                        showNotification('Failed to join room', 'error');
                        return;
                    }
                }
                
                // Set current room and update UI
                currentRoom = room;
                saveCurrentRoom(room); // Save room to localStorage
                document.getElementById('currentRoomName').textContent = room.name;
                document.getElementById('messageInput').disabled = false;
                document.querySelector('.send-button').disabled = false;
                
                // Update room selection
                document.querySelectorAll('.room-item').forEach(item => {
                    item.classList.remove('active');
                });
                event.target.closest('.room-item').classList.add('active');
                
                // Load messages and initialize Pusher
                loadMessages();
                initializePusher();
                
            } catch (error) {
                // If error is "user already in room", just proceed
                if (error.response?.data?.message?.includes('already in room')) {
                    // Set current room and update UI
                    currentRoom = room;
                    saveCurrentRoom(room); // Save room to localStorage
                    document.getElementById('currentRoomName').textContent = room.name;
                    document.getElementById('messageInput').disabled = false;
                    document.querySelector('.send-button').disabled = false;
                    
                    // Update room selection
                    document.querySelectorAll('.room-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    event.target.closest('.room-item').classList.add('active');
                    
                    // Load messages and initialize Pusher
                    loadMessages();
                    initializePusher();
                } else {
                    showNotification(error.response?.data?.message || 'Failed to join room', 'error');
                }
            }
        }

        // Message functions
        async function loadMessages() {
            try {
                console.log('Loading messages for room:', currentRoom.id);
                const response = await axios.get(`${API_BASE}/messages?room_id=${currentRoom.id}`);
                console.log('Messages response:', response.data);
                
                if (response.data.success) {
                    const messages = response.data.data.data || response.data.data;
                    console.log('Messages to display:', messages);
                    displayMessages(messages.reverse());
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                console.error('Error response:', error.response);
                showNotification('Failed to load messages: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        function displayMessages(messages) {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.innerHTML = '';
            
            messages.forEach(message => {
                addMessageToUI(message);
            });
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function addMessageToUI(message) {
            const messagesContainer = document.getElementById('messagesContainer');
            const messageElement = document.createElement('div');
            messageElement.className = `message ${message.user_id === currentUser.id ? 'own' : ''}`;
            
            const time = new Date(message.created_at).toLocaleTimeString();
            
            messageElement.innerHTML = `
                <div class="message-avatar">${message.user.name.charAt(0).toUpperCase()}</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author">${message.user.name}</span>
                        <span class="message-time">${time}</span>
                    </div>
                    <div class="message-text">${message.content}</div>
                </div>
            `;
            
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function sendMessage(content) {
            try {
                console.log('Sending message...');
                console.log('Current room:', currentRoom);
                console.log('Token:', getToken());
                console.log('Authorization header:', axios.defaults.headers.common['Authorization']);
                
                const response = await axios.post(`${API_BASE}/messages`, {
                    room_id: currentRoom.id,
                    content: content
                });
                
                console.log('Message sent successfully:', response.data);
                
                if (response.data.success) {
                    // Clear input field
                    document.getElementById('messageInput').value = '';
                    
                    // Add message to UI immediately
                    const message = response.data.data;
                    addMessageToUI(message);
                    
                    // Show success notification
                    showNotification('Message sent successfully!', 'success');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                console.error('Error response:', error.response);
                showNotification(error.response?.data?.message || 'Failed to send message', 'error');
            }
        }

        // Pusher functions
        function initializePusher() {
            // Skip Pusher if not configured
            if (typeof Pusher === 'undefined') {
                console.log('Pusher not available, skipping real-time features');
                return;
            }

            if (pusher) {
                pusher.disconnect();
            }

            // Check if Pusher is properly configured
            const pusherKey = 'your-pusher-key';
            const pusherCluster = 'your-cluster';
            
            if (pusherKey === 'your-pusher-key' || pusherCluster === 'your-cluster') {
                console.log('Pusher not configured, skipping real-time features');
                return;
            }

            try {
                // Initialize Pusher
                pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'Authorization': `Bearer ${getToken()}`,
                            'Accept': 'application/json',
                        }
                    }
                });

                channel = pusher.subscribe(`presence-room.${currentRoom.id}`);

                channel.bind('pusher:subscription_succeeded', (members) => {
                    document.getElementById('onlineUsers').textContent = `${Object.keys(members.members).length} online`;
                });

                channel.bind('pusher:member_added', (member) => {
                    document.getElementById('onlineUsers').textContent = `${Object.keys(channel.members.members).length} online`;
                    showNotification(`${member.info.name} joined the room`, 'success');
                });

                channel.bind('pusher:member_removed', (member) => {
                    document.getElementById('onlineUsers').textContent = `${Object.keys(channel.members.members).length} online`;
                    showNotification(`${member.info.name} left the room`, 'error');
                });

                channel.bind('App\\Events\\MessageSent', (data) => {
                    addMessageToUI(data.message);
                });
            } catch (error) {
                console.error('Pusher initialization error:', error);
            }
        }

        // Event listeners
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            login(email, password);
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const passwordConfirmation = document.getElementById('regPasswordConfirm').value;
            register(name, email, password, passwordConfirmation);
        });

        document.getElementById('showRegister').addEventListener('click', (e) => {
            e.preventDefault();
            showRegisterInterface();
        });

        document.getElementById('showLogin').addEventListener('click', (e) => {
            e.preventDefault();
            showLoginInterface();
        });

        document.getElementById('messageForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const content = document.getElementById('messageInput').value.trim();
            if (content && currentRoom) {
                sendMessage(content);
            }
        });

        document.getElementById('logoutButton').addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });

        document.getElementById('refreshMessagesButton').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentRoom) {
                loadMessages();
                showNotification('Messages refreshed!', 'success');
            }
        });

        // Check if user is already logged in
        window.addEventListener('load', () => {
            const token = getToken();
            if (token) {
                axios.get(`${API_BASE}/me`)
                    .then(response => {
                        if (response.data.success) {
                            currentUser = response.data.data.user;
                            showChatInterface();
                            loadRooms();
                        } else {
                            clearToken();
                            showLoginInterface();
                        }
                    })
                    .catch(() => {
                        clearToken();
                        showLoginInterface();
                    });
            }
        });
    </script>
</body>
</html> 