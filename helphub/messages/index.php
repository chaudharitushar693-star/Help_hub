<?php
$pageTitle = "Messages & Direct Chat | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
require_login();
$activeConvParam = (int)($_GET['conv'] ?? 0);
?>

<main class="chat-layout">
  <!-- LEFT: CONVERSATIONS SIDEBAR -->
  <aside class="chat-sidebar" id="chatSidebar">
    <div class="chat-search-bar">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2 style="font-size:20px; font-family:var(--font-heading);">Messages</h2>
        <span class="pill" style="background:var(--primary-light); color:var(--primary); font-size:11.5px;">Live Chat</span>
      </div>
      <input type="text" id="convSearch" placeholder="Search conversations...">
    </div>

    <div class="conversation-list" id="conversationsContainer">
      <div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13.5px;">
        Loading conversations...
      </div>
    </div>
  </aside>

  <!-- RIGHT: ACTIVE CHAT SCREEN -->
  <section class="chat-main" id="chatMain">
    <div id="noChatSelected" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text-muted); text-align:center; padding:30px;">
      <span style="font-size:52px; margin-bottom:12px;">💬</span>
      <h3 style="font-size:20px; color:var(--text-main); margin-bottom:6px;">Select a conversation to start chatting</h3>
      <p style="max-width:360px; font-size:14px;">Contact neighbors from the Help Board to coordinate supplies, repairs, or tutoring.</p>
    </div>

    <div id="activeChatContainer" class="hidden" style="display:flex; flex-direction:column; height:100%;">
      <!-- CHAT HEADER -->
      <div class="chat-header">
        <div class="chat-header-user">
          <button id="mobileBackBtn" class="icon-btn" style="display:none; width:34px; height:34px; font-size:15px; margin-right:6px;" onclick="closeMobileChat()">←</button>
          <div class="avatar" id="chatHeaderAvatar">U</div>
          <div>
            <h4 id="chatHeaderName" style="font-size:16px;">User Name</h4>
            <small id="chatHeaderMeta" style="color:var(--text-muted); font-size:12px;">🟢 Active Now • Location</small>
          </div>
        </div>

        <div style="display:flex; gap:8px;">
          <a id="chatHeaderProfileLink" href="#" class="btn btn-outline btn-sm">Profile</a>
          <button class="btn btn-outline btn-sm" onclick="reportActiveChatUser()">⚠️ Report</button>
        </div>
      </div>

      <!-- MESSAGES SCROLL AREA -->
      <div class="chat-messages" id="chatMessagesArea">
        <!-- Message bubbles populated dynamically -->
      </div>

      <!-- CHAT INPUT BAR -->
      <form class="chat-input-bar" id="chatInputForm">
        <input type="text" id="chatMessageInput" placeholder="Type a message safely and transparently..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary" style="padding:10px 22px;">Send ➤</button>
      </form>
    </div>
  </section>
</main>

<script>
let currentConvId = <?= $activeConvParam ?>;
let activeRecipientId = null;
let chatPoller = null;

async function loadConversations() {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/messages.php?action=conversations');
    const container = $('#conversationsContainer');
    const list = res.data.conversations || [];

    if (list.length === 0) {
      container.innerHTML = `
        <div style="text-align:center; padding:35px 20px; color:var(--text-muted);">
          <p style="font-weight:700; margin-bottom:6px;">No conversations yet</p>
          <small>Start helping someone or post a request and conversations will appear here.</small>
        </div>
      `;
      return;
    }

    container.innerHTML = list.map(c => {
      const isActive = c.conversation_id == currentConvId;
      const initial = c.other_user_name ? c.other_user_name.charAt(0).toUpperCase() : 'U';
      const unreadBadge = c.unread_count > 0 ? `<span class="badge-counter" style="position:static;">${c.unread_count}</span>` : '';

      return `
        <div class="conversation-item ${isActive ? 'active' : ''}" onclick="selectConversation(${c.conversation_id}, ${c.other_user_id})">
          <div class="avatar avatar-sm">${initial}</div>
          <div class="conv-info">
            <div class="conv-top">
              <span class="conv-name">${c.other_user_name}</span>
              <span class="conv-time">${c.last_message_time ? formatChatTime(c.last_message_time) : ''}</span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span class="conv-msg">${c.last_message || 'Started conversation'}</span>
              ${unreadBadge}
            </div>
          </div>
        </div>
      `;
    }).join('');

    // If an active conversation is requested from URL param, open it
    if (currentConvId && !activeRecipientId) {
      const match = list.find(c => c.conversation_id == currentConvId);
      if (match) {
        selectConversation(currentConvId, match.other_user_id);
      }
    }
  } catch (e) {}
}

