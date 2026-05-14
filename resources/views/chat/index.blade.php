@extends('layouts.main')

@section('title', 'Chat')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <div class="row">
            <div class="col-12">
                <div class="page-breadcrumb d-flex align-items-center">
                    <div class="me-auto">
                        <x-breadcrumbs-with-icons :links="[
                            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                            ['label' => 'Chat', 'url' => '#', 'icon' => 'bx bx-message-rounded-dots']
                        ]" />
                    </div>
                </div>
            </div>
        </div>
        <h6 class="mb-0 text-uppercase">CHAT</h6>
        <hr />

        <!-- Modern Chat Container -->
        <div class="row">
            <div class="col-12">
                <div class="modern-chat-container">
                    <!-- Chat Sidebar -->
                    <div class="chat-sidebar-modern">
                        <!-- User Profile Header -->
                        <div class="user-profile-header">
                            <div class="user-avatar">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" />
                                @else
                                    <img src="assets/images/avatars/avatar-1.png" alt="{{ auth()->user()->name }}" />
                                @endif
                                <span class="status-indicator online"></span>
                            </div>
                            <div class="user-info">
                                <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                                <small class="text-muted">Online</small>
                            </div>
                            <div class="header-actions">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class='bx bx-dots-horizontal-rounded'></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="#"><i class='bx bx-cog me-2'></i>Settings</a>
                                    <a class="dropdown-item" href="#"><i class='bx bx-help-circle me-2'></i>Help</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class='bx bx-log-out me-2'></i>Sign Out
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logout Form (hidden) -->
                        <form id="logout-form" action="/logout" method="POST" class="d-none">
                            @csrf
                        </form>

                        <!-- Search Bar -->
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <i class='bx bx-search search-icon'></i>
                                <input type="text" class="form-control search-input" id="userSearchInput" placeholder="Search users..." />
                                <i class='bx bx-filter-alt filter-icon' id="clearSearch" style="cursor: pointer; display: none;"></i>
                            </div>
                        </div>

                        <!-- Navigation Tabs -->
                        <div class="nav-tabs-modern">
                            <button class="nav-tab active" data-tab="chats">
                                <i class='bx bx-conversation'></i>
                                <span>Chats</span>
                            </button>
                            <button class="nav-tab" data-tab="calls">
                                <i class='bx bx-phone'></i>
                                <span>Calls</span>
                            </button>
                            <button class="nav-tab" data-tab="contacts">
                                <i class='bx bxs-contact'></i>
                                <span>Contacts</span>
                            </button>
                            <button class="nav-tab" data-tab="notifications">
                                <i class='bx bx-bell'></i>
                                <span>Notifications</span>
                            </button>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm action-btn">
                                <i class='bx bx-video me-1'></i>Meet Now
                            </button>
                            <button class="btn btn-outline-primary btn-sm action-btn">
                                <i class='bx bx-plus me-1'></i>New Chat
                            </button>
                        </div>

                        <!-- Conversations List -->
                        <div class="conversations-list">
                            @forelse($users as $user)
                                @php
                                    $isOnline = $user->id % 3 == 0;
                                    $statusClass = $isOnline ? 'online' : 'offline';
                                    $statusText = $isOnline ? 'Online' : 'Offline';
                                @endphp
                                <div class="conversation-item" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-user-phone="{{ $user->phone }}" data-user-status="{{ $statusText }}" data-user-online="{{ $isOnline ? 'true' : 'false' }}">
                                    <div class="conversation-avatar">
                                        @if($user->profile_photo_path)
                                            <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" />
                                        @else
                                            <img src="assets/images/avatars/avatar-{{ ($user->id % 6) + 1 }}.png" alt="{{ $user->name }}" />
                                        @endif
                                        <span class="status-indicator {{ $statusClass }}"></span>
                                    </div>
                                    <div class="conversation-content">
                                        <div class="conversation-header">
                                            <h6 class="conversation-name">{{ $user->name }}</h6>
                                            <span class="conversation-time">{{ $statusText }}</span>
                                        </div>
                                        <p class="conversation-preview">{{ $user->role ?? 'User' }} • {{ $user->branch->name ?? 'Branch' }} • {{ format_phone_for_display($user->phone) }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class='bx bx-user-x fs-1 text-muted'></i>
                                    <p class="text-muted mt-2">No users found</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Chat Main Area -->
                    <div class="chat-main-area">
                        <!-- Chat Header -->
                        <div class="chat-header-modern">
                            <div class="chat-header-left">
                                <div class="current-chat-user" id="current-chat-user" style="display: none;">
                                    <img src="" alt="" id="chat-user-avatar" />
                                    <div class="user-details">
                                        <h5 class="mb-0" id="chat-user-name"></h5>
                                        <small class="text-muted" id="chat-user-phone"></small>
                                        <small class="text-success" id="chat-user-status">
                                            <i class='bx bxs-circle'></i> Active now
                                        </small>
                                    </div>
                                </div>
                                <div class="no-chat-selected" id="no-chat-selected">
                                    <h5 class="mb-0 text-muted">Select a user to start chatting</h5>
                                </div>
                            </div>
                            <div class="chat-header-right">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class='bx bx-search'></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="voiceCallBtn" title="Voice Call">
                                    <i class='bx bx-phone'></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="videoCallBtn" title="Video Call">
                                    <i class='bx bx-video'></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary notification-btn" id="notificationBtn" title="Notifications">
                                    <i class='bx bx-bell'></i>
                                    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages -->
                        <div class="chat-messages-container">
                            <div class="messages-wrapper" id="messagesWrapper">
                                <!-- Welcome Message (shown when no user is selected) -->
                                <div class="welcome-message" id="welcomeMessage">
                                    <div class="welcome-content">
                                        <div class="welcome-icon">
                                            <i class='bx bx-message-rounded-dots'></i>
                                        </div>
                                        <h4>Welcome to {{ \App\Services\SystemSettingService::get('app_name', 'SmartAccounting') }} Chat!</h4>
                                        <p>Select a user from the sidebar to start chatting. You can:</p>
                                        <div class="welcome-features">
                                            <div class="feature-item">
                                                <i class='bx bx-message-square'></i>
                                                <span>Send text messages</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class='bx bx-paperclip'></i>
                                                <span>Share documents and files</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class='bx bx-phone'></i>
                                                <span>Voice and video calls</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class='bx bx-search'></i>
                                                <span>Search for users</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class='bx bx-check-double'></i>
                                                <span>See message delivery status</span>
                                            </div>
                                        </div>
                                        <div class="welcome-tip">
                                            <i class='bx bx-info-circle'></i>
                                            <span>Tip: Use the search bar to quickly find users</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="chat-input-container">
                            <div class="input-wrapper">
                                <button class="btn btn-sm btn-outline-secondary attachment-btn" id="attachmentBtn">
                                    <i class='bx bx-paperclip'></i>
                                </button>
                                <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.xlsx,.xls" style="display: none;" />
                                <div class="message-input-wrapper">
                                    <input type="text" class="form-control message-input" id="messageInput" placeholder="Type a message..." />
                                    <button class="btn btn-sm btn-outline-secondary emoji-btn">
                                        <i class='bx bx-smile'></i>
                                    </button>
                                </div>
                                <button class="btn btn-primary send-btn" id="sendMessageBtn">
                                    <i class='bx bx-send'></i>
                                </button>
                            </div>
                            
                            <!-- File Preview -->
                            <div id="filePreview" class="file-preview" style="display: none;">
                                <div class="file-preview-content">
                                    <div class="file-info">
                                        <i class='bx bx-file file-icon' id="filePreviewIcon"></i>
                                        <div class="file-details">
                                            <span class="file-name" id="fileName"></span>
                                            <span class="file-size" id="fileSize"></span>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger remove-file-btn" id="removeFileBtn">
                                        <i class='bx bx-x'></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call Modals -->
<!-- Voice Call Modal -->
<div class="modal fade" id="voiceCallModal" tabindex="-1" aria-labelledby="voiceCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content call-modal">
            <div class="modal-header call-header">
                <h5 class="modal-title" id="voiceCallModalLabel">
                    <i class='bx bx-phone'></i> Voice Call
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body call-body">
                <div class="call-user-info">
                    <div class="call-avatar">
                        <img id="voiceCallAvatar" src="assets/images/avatars/avatar-1.png" alt="User" />
                        <div class="call-status-indicator">
                            <i class='bx bx-phone-call'></i>
                        </div>
                    </div>
                    <h4 id="voiceCallUserName">User Name</h4>
                    <p id="voiceCallStatus">Calling...</p>
                </div>
                <div class="call-timer" id="voiceCallTimer" style="display: none;">
                    <span id="voiceCallTime">00:00</span>
                </div>
            </div>
            <div class="modal-footer call-footer">
                <div class="call-controls">
                    <button class="btn btn-lg btn-outline-secondary call-control-btn" id="muteVoiceBtn" title="Mute">
                        <i class='bx bx-microphone'></i>
                    </button>
                    <button class="btn btn-lg btn-danger call-control-btn" id="endVoiceCallBtn" title="End Call">
                        <i class='bx bx-phone-off'></i>
                    </button>
                    <button class="btn btn-lg btn-outline-secondary call-control-btn" id="speakerVoiceBtn" title="Speaker">
                        <i class='bx bx-volume-full'></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Video Call Modal -->
<div class="modal fade" id="videoCallModal" tabindex="-1" aria-labelledby="videoCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content call-modal">
            <div class="modal-header call-header">
                <h5 class="modal-title" id="videoCallModalLabel">
                    <i class='bx bx-video'></i> Video Call
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body call-body">
                <div class="video-container">
                    <div class="main-video" id="mainVideo">
                        <video id="remoteVideo" autoplay muted></video>
                        <div class="video-overlay" id="videoOverlay">
                            <div class="call-user-info">
                                <div class="call-avatar">
                                    <img id="videoCallAvatar" src="assets/images/avatars/avatar-1.png" alt="User" />
                                    <div class="call-status-indicator">
                                        <i class='bx bx-video'></i>
                                    </div>
                                </div>
                                <h4 id="videoCallUserName">User Name</h4>
                                <p id="videoCallStatus">Calling...</p>
                            </div>
                        </div>
                    </div>
                    <div class="local-video" id="localVideo">
                        <video id="localVideoElement" autoplay muted></video>
                        <button class="btn btn-sm btn-outline-secondary camera-toggle" id="cameraToggleBtn" title="Toggle Camera">
                            <i class='bx bx-camera-off'></i>
                        </button>
                    </div>
                </div>
                <div class="call-timer" id="videoCallTimer" style="display: none;">
                    <span id="videoCallTime">00:00</span>
                </div>
            </div>
            <div class="modal-footer call-footer">
                <div class="call-controls">
                    <button class="btn btn-lg btn-outline-secondary call-control-btn" id="muteVideoBtn" title="Mute">
                        <i class='bx bx-microphone'></i>
                    </button>
                    <button class="btn btn-lg btn-danger call-control-btn" id="endVideoCallBtn" title="End Call">
                        <i class='bx bx-phone-off'></i>
                    </button>
                    <button class="btn btn-lg btn-outline-secondary call-control-btn" id="cameraBtn" title="Camera">
                        <i class='bx bx-camera'></i>
                    </button>
                    <button class="btn btn-lg btn-outline-secondary call-control-btn" id="speakerVideoBtn" title="Speaker">
                        <i class='bx bx-volume-full'></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incoming Call Modal -->
<div class="modal fade" id="incomingCallModal" tabindex="-1" aria-labelledby="incomingCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content call-modal incoming-call">
            <div class="modal-header call-header">
                <h5 class="modal-title" id="incomingCallModalLabel">
                    <i class='bx bx-phone-incoming'></i> Incoming Call
                </h5>
            </div>
            <div class="modal-body call-body">
                <div class="call-user-info">
                    <div class="call-avatar incoming-call-avatar">
                        <img id="incomingCallAvatar" src="assets/images/avatars/avatar-1.png" alt="User" />
                        <div class="call-status-indicator incoming">
                            <i class='bx bx-phone'></i>
                        </div>
                    </div>
                    <h4 id="incomingCallUserName">User Name</h4>
                    <p id="incomingCallType">Incoming call...</p>
                </div>
            </div>
            <div class="modal-footer call-footer">
                <div class="call-controls">
                    <button class="btn btn-lg btn-success call-control-btn" id="acceptCallBtn" title="Accept">
                        <i class='bx bx-phone'></i>
                    </button>
                    <button class="btn btn-lg btn-danger call-control-btn" id="rejectCallBtn" title="Reject">
                        <i class='bx bx-phone-off'></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Panel -->
<div class="notification-panel" id="notificationPanel" style="display: none;">
    <div class="notification-header">
        <h6 class="mb-0">
            <i class='bx bx-bell me-2'></i>Notifications
        </h6>
        <button class="btn btn-sm btn-outline-secondary" id="closeNotificationPanel">
            <i class='bx bx-x'></i>
        </button>
    </div>
    <div class="notification-list" id="notificationList">
        <!-- Notifications will be populated here -->
    </div>
    <div class="notification-footer">
        <button class="btn btn-sm btn-outline-primary" id="markAllRead">
            <i class='bx bx-check-double me-1'></i>Mark All as Read
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="clearAllNotifications">
            <i class='bx bx-trash me-1'></i>Clear All
        </button>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Modern Chat Design - Complete Redesign */
.modern-chat-container {
    display: flex;
    height: calc(100vh - 200px);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

/* Sidebar Styles */
.chat-sidebar-modern {
    width: 320px;
    background: #f0f2f5;
    color: #111b21;
    display: flex;
    flex-direction: column;
    position: relative;
    border-right: 1px solid #e9edef;
}

.user-profile-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #e9edef;
    background: #ffffff;
}

.user-avatar {
    position: relative;
}

.user-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid #e9edef;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.status-indicator.online {
    background: #28a745;
}

.status-indicator.offline {
    background: #6c757d;
}

.user-info h6 {
    color: #111b21;
    margin: 0;
    font-weight: 600;
}

.user-info small {
    color: #667781;
}

.header-actions {
    margin-left: auto;
}

.search-container {
    padding: 16px 20px;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: #ffffff;
    border-radius: 8px;
    padding: 8px 12px;
    border: 1px solid #e9edef;
}

.search-input {
    background: transparent;
    border: none;
    color: #111b21;
    padding-left: 8px;
    padding-right: 8px;
}

.search-input::placeholder {
    color: #667781;
}

.search-icon, .filter-icon {
    color: #667781;
    font-size: 18px;
}

#clearSearch:hover {
    color: #00a884;
    transform: scale(1.1);
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 168, 132, 0.2);
}

.no-search-results {
    color: #667781;
}

.no-search-results i {
    font-size: 48px;
    opacity: 0.5;
}

.nav-tabs-modern {
    display: flex;
    padding: 0 20px;
    gap: 8px;
    margin-bottom: 16px;
}

.nav-tab {
    flex: 1;
    background: transparent;
    border: none;
    color: #667781;
    padding: 12px 8px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.nav-tab.active {
    background: #ffffff;
    color: #00a884;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.nav-tab i {
    font-size: 20px;
}

.nav-tab span {
    font-size: 12px;
    font-weight: 500;
}

.action-buttons {
    padding: 0 20px;
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.action-btn {
    flex: 1;
    border-radius: 8px;
    font-size: 12px;
    padding: 8px 12px;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 20px;
}

.conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.conversation-item:hover {
    background: #f5f6f6;
}

.conversation-item.active {
    background: #ffffff;
    border-left: 3px solid #00a884;
}

.conversation-avatar {
    position: relative;
}

.conversation-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #e9edef;
}

.conversation-content {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.conversation-name {
    color: #111b21;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-time {
    color: #667781;
    font-size: 12px;
}

.conversation-preview {
    color: #667781;
    font-size: 13px;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Main Chat Area */
.chat-main-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #efeae2;
}

.chat-header-modern {
    background: white;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e9ecef;
}

.chat-header-left {
    display: flex;
    align-items: center;
}

.current-chat-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.current-chat-user img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.user-details h5 {
    color: #333;
    font-weight: 600;
    margin: 0;
}

.user-details small {
    color: #28a745;
    font-weight: 500;
}

.chat-header-right {
    display: flex;
    gap: 8px;
}

.chat-header-right .btn {
    border-radius: 8px;
    padding: 8px 12px;
}

.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: #efeae2;
}

.messages-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

/* Welcome Message Styles */
.welcome-message {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    min-height: 400px;
}

.welcome-content {
    text-align: center;
    max-width: 500px;
    padding: 40px 20px;
}

.welcome-icon {
    margin-bottom: 24px;
}

.welcome-icon i {
    font-size: 64px;
    color: #00a884;
    opacity: 0.8;
}

.welcome-content h4 {
    color: #111b21;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 16px;
}

.welcome-content p {
    color: #667781;
    font-size: 16px;
    margin-bottom: 32px;
    line-height: 1.5;
}

.welcome-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 12px;
    border: 1px solid rgba(0, 168, 132, 0.1);
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 168, 132, 0.1);
}