async function selectConversation(convId, recipientId) {
  currentConvId = convId;
  activeRecipientId = recipientId;

  // Toggle mobile display classes
  if (window.innerWidth <= 860) {
    $('#chatSidebar').classList.add('chat-active-mobile');
    $('#mobileBackBtn').style.display = 'grid';
  }

  $('#noChatSelected').classList.add('hidden');
  $('#activeChatContainer').classList.remove('hidden');

  await fetchChatHistory();

  if (chatPoller) clearInterval(chatPoller);
  chatPoller = setInterval(fetchChatHistory, 3500); // Live polling every 3.5s
}

function closeMobileChat() {
  $('#chatSidebar').classList.remove('chat-active-mobile');
  $('#activeChatContainer').classList.add('hidden');
  $('#noChatSelected').classList.remove('hidden');
  if (chatPoller) clearInterval(chatPoller);
}

async function fetchChatHistory() {
  if (!currentConvId) return;
  try {
    const res = await apiFetch(`<?= BASE_URL ?>api/messages.php?action=history&conversation_id=${currentConvId}`);
    const other = res.data.other_user;
    const messages = res.data.messages || [];

    if (other) {
      $('#chatHeaderName').textContent = other.full_name;
      $('#chatHeaderAvatar').textContent = other.full_name.charAt(0).toUpperCase();
      $('#chatHeaderMeta').textContent = `📍 ${other.city || 'Neighborhood'} • ⭐ ${Number(other.rating || 5).toFixed(1)}/5`;
      $('#chatHeaderProfileLink').href = `<?= BASE_URL ?>profile/profile.php?id=${other.id}`;
      activeRecipientId = other.id;
    }

    const area = $('#chatMessagesArea');
    const wasAtBottom = area.scrollHeight - area.scrollTop <= area.clientHeight + 100;

    area.innerHTML = messages.map(m => {
      const isMine = m.sender_id == currentUser.id;
      const bubbleClass = isMine ? 'msg-outgoing' : 'msg-incoming';
      const readCheck = isMine ? (m.read_status ? ' ✓✓' : ' ✓') : '';

      return `
        <div class="message-bubble ${bubbleClass}">
          <div>${escapeHtml(m.message)}</div>
          <span class="msg-time">${formatChatTime(m.created_at)}${readCheck}</span>
        </div>
      `;
    }).join('');

    if (wasAtBottom) {
      area.scrollTop = area.scrollHeight;
    }
  } catch (e) {}
}

// Send Message Handler (Without Page Refresh)
$('#chatInputForm').onsubmit = async (e) => {
  e.preventDefault();
  const input = $('#chatMessageInput');
  const text = input.value.trim();
  if (!text || !currentConvId) return;

  input.value = '';
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/messages.php?action=send', {
      method: 'POST',
      body: JSON.stringify({
        conversation_id: currentConvId,
        receiver_id: activeRecipientId,
        message: text
      })
    });

    await fetchChatHistory();
    const area = $('#chatMessagesArea');
    area.scrollTop = area.scrollHeight;
    loadConversations();
  } catch (err) {
    showToast(err.message || 'Failed to deliver message', 'error');
  }
};

function formatChatTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr.replace(' ', 'T'));
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function reportActiveChatUser() {
  if (!activeRecipientId) return;
  const reason = prompt('Please specify the reason for reporting this user (e.g. spam, inappropriate conduct):');
  if (reason) {
    apiFetch('<?= BASE_URL ?>api/safety.php?action=report', {
      method: 'POST',
      body: JSON.stringify({ item_type: 'user', item_id: activeRecipientId, reason: reason })
    }).then(res => {
      showToast(res.message || 'Report submitted to moderators', 'success');
    }).catch(e => showToast(e.message, 'error'));
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadConversations();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