.feature-item i {
    font-size: 20px;
    color: #00a884;
    width: 24px;
    text-align: center;
}

.feature-item span {
    color: #111b21;
    font-size: 14px;
    font-weight: 500;
}

.welcome-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(0, 168, 132, 0.1);
    border-radius: 8px;
    border-left: 4px solid #00a884;
}

.welcome-tip i {
    color: #00a884;
    font-size: 16px;
}

.welcome-tip span {
    color: #111b21;
    font-size: 14px;
    font-weight: 500;
}

.message-item {
    display: flex;
    margin-bottom: 20px;
    align-items: flex-end;
}

.message-item.received {
    justify-content: flex-start;
}

.message-item.sent {
    justify-content: flex-end;
}

.message-avatar {
    margin-right: 12px;
}

.message-avatar img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.message-content {
    max-width: 60%;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message-bubble.received {
    background: #ffffff;
    color: #111b21;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.message-bubble.sent {
    background: #d9fdd3;
    color: #111b21;
    border-bottom-right-radius: 4px;
}

.message-bubble p {
    margin: 0;
    line-height: 1.4;
}

.message-meta {
    margin-top: 4px;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
}

.message-time {
    font-size: 11px;
    color: #6c757d;
}

.message-status {
    display: inline-flex;
    align-items: center;
    font-size: 14px;
}

.message-status.sent {
    color: #6c757d;
}

.message-status.delivered {
    color: #6c757d;
}

.message-status.read {
    color: #34b7f1;
}

.message-item.sent .message-time {
    text-align: right;
}

.message-item.received .message-time {
    text-align: left;
}

/* Chat Input */
.chat-input-container {
    background: white;
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
}

.input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8f9fa;
    border-radius: 24px;
    padding: 8px;
}

.attachment-btn {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: white;
    color: #6c757d;
}

.message-input-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 20px;
    padding: 0 16px;
}

.message-input {
    border: none;
    background: transparent;
    padding: 12px 0;
    flex: 1;
}

.message-input:focus {
    box-shadow: none;
    outline: none;
}

.emoji-btn {
    border: none;
    background: transparent;
    color: #6c757d;
    padding: 8px;
    border-radius: 50%;
}

.emoji-btn:hover {
    background: #f8f9fa;
}

.send-btn {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: #00a884;
    color: white;
}

.send-btn:hover {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

/* File Preview Styles */
.file-preview {
    margin-top: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.file-preview-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.file-icon {
    font-size: 24px;
    color: #6c757d;
}

.file-details {
    display: flex;
    flex-direction: column;
}

.file-name {
    font-weight: 500;
    color: #111b21;
    font-size: 14px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-size {
    font-size: 12px;
    color: #667781;
}

.remove-file-btn {
    padding: 6px 10px;
    border-radius: 6px;
}

.remove-file-btn:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

/* File type specific icons */
.file-preview[data-file-type="pdf"] .file-icon {
    color: #dc3545;
}

.file-preview[data-file-type="doc"] .file-icon,
.file-preview[data-file-type="docx"] .file-icon {
    color: #0d6efd;
}

.file-preview[data-file-type="xlsx"] .file-icon,
.file-preview[data-file-type="xls"] .file-icon {
    color: #198754;
}

.file-preview[data-file-type="jpg"] .file-icon,
.file-preview[data-file-type="jpeg"] .file-icon,
.file-preview[data-file-type="png"] .file-icon,
.file-preview[data-file-type="gif"] .file-icon {
    color: #fd7e14;
}

.file-preview[data-file-type="txt"] .file-icon {
    color: #6c757d;
}

/* File attachment in messages */
.file-attachment {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    margin-top: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.file-attachment:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.file-attachment .file-icon {
    font-size: 22px;
    color: rgba(255, 255, 255, 0.95);
    width: 24px;
    text-align: center;
}

.file-attachment .file-name {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 600;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.3;
}

.file-attachment .file-size {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
    white-space: nowrap;
}

/* File attachment in received messages */
.message-item.received .file-attachment {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.message-item.received .file-attachment:hover {
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.message-item.received .file-attachment .file-icon {
    color: #00a884;
}

.message-item.received .file-attachment .file-name {
    color: #111b21;
}

.message-item.received .file-attachment .file-size {
    color: #667781;
    background: rgba(0, 168, 132, 0.1);
}

/* Loading animation for file downloads */
.bx-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* File attachment click feedback */
.file-attachment:active {
    transform: scale(0.98);
    transition: transform 0.1s ease;
}

/* Call Modal Styles */
.call-modal {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.call-header {
    background: linear-gradient(135deg, #00a884, #008f6f);
    color: white;
    border-bottom: none;
    border-radius: 16px 16px 0 0;
    padding: 20px 24px;
}

.call-header .modal-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.call-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.call-body {
    padding: 40px 24px;
    text-align: center;
    background: #f8f9fa;
}

.call-user-info {
    margin-bottom: 30px;
}

.call-avatar {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.call-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #00a884;
    box-shadow: 0 8px 24px rgba(0, 168, 132, 0.3);
}

.call-status-indicator {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 40px;
    height: 40px;
    background: #00a884;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    border: 3px solid white;
    animation: pulse 2s infinite;
}

.call-status-indicator.incoming {
    background: #28a745;
    animation: ring 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes ring {
    0% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.1) rotate(-5deg); }
    50% { transform: scale(1) rotate(0deg); }
    75% { transform: scale(1.1) rotate(5deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.call-user-info h4 {
    color: #111b21;
    font-weight: 600;
    margin-bottom: 8px;
}

.call-user-info p {
    color: #667781;
    font-size: 16px;
    margin: 0;
}

.call-timer {
    font-size: 24px;
    font-weight: 600;
    color: #00a884;
    margin-bottom: 20px;
}

.call-footer {
    background: #ffffff;
    border-top: 1px solid #e9edef;
    border-radius: 0 0 16px 16px;
    padding: 24px;
}

.call-controls {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.call-control-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.3s ease;
    border: 2px solid;
}

.call-control-btn:hover {
    transform: scale(1.1);
}

.call-control-btn.btn-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.call-control-btn.btn-success {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

/* Video Call Styles */
.video-container {
    position: relative;
    width: 100%;
    height: 400px;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
}

.main-video {
    width: 100%;
    height: 100%;
    position: relative;
}

.main-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.local-video {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 120px;
    height: 90px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid white;
    background: #333;
}

.local-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.camera-toggle {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    font-size: 12px;
    padding: 0;
}

/* Incoming Call Styles */
.incoming-call .call-header {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.incoming-call-avatar {
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

/* Notification Styles */
.notification-btn {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    animation: pulse 2s infinite;
}

.notification-panel {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    border: 1px solid #e9edef;
    z-index: 1050;
    overflow: hidden;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e9edef;
    background: #f8f9fa;
}

.notification-header h6 {
    color: #111b21;
    font-weight: 600;
    margin: 0;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
    padding: 0;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f2f5;
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: rgba(0, 168, 132, 0.05);
    border-left: 3px solid #00a884;
}

.notification-item.unread:hover {
    background: rgba(0, 168, 132, 0.1);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0;
}

.notification-icon.message {
    background: #00a884;
}

.notification-icon.call {
    background: #28a745;
}

.notification-icon.system {
    background: #6c757d;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    color: #111b21;
    margin-bottom: 4px;
    font-size: 14px;
}

.notification-message {
    color: #667781;
    font-size: 13px;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-time {
    color: #8e9ba4;
    font-size: 12px;
}

.notification-footer {
    display: flex;
    justify-content: space-between;
    padding: 12px 20px;
    border-top: 1px solid #e9edef;
    background: #f8f9fa;
}

.notification-footer .btn {
    font-size: 12px;
    padding: 6px 12px;
}

/* Empty notification state */
.notification-empty {
    text-align: center;
    padding: 40px 20px;
    color: #8e9ba4;
}

.notification-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.notification-empty p {
    margin: 0;
    font-size: 14px;
}

/* Scrollbar Styling */
.conversations-list::-webkit-scrollbar,
.chat-messages-container::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-track,
.chat-messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.conversations-list::-webkit-scrollbar-thumb,
.chat-messages-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.chat-messages-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-chat-container {
        height: calc(100vh - 150px);
        border-radius: 0;
    }
    
    .chat-sidebar-modern {
        width: 280px;
    }
    
    .message-content {
        max-width: 80%;
    }
    
    .chat-header-modern {
        padding: 12px 16px;
    }
    
    .chat-messages-container {
        padding: 16px;
    }
    
    .chat-input-container {
        padding: 12px 16px;
    }
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
    .modern-chat-container {
        background: #1a1a1a;
        border-color: #333;
    }
    
    .chat-main-area {
        background: #1a1a1a;
    }
    
    .chat-header-modern {
        background: #2d2d2d;
        border-color: #333;
    }
    
    .chat-input-container {
        background: #2d2d2d;
        border-color: #333;
    }
    
    .message-bubble.received {
        background: #2d2d2d;
        color: #fff;
    }
    
    .input-wrapper {
        background: #2d2d2d;
    }
    
    .message-input-wrapper {
        background: #1a1a1a;
    }
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Send message functionality
    $('#sendMessageBtn').on('click', function() {
        sendMessage();
    });

    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Tab switching
    $('.nav-tab').on('click', function() {
        $('.nav-tab').removeClass('active');
        $(this).addClass('active');
    });

    // Search functionality
    $('#userSearchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm.length > 0) {
            $('#clearSearch').show();
        } else {
            $('#clearSearch').hide();
        }
        
        $('.conversation-item').each(function() {
            const userName = $(this).find('.conversation-name').text().toLowerCase();
            const userRole = $(this).find('.conversation-preview').text().toLowerCase();
            
            if (userName.includes(searchTerm) || userRole.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Show no results message if no users match
        const visibleUsers = $('.conversation-item:visible').length;
        if (searchTerm.length > 0 && visibleUsers === 0) {
            if ($('.no-search-results').length === 0) {
                $('.conversations-list').append(`
                    <div class="no-search-results text-center py-4">
                        <i class='bx bx-search fs-1 text-muted'></i>
                        <p class="text-muted mt-2">No users found matching "${searchTerm}"</p>
                    </div>
                `);
            }
        } else {
            $('.no-search-results').remove();
        }
    });
    
    // Clear search
    $('#clearSearch').on('click', function() {
        $('#userSearchInput').val('');
        $('.conversation-item').show();
        $('.no-search-results').remove();
        $(this).hide();
    });
    
    // Conversation switching
    $('.conversation-item').on('click', function() {
        $('.conversation-item').removeClass('active');
        $(this).addClass('active');
        
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        const userPhone = $(this).data('user-phone');
        const userStatus = $(this).data('user-status');
        const isOnline = $(this).data('user-online');
        const userAvatar = $(this).find('img').attr('src');
        
        // Update chat header
        $('#chat-user-name').text(userName || '');
        $('#chat-user-phone').text(formatPhoneDisplay(userPhone || ''));
        $('#chat-user-avatar').attr('src', userAvatar || '').attr('alt', userName || '');
        
        // Update status based on actual user status
        if (isOnline === true) {
            $('#chat-user-status').html('<i class="bx bxs-circle"></i> Active now').removeClass('text-muted').addClass('text-success');
        } else {
            $('#chat-user-status').html('<i class="bx bxs-circle"></i> Offline').removeClass('text-success').addClass('text-muted');
        }
        
        $('#current-chat-user').show();
        $('#no-chat-selected').hide();
        
        // Store current chat user
        window.currentChatUser = {
            id: userId,
            name: userName,
            phone: userPhone,
            status: userStatus,
            isOnline: isOnline
        };
        
        // Hide welcome message and show messages
        $('#welcomeMessage').hide();
        
        // Load messages for this user
        loadMessages(userId);
    });

    function sendMessage() {
        const message = $('#messageInput').val().trim();
        if (!message || !window.currentChatUser) return;

        // Add message to chat with initial "sent" status
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const messageHtml = `
            <div class="message-item sent">
                <div class="message-content">
                    <div class="message-bubble sent">
                        <p>${escapeHtml(message)}</p>
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        <span class="message-status sent">
                            <i class='bx bx-check'></i>
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        $('.messages-wrapper').append(messageHtml);
        
        // Clear input
        $('#messageInput').val('');
        
        // Scroll to bottom
        $('.chat-messages-container').scrollTop($('.chat-messages-container')[0].scrollHeight);
        
        // Send message to server
        $.ajax({
            url: '/chat/send',
            method: 'POST',
            data: {
                receiver_id: window.currentChatUser.id,
                message: message,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update message status to delivered
                    setTimeout(() => {
                        updateMessageStatus('delivered');
                    }, 2000);
                    
                    // Update message status to read
                    setTimeout(() => {
                        updateMessageStatus('read');
                    }, 4000);
                }
            },
            error: function() {
                console.error('Failed to send message');
            }
        });
    }
    
    function loadMessages(userId) {
        $.ajax({
            url: `/chat/messages/${userId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    // Clear welcome message and add messages
                    $('.messages-wrapper').empty();
                    
                    response.messages.forEach(function(msg) {
                        const isSent = msg.sender_id == {{ auth()->id() }};
                        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        let messageHtml = '';
                        if (isSent) {
                            messageHtml = `
                                <div class="message-item sent">
                                    <div class="message-content">
                                        <div class="message-bubble sent">
                                            ${msg.message ? `<p>${escapeHtml(msg.message)}</p>` : ''}
                                            ${msg.file_path ? `
                                                <div class="file-attachment" onclick="downloadFile(${msg.id})">
                                                    <i class='${getFileIcon(msg.file_name)} file-icon'></i>
                                                    <span class="file-name">${escapeHtml(msg.file_name)}</span>
                                                    <span class="file-size">${msg.file_size}</span>
                                                </div>
                                            ` : ''}
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-time">${time}</span>
                                            <span class="message-status ${msg.is_read ? 'read' : 'delivered'}">
                                                <i class='bx bx-check-double'></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            messageHtml = `
                                <div class="message-item received">
                                    <div class="message-avatar">
                                        <img src="assets/images/avatars/avatar-${(msg.sender_id % 6) + 1}.png" alt="${msg.sender.name}" />
                                    </div>
                                    <div class="message-content">
                                        <div class="message-bubble received">
                                            ${msg.message ? `<p>${escapeHtml(msg.message)}</p>` : ''}
                                            ${msg.file_path ? `
                                                <div class="file-attachment" onclick="downloadFile(${msg.id})">
                                                    <i class='${getFileIcon(msg.file_name)} file-icon'></i>
                                                    <span class="file-name">${escapeHtml(msg.file_name)}</span>
                                                    <span class="file-size">${msg.file_size}</span>
                                                </div>
                                            ` : ''}
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-time">${time}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('.messages-wrapper').append(messageHtml);
                    });
                    
                    // Scroll to bottom
                    $('.chat-messages-container').scrollTop($('.chat-messages-container')[0].scrollHeight);
                }
            },
            error: function() {
                console.error('Failed to load messages');
            }
        });
    }
    
    function getFileIcon(fileName) {
        const extension = fileName.split('.').pop().toLowerCase();
        
        switch(extension) {
            case 'pdf':
                return 'bx bx-file-pdf';
            case 'doc':
            case 'docx':
                return 'bx bx-file-doc';
            case 'xls':
            case 'xlsx':
                return 'bx bx-file-spreadsheet';
            case 'txt':
                return 'bx bx-file-txt';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'svg':
                return 'bx bx-image';
            case 'zip':
            case 'rar':
            case '7z':
                return 'bx bx-archive';
            case 'mp3':
            case 'wav':
            case 'ogg':
                return 'bx bx-music';
            case 'mp4':
            case 'avi':
            case 'mov':
                return 'bx bx-video';
            default:
                return 'bx bx-file';
        }
    }
    

    
    function updateMessageStatus(status) {
        const lastMessage = $('.message-item.sent').last();
        const statusElement = lastMessage.find('.message-status');
        
        if (status === 'delivered') {
            statusElement.removeClass('sent').addClass('delivered');
            statusElement.html('<i class="bx bx-check-double"></i>');
        } else if (status === 'read') {
            statusElement.removeClass('delivered').addClass('read');
            statusElement.html('<i class="bx bx-check-double"></i>');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // File attachment functionality
    $('#attachmentBtn').on('click', function() {
        $('#fileInput').click();
    });
    
    $('#fileInput').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                this.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                alert('File type not supported. Please select a valid document or image.');
                this.value = '';
                return;
            }
            
            // Show file preview
            showFilePreview(file);
        }
    });
    
    $('#removeFileBtn').on('click', function() {
        removeFilePreview();
    });
    
    function showFilePreview(file) {
        const fileSize = formatFileSize(file.size);
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        $('#fileName').text(file.name);
        $('#fileSize').text(fileSize);
        
        // Set appropriate file icon
        $('#filePreviewIcon').attr('class', `${getFileIcon(file.name)} file-icon`);
        
        // Set file type for icon color
        $('#filePreview').attr('data-file-type', fileExtension);
        
        // Show preview
        $('#filePreview').show();
        
        // Store file for sending
        window.selectedFile = file;
    }
    
    function removeFilePreview() {
        $('#filePreview').hide();
        $('#fileInput').val('');
        window.selectedFile = null;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Update send message function to handle files
    function sendMessage() {
        const message = $('#messageInput').val().trim();
        const file = window.selectedFile;
        
        if ((!message && !file) || !window.currentChatUser) return;

        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        if (file) {
            // Send file message
            sendFileMessage(file, message, time);
        } else {
            // Send text message (existing functionality)
            sendTextMessage(message, time);
        }
    }
    
    function sendFileMessage(file, message, time) {
        const formData = new FormData();
        formData.append('receiver_id', window.currentChatUser.id);
        formData.append('message', message || '');
        formData.append('file', file);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        
        // Show file message in chat immediately
        const fileMessageHtml = `
            <div class="message-item sent">
                <div class="message-content">
                    <div class="message-bubble sent">
                        ${message ? `<p>${escapeHtml(message)}</p>` : ''}
                        <div class="file-attachment">
                            <i class='${getFileIcon(file.name)} file-icon'></i>
                            <span class="file-name">${escapeHtml(file.name)}</span>
                            <span class="file-size">${formatFileSize(file.size)}</span>
                        </div>
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        <span class="message-status sent">
                            <i class='bx bx-check'></i>
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        $('.messages-wrapper').append(fileMessageHtml);
        
        // Clear input and file
        $('#messageInput').val('');
        removeFilePreview();
        
        // Scroll to bottom
        $('.chat-messages-container').scrollTop($('.chat-messages-container')[0].scrollHeight);
        
        // Send to server
        $.ajax({
            url: '/chat/send',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Update message status
                    setTimeout(() => {
                        updateMessageStatus('delivered');
                    }, 2000);
                    
                    setTimeout(() => {
                        updateMessageStatus('read');
                    }, 4000);
                }
            },
            error: function() {
                console.error('Failed to send file');
            }
        });
    }
    
    function sendTextMessage(message, time) {
        const messageHtml = `
            <div class="message-item sent">
                <div class="message-content">
                    <div class="message-bubble sent">
                        <p>${escapeHtml(message)}</p>
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        <span class="message-status sent">
                            <i class='bx bx-check'></i>
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        $('.messages-wrapper').append(messageHtml);
        
        // Clear input
        $('#messageInput').val('');
        
        // Scroll to bottom
        $('.chat-messages-container').scrollTop($('.chat-messages-container')[0].scrollHeight);
        
        // Send to server
        $.ajax({
            url: '/chat/send',
            method: 'POST',
            data: {
                receiver_id: window.currentChatUser.id,
                message: message,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    setTimeout(() => {
                        updateMessageStatus('delivered');
                    }, 2000);
                    
                    setTimeout(() => {
                        updateMessageStatus('read');
                    }, 4000);
                }
            },
            error: function() {
                console.error('Failed to send message');
            }
        });
    }
    
    // Auto-scroll to bottom on load
    $('.chat-messages-container').scrollTop($('.chat-messages-container')[0].scrollHeight);
    
    // Update user status periodically to make it more realistic
    function updateUserStatus() {
        $('.conversation-item').each(function() {
            const $item = $(this);
            const userId = $item.data('user-id');
            const currentStatus = $item.data('user-online');
            
            // Simulate status changes (10% chance of status change every 30 seconds)
            if (Math.random() < 0.1) {
                const newStatus = !currentStatus;
                const statusText = newStatus ? 'Online' : 'Offline';
                const statusClass = newStatus ? 'online' : 'offline';
                
                // Update data attributes
                $item.data('user-status', statusText);
                $item.data('user-online', newStatus);
                
                // Update visual indicators
                $item.find('.status-indicator').removeClass('online offline').addClass(statusClass);
                $item.find('.conversation-time').text(statusText);
                
                // Update chat header if this user is currently selected
                if ($item.hasClass('active') && window.currentChatUser && window.currentChatUser.id === userId) {
                    if (newStatus) {
                        $('#chat-user-status').html('<i class="bx bxs-circle"></i> Active now').removeClass('text-muted').addClass('text-success');
                    } else {
                        $('#chat-user-status').html('<i class="bx bxs-circle"></i> Offline').removeClass('text-success').addClass('text-muted');
                    }
                    window.currentChatUser.status = statusText;
                    window.currentChatUser.isOnline = newStatus;
                }
            }
        });
    }
    
    // Update status every 30 seconds
    setInterval(updateUserStatus, 30000);
    
    // Add notifications for various events
    function addChatNotification(type, title, message) {
        const timeAgo = 'Just now';
        addNotification(type, title, message, timeAgo);
    }
    
    // Notify when user comes online
    function notifyUserOnline(userName) {
        addChatNotification('system', 'User Online', `${userName} is now online`);
    }
    
    // Notify when user goes offline
    function notifyUserOffline(userName) {
        addChatNotification('system', 'User Offline', `${userName} is now offline`);
    }
    
    // Notify for missed calls
    function notifyMissedCall(userName, callType) {
        addChatNotification('call', 'Missed Call', `${userName} tried to ${callType.toLowerCase()} you`);
    }
    
    // Notify for new messages (when not in active chat)
    function notifyNewMessage(senderName, message) {
        if (!window.currentChatUser || window.currentChatUser.name !== senderName) {
            addChatNotification('message', 'New Message', `${senderName}: ${message.substring(0, 50)}${message.length > 50 ? '...' : ''}`);
        }
    }
    
    // Enhanced status update function with notifications
    function updateUserStatus() {
        $('.conversation-item').each(function() {
            const $item = $(this);
            const userId = $item.data('user-id');
            const userName = $item.data('user-name');
            const currentStatus = $item.data('user-online');
            
            // Simulate status changes (10% chance of status change every 30 seconds)
            if (Math.random() < 0.1) {
                const newStatus = !currentStatus;
                const statusText = newStatus ? 'Online' : 'Offline';
                const statusClass = newStatus ? 'online' : 'offline';
                
                // Update data attributes
                $item.data('user-status', statusText);
                $item.data('user-online', newStatus);
                
                // Update visual indicators
                $item.find('.status-indicator').removeClass('online offline').addClass(statusClass);
                $item.find('.conversation-time').text(statusText);
                
                // Add notification for status change
                if (newStatus) {
                    notifyUserOnline(userName);
                } else {
                    notifyUserOffline(userName);
                }
                
                // Update chat header if this user is currently selected
                if ($item.hasClass('active') && window.currentChatUser && window.currentChatUser.id === userId) {
                    if (newStatus) {
                        $('#chat-user-status').html('<i class="bx bxs-circle"></i> Active now').removeClass('text-muted').addClass('text-success');
                    } else {
                        $('#chat-user-status').html('<i class="bx bxs-circle"></i> Offline').removeClass('text-success').addClass('text-muted');
                    }
                    window.currentChatUser.status = statusText;
                    window.currentChatUser.isOnline = newStatus;
                }
            }
        });
    }
});

// Call Functionality
let callTimer = null;
let callStartTime = null;
let isMuted = false;
let isSpeakerOn = false;
let isCameraOn = true;
let localStream = null;
let remoteStream = null;

// Call button event listeners
$('#voiceCallBtn').on('click', function() {
    if (!window.currentChatUser || !window.currentChatUser.name) {
        alert('Please select a user to call');
        return;
    }
    initiateVoiceCall();
});

$('#videoCallBtn').on('click', function() {
    if (!window.currentChatUser || !window.currentChatUser.name) {
        alert('Please select a user to call');
        return;
    }
    initiateVideoCall();
});

function initiateVoiceCall() {
    const userName = window.currentChatUser.name || '';
    const userPhone = $('.conversation-item.active').data('user-phone') || '';
    const userAvatar = $('.conversation-item.active img').attr('src') || '';
    
    $('#voiceCallUserName').text(userName);
    $('#voiceCallAvatar').attr('src', userAvatar);
    $('#voiceCallStatus').text(`Calling ${formatPhoneDisplay(userPhone)}...`);
    $('#voiceCallTimer').hide();
    
    $('#voiceCallModal').modal('show');
    
    // Simulate call connection after 3 seconds
    setTimeout(() => {
        $('#voiceCallStatus').text('Connected');
        startCallTimer('voice');
    }, 3000);
}

function initiateVideoCall() {
    const userName = window.currentChatUser.name || '';
    const userPhone = $('.conversation-item.active').data('user-phone') || '';
    const userAvatar = $('.conversation-item.active img').attr('src') || '';
    
    $('#videoCallUserName').text(userName);
    $('#videoCallAvatar').attr('src', userAvatar);
    $('#videoCallStatus').text(`Calling ${formatPhoneDisplay(userPhone)}...`);
    $('#videoCallTimer').hide();
    
    $('#videoCallModal').modal('show');
    
    // Request camera and microphone access
    navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        .then(stream => {
            localStream = stream;
            document.getElementById('localVideoElement').srcObject = stream;
            
            // Simulate call connection after 3 seconds
            setTimeout(() => {
                $('#videoCallStatus').text('Connected');
                $('#videoOverlay').hide();
                startCallTimer('video');
            }, 3000);
        })
        .catch(err => {
            console.error('Error accessing media devices:', err);
            alert('Unable to access camera/microphone. Please check permissions.');
        });
}

function startCallTimer(type) {
    callStartTime = Date.now();
    $(`#${type}CallTimer`).show();
    
    callTimer = setInterval(() => {
        const elapsed = Date.now() - callStartTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        $(`#${type}CallTime`).text(timeString);
    }, 1000);
}

function endCall(type) {
    if (callTimer) {
        clearInterval(callTimer);
        callTimer = null;
    }
    
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    $(`#${type}CallModal`).modal('hide');
    
    // Show call ended notification
    showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} call ended`);
}

// Call control event listeners
$('#muteVoiceBtn, #muteVideoBtn').on('click', function() {
    isMuted = !isMuted;
    const icon = isMuted ? 'bx-microphone-off' : 'bx-microphone';
    $(this).find('i').attr('class', `bx ${icon}`);
    
    if (localStream) {
        localStream.getAudioTracks().forEach(track => {
            track.enabled = !isMuted;
        });
    }
    
    showNotification(isMuted ? 'Microphone muted' : 'Microphone unmuted');
});

$('#speakerVoiceBtn, #speakerVideoBtn').on('click', function() {
    isSpeakerOn = !isSpeakerOn;
    const icon = isSpeakerOn ? 'bx-volume-mute' : 'bx-volume-full';
    $(this).find('i').attr('class', `bx ${icon}`);
    
    showNotification(isSpeakerOn ? 'Speaker off' : 'Speaker on');
});

$('#cameraBtn').on('click', function() {
    isCameraOn = !isCameraOn;
    const icon = isCameraOn ? 'bx-camera' : 'bx-camera-off';
    $(this).find('i').attr('class', `bx ${icon}`);
    
    if (localStream) {
        localStream.getVideoTracks().forEach(track => {
            track.enabled = isCameraOn;
        });
    }
    
    showNotification(isCameraOn ? 'Camera on' : 'Camera off');
});

$('#endVoiceCallBtn').on('click', function() {
    endCall('voice');
});

$('#endVideoCallBtn').on('click', function() {
    endCall('video');
});

// Phone number formatting helper
function formatPhoneDisplay(phone) {
    // Check if phone is valid
    if (!phone || typeof phone !== 'string') {
        return '';
    }
    
    try {
        // Remove any non-digit characters
        const cleanPhone = phone.replace(/\D/g, '');
        
        // Format Tanzania phone numbers
        if (cleanPhone.startsWith('255') && cleanPhone.length === 12) {
            return `+${cleanPhone.substring(0, 3)} ${cleanPhone.substring(3, 6)} ${cleanPhone.substring(6, 9)} ${cleanPhone.substring(9)}`;
        }
        
        // Format other numbers
        if (cleanPhone.length === 10) {
            return `+1 ${cleanPhone.substring(0, 3)} ${cleanPhone.substring(3, 6)} ${cleanPhone.substring(6)}`;
        }
        
        // Return original if no formatting applied
        return phone;
    } catch (error) {
        console.error('Error formatting phone number:', error, phone);
        return phone || '';
    }
}

// Incoming call simulation (for demo purposes)
function simulateIncomingCall() {
    // Check if any call modal is already open
    if ($('#voiceCallModal').hasClass('show') || 
        $('#videoCallModal').hasClass('show') || 
        $('#incomingCallModal').hasClass('show')) {
        console.log('Call already in progress, skipping incoming call simulation');
        return;
    }
    
    // Get random user from the conversation list
    const conversationItems = $('.conversation-item');
    if (conversationItems.length === 0) {
        console.log('No users available for incoming call simulation');
        return;
    }
    
    const randomIndex = Math.floor(Math.random() * conversationItems.length);
    const randomUserElement = conversationItems.eq(randomIndex);
    
    const userName = randomUserElement.data('user-name') || '';
    const userPhone = randomUserElement.data('user-phone') || '';
    const userAvatar = randomUserElement.find('img').attr('src') || '';
    const callTypes = ['Voice Call', 'Video Call'];
    const randomType = callTypes[Math.floor(Math.random() * callTypes.length)];
    
    $('#incomingCallUserName').text(userName);
    $('#incomingCallType').text(`${randomType} from ${formatPhoneDisplay(userPhone)}`);
    $('#incomingCallAvatar').attr('src', userAvatar);
    
    $('#incomingCallModal').modal('show');
    
    console.log(`Simulated incoming ${randomType} from ${userName} (${formatPhoneDisplay(userPhone)})`);
}

// Accept/Reject call handlers
$('#acceptCallBtn').on('click', function() {
    $('#incomingCallModal').modal('hide');
    const callType = $('#incomingCallType').text();
    
    if (callType === 'Video Call') {
        initiateVideoCall();
    } else {
        initiateVoiceCall();
    }
});

$('#rejectCallBtn').on('click', function() {
    $('#incomingCallModal').modal('hide');
    const callerName = $('#incomingCallUserName').text();
    const callType = $('#incomingCallType').text().split(' ')[0];
    notifyMissedCall(callerName, callType);
    showNotification('Call rejected');
});

// Notification function
function showNotification(message) {
    // Create a simple notification
    const notification = $(`
        <div class="alert alert-info alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class='bx bx-info-circle me-2'></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.alert('close');
    }, 3000);
}

// Simulate incoming call every 30 seconds (for demo) - DISABLED
// setInterval(simulateIncomingCall, 30000);

// Manual incoming call simulation (for testing)
// Uncomment the line below to enable automatic incoming calls
// setInterval(simulateIncomingCall, 30000);

// Test incoming call with keyboard shortcut (Ctrl + I)
$(document).on('keydown', function(e) {
    if (e.ctrlKey && e.key === 'i') {
        e.preventDefault();
        simulateIncomingCall();
    }
});

// Add test button to chat header (for development only)
$('.chat-header-right').append(`
    <button class="btn btn-sm btn-outline-warning" id="testIncomingCall" title="Test Incoming Call (Ctrl+I)" style="display: none;">
        <i class='bx bx-phone-incoming'></i>
    </button>
`);

// Show test button in development mode
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    $('#testIncomingCall').show();
}

$('#testIncomingCall').on('click', function() {
    simulateIncomingCall();
});

// Add a toggle for incoming call simulation
let incomingCallSimulationEnabled = false;

// Add toggle button to sidebar (for development only)
$('.chat-sidebar-modern .sidebar-header').append(`
    <div class="mt-2" id="simulationToggle" style="display: none;">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="enableIncomingCalls">
            <label class="form-check-label small text-muted" for="enableIncomingCalls">
                Enable Incoming Call Simulation
            </label>
        </div>
    </div>
`);

// Show toggle in development mode
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    $('#simulationToggle').show();
}

$('#enableIncomingCalls').on('change', function() {
    incomingCallSimulationEnabled = this.checked;
    if (incomingCallSimulationEnabled) {
        console.log('Incoming call simulation enabled');
        // Start simulation every 60 seconds when enabled
        window.incomingCallInterval = setInterval(simulateIncomingCall, 60000);
    } else {
        console.log('Incoming call simulation disabled');
        if (window.incomingCallInterval) {
            clearInterval(window.incomingCallInterval);
            window.incomingCallInterval = null;
        }
    }
});

// Notification System
let notifications = [];
let unreadCount = 0;

// Initialize notification system
function initNotificationSystem() {
    // Load existing notifications from localStorage
    const savedNotifications = localStorage.getItem('chatNotifications');
    if (savedNotifications) {
        notifications = JSON.parse(savedNotifications);
        updateNotificationCount();
        renderNotifications();
    }
    
    // Add sample notifications for demo
    addSampleNotifications();
}

// Add sample notifications for demonstration
function addSampleNotifications() {
    if (notifications.length === 0) {
        addNotification('message', 'New Message', 'John Doe sent you a message', '2 min ago');
        addNotification('call', 'Missed Call', 'Jane Smith tried to call you', '5 min ago');
        addNotification('system', 'System Update', 'Chat system has been updated', '1 hour ago');
    }
}

// Add a new notification
function addNotification(type, title, message, time) {
    const notification = {
        id: Date.now() + Math.random(),
        type: type,
        title: title,
        message: message,
        time: time,
        timestamp: Date.now(),
        isRead: false
    };
    
    notifications.unshift(notification);
    unreadCount++;
    
    // Save to localStorage
    localStorage.setItem('chatNotifications', JSON.stringify(notifications));
    
    // Update UI
    updateNotificationCount();
    renderNotifications();
    
    // Show desktop notification if supported
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: message,
            icon: '/favicon.ico'
        });
    }
}

// Update notification count badge
function updateNotificationCount() {
    const badge = $('#notificationCount');
    const navbarBadge = $('#navbarNotificationCount');
    
    // Update chat notification badge
    if (unreadCount > 0) {
        badge.text(unreadCount > 99 ? '99+' : unreadCount).show();
    } else {
        badge.hide();
    }
    
    // Update navbar notification count (combine system + chat notifications)
    const systemNotifications = parseInt($('#navbarNotificationCount').data('system-count') || 0);
    const totalNotifications = systemNotifications + unreadCount;
    
    if (totalNotifications > 0) {
        navbarBadge.text(totalNotifications > 99 ? '99+' : totalNotifications).show();
    } else {
        navbarBadge.hide();
    }
    
    // Update navbar chat notifications
updateNavbarChatNotifications();
}

// Update navbar chat notifications
function updateNavbarChatNotifications() {
    const container = $('#navbarChatNotifications');
    
    if (notifications.length === 0) {
        container.html(`
            <a class="dropdown-item" href="javascript:;">
                <div class="d-flex align-items-center">
                    <div class="notify bg-light-secondary text-secondary"><i class="bx bx-message"></i></div>
                    <div class="flex-grow-1">
                        <h6 class="msg-name">No chat notifications</h6>
                    </div>
                </div>
            </a>
        `);
        return;
    }
    
    // Show only the latest 3 unread notifications
    const unreadNotifications = notifications.filter(n => !n.isRead).slice(0, 3);
    
    if (unreadNotifications.length === 0) {
        container.html(`
            <a class="dropdown-item" href="javascript:;">
                <div class="d-flex align-items-center">
                    <div class="notify bg-light-secondary text-secondary"><i class="bx bx-message"></i></div>
                    <div class="flex-grow-1">
                        <h6 class="msg-name">No unread chat notifications</h6>
                    </div>
                </div>
            </a>
        `);
        return;
    }
    
    const notificationHtml = unreadNotifications.map(notification => `
        <a class="dropdown-item" href="javascript:;" onclick="openChatAndMarkRead(${notification.id})">
            <div class="d-flex align-items-center">
                <div class="notify bg-light-${getNotificationColor(notification.type)} text-${getNotificationColor(notification.type)}">
                    <i class="bx ${getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="msg-name">${notification.title} <span class="msg-time float-end">${notification.time}</span></h6>
                    <p class="msg-info">${notification.message}</p>
                </div>
            </div>
        </a>
    `).join('');
    
    container.html(notificationHtml);
}

// Get notification color for navbar
function getNotificationColor(type) {
    switch(type) {
        case 'message': return 'primary';
        case 'call': return 'success';
        case 'system': return 'secondary';
        default: return 'info';
    }
}

// Open chat and mark notification as read
function openChatAndMarkRead(notificationId) {
    // Mark notification as read
    markNotificationAsRead(notificationId);
    
    // Open chat if not already open
    if (window.location.pathname !== '/chat') {
        window.location.href = '/chat';
    }
    
    // Close navbar dropdown
    $('.dropdown-menu').removeClass('show');
}

// Render notifications in the panel
function renderNotifications() {
    const container = $('#notificationList');
    
    if (notifications.length === 0) {
        container.html(`
            <div class="notification-empty">
                <i class='bx bx-bell-off'></i>
                <p>No notifications yet</p>
            </div>
        `);
        return;
    }
    
    const notificationHtml = notifications.map(notification => `
        <div class="notification-item ${notification.isRead ? '' : 'unread'}" data-notification-id="${notification.id}">
            <div class="notification-icon ${notification.type}">
                <i class='bx ${getNotificationIcon(notification.type)}'></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${notification.time}</div>
            </div>
        </div>
    `).join('');
    
    container.html(notificationHtml);
}

// Get icon for notification type
function getNotificationIcon(type) {
    switch(type) {
        case 'message': return 'bx-message-square';
        case 'call': return 'bx-phone';
        case 'system': return 'bx-cog';
        default: return 'bx-bell';
    }
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    const notification = notifications.find(n => n.id === notificationId);
    if (notification && !notification.isRead) {
        notification.isRead = true;
        unreadCount--;
        localStorage.setItem('chatNotifications', JSON.stringify(notifications));
        updateNotificationCount();
        renderNotifications();
    }
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    notifications.forEach(notification => {
        notification.isRead = true;
    });
    unreadCount = 0;
    localStorage.setItem('chatNotifications', JSON.stringify(notifications));
    updateNotificationCount();
    renderNotifications();
}

// Clear all notifications
function clearAllNotifications() {
    notifications = [];
    unreadCount = 0;
    localStorage.setItem('chatNotifications', JSON.stringify(notifications));
    updateNotificationCount();
    renderNotifications();
}

// Notification event handlers
$('#notificationBtn').on('click', function() {
    $('#notificationPanel').toggle();
});

$('#closeNotificationPanel').on('click', function() {
    $('#notificationPanel').hide();
});

$(document).on('click', '.notification-item', function() {
    const notificationId = $(this).data('notification-id');
    markNotificationAsRead(notificationId);
});

$('#markAllRead').on('click', function() {
    markAllNotificationsAsRead();
});

$('#clearAllNotifications').on('click', function() {
    if (confirm('Are you sure you want to clear all notifications?')) {
        clearAllNotifications();
    }
});

// Close notification panel when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#notificationPanel, #notificationBtn').length) {
        $('#notificationPanel').hide();
    }
});

// Request notification permission
if ('Notification' in window) {
    Notification.requestPermission();
}

// Initialize notification system when document is ready
$(document).ready(function() {
    initNotificationSystem();
    
    // Set up system notification count
    const systemCount = parseInt($('#navbarNotificationCount').text()) || 0;
    $('#navbarNotificationCount').data('system-count', systemCount);
    
    // Update navbar notifications on page load
    updateNavbarChatNotifications();
});

// Make functions globally accessible
window.openChatAndMarkRead = openChatAndMarkRead;
window.markNotificationAsRead = markNotificationAsRead;

// Global functions that need to be accessible from HTML onclick attributes
window.downloadFile = function(messageId) {
    // Show loading indicator
    const fileElement = event.target.closest('.file-attachment');
    if (fileElement) {
        const originalContent = fileElement.innerHTML;
        const fileName = fileElement.querySelector('.file-name').textContent;
        const fileExtension = fileName.split('.').pop().toLowerCase();
        
        // Determine if file opens in browser or downloads
        const browserTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'html', 'htm'];
        const isBrowserType = browserTypes.includes(fileExtension);
        
        const actionText = isBrowserType ? 'Opening...' : 'Downloading...';
        fileElement.innerHTML = `<i class="bx bx-loader-alt bx-spin"></i> ${actionText}`;
        fileElement.style.opacity = '0.7';
        
        // Store original content for restoration
        fileElement.setAttribute('data-original-content', originalContent);
    }
    
    // Open/download the file
    const downloadWindow = window.open(`/chat/download/${messageId}`, '_blank');
    
    // Restore original content after a shorter delay for better UX
    setTimeout(() => {
        if (fileElement) {
            const storedContent = fileElement.getAttribute('data-original-content');
            if (storedContent) {
                fileElement.innerHTML = storedContent;
                fileElement.style.opacity = '1';
                fileElement.removeAttribute('data-original-content');
            }
        }
    }, 1200); // Further reduced for better responsiveness
    
    // Add click handler to restore content if user clicks again
    if (fileElement) {
        fileElement.addEventListener('click', function restoreContent() {
            const storedContent = this.getAttribute('data-original-content');
            if (storedContent) {
                this.innerHTML = storedContent;
                this.style.opacity = '1';
                this.removeAttribute('data-original-content');
                this.removeEventListener('click', restoreContent);
            }
        }, { once: true });
        
        // Add a small success indicator after a brief delay
        setTimeout(() => {
            if (fileElement && fileElement.getAttribute('data-original-content')) {
                const currentContent = fileElement.innerHTML;
                if (currentContent.includes('bx-spin')) {
                    // Show success state briefly
                    fileElement.innerHTML = '<i class="bx bx-check-circle" style="color: #28a745;"></i> Complete';
                    fileElement.style.opacity = '0.8';
                    
                    // Then restore original content
                    setTimeout(() => {
                        const storedContent = fileElement.getAttribute('data-original-content');
                        if (storedContent) {
                            fileElement.innerHTML = storedContent;
                            fileElement.style.opacity = '1';
                            fileElement.removeAttribute('data-original-content');
                        }
                    }, 800);
                }
            }
        }, 1000);
    }
};
</script>
@endpush
