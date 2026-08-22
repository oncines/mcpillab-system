<?php
if (!function_exists('is_logged_in') || !is_logged_in() || (!is_employee() && !is_store())) {
    return;
}

$mcbot_role = is_store() ? 'store' : 'employee';
$mcbot_name = trim($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'there'));
?>
<div
    id="mcbotWidget"
    class="mcbot-widget"
    data-role="<?php echo htmlspecialchars($mcbot_role, ENT_QUOTES, 'UTF-8'); ?>"
    data-user-name="<?php echo htmlspecialchars($mcbot_name, ENT_QUOTES, 'UTF-8'); ?>"
>
    <button type="button" class="mcbot-toggle" id="mcbotToggle" aria-label="Open MCbot">
        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" width="64" height="64">
            <defs>
                <clipPath id="mcbotClip">
                    <circle cx="100" cy="100" r="96"/>
                </clipPath>
                <radialGradient id="mcbotBgGrad" cx="50%" cy="40%" r="60%">
                    <stop offset="0%" stop-color="#2a3bbf"/>
                    <stop offset="100%" stop-color="#0d1578"/>
                </radialGradient>
            </defs>
            <!-- Outer dark border ring -->
            <circle cx="100" cy="100" r="100" fill="#09106a"/>
            <!-- Main navy background -->
            <circle cx="100" cy="100" r="96" fill="url(#mcbotBgGrad)"/>
            <!-- White top half -->
            <path d="M4 100 A96 96 0 0 1 196 100 Z" fill="#eef0ff" clip-path="url(#mcbotClip)"/>
            <!-- Wave fills (navy over white) -->
            <path d="M4 105
                C10 105 14 88 22 82 C28 77 32 80 36 86 C40 92 42 98 48 94
                C54 90 56 78 64 73 C70 68 74 74 76 80 C78 84 80 88 84 88
                L84 108 C60 110 30 110 4 108 Z"
                fill="url(#mcbotBgGrad)" clip-path="url(#mcbotClip)"/>
            <path d="M60 105
                C66 105 68 90 76 84 C82 79 86 83 90 89 C93 94 95 98 100 95
                C105 92 107 80 114 74 C120 69 124 75 127 81 C130 86 131 90 136 90
                L136 108 C112 110 80 110 60 108 Z"
                fill="url(#mcbotBgGrad)" clip-path="url(#mcbotClip)"/>
            <path d="M112 105
                C118 105 120 91 128 85 C134 80 138 84 142 90 C145 95 147 100 152 97
                C158 93 160 81 167 76 C173 71 177 77 180 83 C183 88 185 93 190 93
                L196 105 L196 110 C175 110 140 110 112 108 Z"
                fill="url(#mcbotBgGrad)" clip-path="url(#mcbotClip)"/>
            <!-- White wave crests -->
            <path d="M10 95 C14 80 20 68 28 62 C36 56 42 60 46 68 C50 76 52 86 56 90
                C52 88 48 80 44 74 C40 68 36 64 30 68 C24 72 18 84 14 96 Z"
                fill="white" clip-path="url(#mcbotClip)"/>
            <path d="M10 98 C16 70 24 58 32 56 C40 54 46 62 50 72
                C46 66 40 58 34 60 C28 62 20 74 14 100 Z"
                fill="white" opacity="0.55" clip-path="url(#mcbotClip)"/>
            <path d="M68 95 C72 80 78 68 86 62 C94 56 100 60 104 68 C108 76 110 86 114 90
                C110 88 106 80 102 74 C98 68 94 64 88 68 C82 72 76 84 72 96 Z"
                fill="white" clip-path="url(#mcbotClip)"/>
            <path d="M68 98 C74 70 82 58 90 56 C98 54 104 62 108 72
                C104 66 98 58 92 60 C86 62 78 74 72 100 Z"
                fill="white" opacity="0.55" clip-path="url(#mcbotClip)"/>
            <path d="M126 95 C130 80 136 68 144 62 C152 56 158 60 162 68 C166 76 168 86 172 90
                C168 88 164 80 160 74 C156 68 152 64 146 68 C140 72 134 84 130 96 Z"
                fill="white" clip-path="url(#mcbotClip)"/>
            <path d="M126 98 C132 70 140 58 148 56 C156 54 162 62 166 72
                C162 66 156 58 150 60 C144 62 136 74 130 100 Z"
                fill="white" opacity="0.55" clip-path="url(#mcbotClip)"/>
            <!-- McPIL text -->
            <text x="100" y="162"
                text-anchor="middle"
                font-family="Georgia, 'Times New Roman', serif"
                font-size="38"
                font-weight="700"
                font-style="italic"
                fill="white"
                letter-spacing="1">McPIL</text>
            <!-- Inner border -->
            <circle cx="100" cy="100" r="96" fill="none" stroke="#07107a" stroke-width="2.5"/>
        </svg>
    </button>

    <section class="mcbot-panel" id="mcbotPanel" aria-hidden="true">
        <header class="mcbot-header">
            <div class="mcbot-header-info">
                <span class="mcbot-header-icon">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" width="34" height="34">
                        <defs>
                            <clipPath id="hdrClip"><circle cx="100" cy="100" r="96"/></clipPath>
                            <radialGradient id="hdrBg" cx="50%" cy="40%" r="60%">
                                <stop offset="0%" stop-color="#2a3bbf"/>
                                <stop offset="100%" stop-color="#0d1578"/>
                            </radialGradient>
                        </defs>
                        <circle cx="100" cy="100" r="100" fill="#09106a"/>
                        <circle cx="100" cy="100" r="96" fill="url(#hdrBg)"/>
                        <path d="M4 100 A96 96 0 0 1 196 100 Z" fill="#eef0ff" clip-path="url(#hdrClip)"/>
                        <path d="M4 105 C10 105 14 88 22 82 C28 77 32 80 36 86 C40 92 42 98 48 94 C54 90 56 78 64 73 C70 68 74 74 76 80 C78 84 80 88 84 88 L84 108 C60 110 30 110 4 108 Z" fill="url(#hdrBg)" clip-path="url(#hdrClip)"/>
                        <path d="M60 105 C66 105 68 90 76 84 C82 79 86 83 90 89 C93 94 95 98 100 95 C105 92 107 80 114 74 C120 69 124 75 127 81 C130 86 131 90 136 90 L136 108 C112 110 80 110 60 108 Z" fill="url(#hdrBg)" clip-path="url(#hdrClip)"/>
                        <path d="M112 105 C118 105 120 91 128 85 C134 80 138 84 142 90 C145 95 147 100 152 97 C158 93 160 81 167 76 C173 71 177 77 180 83 C183 88 185 93 190 93 L196 105 L196 110 C175 110 140 110 112 108 Z" fill="url(#hdrBg)" clip-path="url(#hdrClip)"/>
                        <path d="M10 95 C14 80 20 68 28 62 C36 56 42 60 46 68 C50 76 52 86 56 90 C52 88 48 80 44 74 C40 68 36 64 30 68 C24 72 18 84 14 96 Z" fill="white" clip-path="url(#hdrClip)"/>
                        <path d="M68 95 C72 80 78 68 86 62 C94 56 100 60 104 68 C108 76 110 86 114 90 C110 88 106 80 102 74 C98 68 94 64 88 68 C82 72 76 84 72 96 Z" fill="white" clip-path="url(#hdrClip)"/>
                        <path d="M126 95 C130 80 136 68 144 62 C152 56 158 60 162 68 C166 76 168 86 172 90 C168 88 164 80 160 74 C156 68 152 64 146 68 C140 72 134 84 130 96 Z" fill="white" clip-path="url(#hdrClip)"/>
                        <text x="100" y="162" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="38" font-weight="700" font-style="italic" fill="white" letter-spacing="1">McPIL</text>
                        <circle cx="100" cy="100" r="96" fill="none" stroke="#07107a" stroke-width="2.5"/>
                    </svg>
                </span>
                <div>
                    <h3>MCbot</h3>
                    <p><i class="fas fa-circle" style="color: #31a24c; font-size: 8px; margin-right: 4px;"></i> Active now</p>
                </div>
            </div>
            <div class="mcbot-header-actions">
                <button type="button" class="mcbot-header-btn" id="mcbotReset" title="Reset Conversation">
                    <i class="fas fa-rotate-left"></i>
                </button>
                <button type="button" class="mcbot-header-btn" id="mcbotSizeToggle" title="Maximize/Minimize">
                    <i class="fas fa-expand-alt" id="mcbotSizeIcon"></i>
                </button>
                <button type="button" class="mcbot-close" id="mcbotClose" aria-label="Close MCbot">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </header>

        <div class="mcbot-body">
            <div class="mcbot-messages" id="mcbotMessages">
                <div class="mcbot-message bot">
                    <strong>MCbot</strong>
                    <?php 
                    $welcome_text = ($mcbot_role === 'admin' || $mcbot_role === 'manager') 
                        ? 'Hello, ' . htmlspecialchars($mcbot_name, ENT_QUOTES, 'UTF-8') . '. Ask me anything about your work here — deliveries, stock, attendance, or any system page.'
                        : 'Hello, ' . htmlspecialchars($mcbot_name, ENT_QUOTES, 'UTF-8') . '. Ask me anything about your work here — stock, attendance, reports, or any system page.';
                    ?>
                    <p><?php echo $welcome_text; ?></p>
                </div>
            </div>

            <div class="mcbot-faq-section">
                <div class="mcbot-faq-header">
                    <span><i class="fas fa-circle-question"></i> Frequently Asked</span>
                    <small><?php echo $mcbot_role === 'store' ? 'Store' : 'Employee'; ?></small>
                </div>
                <div class="mcbot-faq-list" id="mcbotFaqList"></div>
            </div>

            <div class="mcbot-quick-actions" id="mcbotQuickActions">
                <button type="button" class="mcbot-chip" data-prompt="How do I use this page?"><i class="fas fa-circle-info"></i> This page</button>
                <button type="button" class="mcbot-chip" data-prompt="Show FAQ"><i class="fas fa-question-circle"></i> FAQ</button>
                <button type="button" class="mcbot-chip" data-prompt="Help"><i class="fas fa-headset"></i> Help</button>
                <?php
                $quick = $mcbot_role === 'store'
                    ? [['Purchase Orders','purchase_order.php'],['Invoice List','invoice_list.php']]
                    : [['Attendance History','attendance_history.php'],['Messages','chat_interface.php']];
                foreach ($quick as [$label, $href]):
                ?>
                <button type="button" class="mcbot-chip" data-link="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" data-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <form class="mcbot-input-row" id="mcbotForm">
            <input
                type="text"
                id="mcbotInput"
                class="mcbot-input"
                placeholder="Aa"
                autocomplete="off"
            >
            <button type="submit" class="mcbot-send" aria-label="Send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </section>
</div>

<style>
    /* ── Tokens ── */
    .mcbot-widget {
        --navy:      #0d1b3e;
        --navy-mid:  #162248;
        --navy-dark: #09122a;
        --red:       #c0182a;
        --red-hover: #a01220;
        --white:     #ffffff;
        --off-white: #f2f4f8;
        --border:    #d0d6e4;
        --text:      #0d1b3e;
        --muted:     #5a6685;
        --shadow:    0 12px 40px rgba(9,18,42,0.22);

        position: fixed;
        right: 15px;
        bottom: 15px;
        z-index: 1080;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* ── Toggle button (McPIL logo circle) ── */
    .mcbot-toggle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: block;
    }

    .mcbot-toggle:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    }

    .mcbot-toggle:active {
        transform: scale(0.97);
    }

    /* ── Panel ── */
    .mcbot-panel {
        position: absolute;
        right: 0;
        bottom: calc(100% + 15px);
        width: 400px;
        height: 600px;
        max-width: calc(100vw - 32px);
        max-height: calc(100vh - 120px);
        border-radius: 20px;
        background: var(--white);
        box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0,0,0,0.1);
        display: none;
        flex-direction: column;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .mcbot-panel.maximized {
        width: 500px;
        height: 800px;
        max-height: calc(100vh - 50px);
    }

    .mcbot-widget.open .mcbot-panel {
        display: flex;
    }

    /* ── Header ── */
    .mcbot-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: var(--navy);
        color: var(--white);
        flex: 0 0 auto;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .mcbot-header-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mcbot-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        overflow: hidden;
    }

    .mcbot-header h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--white);
    }

    .mcbot-header p {
        margin: 0;
        font-size: 0.75rem;
        color: rgba(255,255,255,0.8);
        font-weight: 500;
    }

    .mcbot-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .mcbot-header-btn,
    .mcbot-close {
        border: none;
        background: transparent;
        color: var(--white);
        width: 34px;
        height: 34px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex: 0 0 auto;
        transition: background 0.2s;
    }

    .mcbot-header-btn:hover,
    .mcbot-close:hover {
        background: rgba(255,255,255,0.15);
    }

    /* ── Body ── */
    .mcbot-body {
        flex: 1;
        overflow-y: auto;
        background: var(--off-white);
    }

    .mcbot-body::-webkit-scrollbar { width: 5px; }
    .mcbot-body::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 999px;
    }

    /* ── Messages ── */
    .mcbot-messages {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .mcbot-message {
        max-width: 80%;
        padding: 9px 15px;
        border-radius: 18px;
        font-size: 1rem;
        line-height: 1.5;
        position: relative;
        margin-bottom: 2px;
    }

    .mcbot-message strong {
        display: none; /* Cleaner look like Messenger */
    }

    .mcbot-message p {
        margin: 0;
    }

    .mcbot-message.bot {
        background: #e4e6eb;
        color: #050505;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .mcbot-message.user {
        background: var(--navy);
        color: var(--white);
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    /* ── Inline nav buttons inside bot messages ── */
    .mcbot-nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    .mcbot-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 11px;
        border-radius: 5px;
        background: var(--navy);
        color: var(--white);
        text-decoration: none;
        font-size: 0.76rem;
        font-weight: 600;
        transition: background 0.15s;
    }

    .mcbot-nav-btn:hover {
        background: var(--red);
        color: var(--white);
    }

    .mcbot-nav-btn i {
        font-size: 11px;
    }

    /* ── FAQ section ── */
    .mcbot-faq-section {
        margin: 0 16px 12px;
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 15px;
        overflow: hidden;
    }

    .mcbot-faq-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        background: #f8f9fa;
        color: var(--navy);
        font-size: 0.8rem;
        font-weight: 700;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .mcbot-faq-header small {
        opacity: 0.6;
        font-weight: 600;
        font-size: 0.72rem;
    }

    .mcbot-faq-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1px;
        background: rgba(0,0,0,0.05);
    }

    .mcbot-faq-item {
        border: none;
        background: var(--white);
        color: var(--text);
        padding: 10px 14px;
        font-size: 0.88rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        line-height: 1.3;
        transition: background 0.15s;
    }

    .mcbot-faq-item:hover {
        background: #f0f2f5;
    }

    /* ── Quick actions ── */
    .mcbot-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 0 16px 16px;
    }

    .mcbot-chip {
        border: 1px solid var(--navy);
        background: var(--white);
        color: var(--navy);
        border-radius: 18px;
        padding: 6px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .mcbot-chip:hover {
        background: var(--navy);
        color: var(--white);
        box-shadow: 0 4px 12px rgba(13,27,62,0.15);
    }

    /* ── Input row ── */
    .mcbot-input-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--white);
        border-top: 1px solid rgba(0,0,0,0.1);
        flex: 0 0 auto;
    }

    .mcbot-input {
        flex: 1;
        min-width: 0;
        border: none;
        border-radius: 20px;
        padding: 9px 16px;
        font-size: 1rem;
        color: var(--text);
        background: #f0f2f5;
        outline: none;
    }

    .mcbot-input:focus {
        background: #e4e6eb;
    }

    .mcbot-send {
        border: none;
        background: transparent;
        color: var(--navy);
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: transform 0.2s, color 0.2s;
    }

    .mcbot-send:hover {
        color: var(--red);
        transform: scale(1.1);
    }

    /* ── Mobile ── */
    @media (max-width: 575.98px) {
        .mcbot-widget { right: 10px; bottom: 10px; }

        .mcbot-panel {
            width: calc(100vw - 20px);
            height: calc(100vh - 100px);
            bottom: calc(100% + 10px);
            border-radius: 15px;
        }

        .mcbot-faq-list { grid-template-columns: 1fr; }
    }
</style>

<script>
 (function () { 
     const widget = document.getElementById('mcbotWidget'); 
     if (!widget || widget.dataset.initialized === 'true') return; 
     widget.dataset.initialized = 'true'; 
 
     const role         = widget.dataset.role || 'employee'; 
     const userName     = widget.dataset.userName || 'there'; 
     const currentPage  = window.location.pathname.split('/').pop() || 'dashboard.php'; 
     const toggle       = document.getElementById('mcbotToggle'); 
     const closeBtn     = document.getElementById('mcbotClose'); 
     const resetBtn     = document.getElementById('mcbotReset'); 
     const sizeBtn      = document.getElementById('mcbotSizeToggle'); 
     const sizeIcon     = document.getElementById('mcbotSizeIcon'); 
     const panel        = document.getElementById('mcbotPanel'); 
     const form         = document.getElementById('mcbotForm'); 
     const input        = document.getElementById('mcbotInput'); 
     const messages     = document.getElementById('mcbotMessages'); 
     const body         = widget.querySelector('.mcbot-body'); 
     const faqList      = document.getElementById('mcbotFaqList'); 
     const quickActions = document.getElementById('mcbotQuickActions'); 
     const faqSection   = widget.querySelector('.mcbot-faq-section'); 
 
     /* ── Conversation history (kept in memory + localStorage) ── */ 
     const HISTORY_KEY    = `mcbot_history_${userName}`; 
     const MAX_HISTORY    = 20;   // total messages kept in localStorage 
     const CONTEXT_TURNS  = 10;   // last N messages sent to the API for context 
 
     let conversationHistory = []; // [{role:'user'|'assistant', content:'...'}] 
 
     function saveHistory() { 
         const trimmed = conversationHistory.slice(-MAX_HISTORY); 
         try { 
             localStorage.setItem(HISTORY_KEY, JSON.stringify({ 
                 history:   trimmed, 
                 timestamp: Date.now() 
             })); 
         } catch (_) {} 
     } 
 
     function loadHistory() { 
         try { 
             const raw = localStorage.getItem(HISTORY_KEY); 
             if (!raw) return false; 
             const parsed = JSON.parse(raw); 
             const twoHours = 2 * 60 * 60 * 1000; 
             if (Date.now() - (parsed.timestamp || 0) > twoHours) { 
                 localStorage.removeItem(HISTORY_KEY); 
                 return false; 
             } 
             conversationHistory = Array.isArray(parsed.history) ? parsed.history : []; 
             return conversationHistory.length > 0; 
         } catch (_) { 
             return false; 
         } 
     } 
 
     /* ── Escape HTML ── */ 
     function esc(str) { 
         return String(str) 
             .replace(/&/g, '&amp;').replace(/</g, '&lt;') 
             .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); 
     } 
 
     /* ── Render a message bubble ── */ 
     function appendMessage(sender, text, links) { 
         const bubble = document.createElement('div'); 
         bubble.className = 'mcbot-message ' + sender; 
         let html = `<p>${esc(text)}</p>`; 
         if (sender === 'bot' && Array.isArray(links) && links.length) { 
             html += '<div class="mcbot-nav-links">' + 
                 links.map(l => { 
                     const isInternal = l.href.startsWith('#'); 
                     const href       = isInternal ? 'javascript:void(0)' : esc(l.href); 
                     const dataAttr   = isInternal ? `data-prompt="${esc(l.label)}"` : ''; 
                     return `<a class="mcbot-nav-btn" href="${href}" ${dataAttr}>` + 
                            `<i class="fas fa-arrow-up-right-from-square"></i>${esc(l.label)}</a>`; 
                 }).join('') + '</div>'; 
         } 
         bubble.innerHTML = html; 
         messages.appendChild(bubble); 
         scrollToBottom(); 
     } 
 
     /* ── Typing indicator ── */ 
     function showTyping() { 
         const el = document.createElement('div'); 
         el.className = 'mcbot-message bot mcbot-typing'; 
         el.id = 'mcbotTyping'; 
         el.innerHTML = `<span></span><span></span><span></span>`; 
         messages.appendChild(el); 
         scrollToBottom(); 
     } 
 
     function hideTyping() { 
         const el = document.getElementById('mcbotTyping'); 
         if (el) el.remove(); 
     } 
 
     function scrollToBottom() { 
         if (body) { 
             body.scrollTo({ top: body.scrollHeight, behavior: 'smooth' }); 
         } 
     } 
 
     /* ── Restore previous conversation bubbles from history ── */ 
     function restoreBubbles() { 
         messages.innerHTML = ''; 
         conversationHistory.forEach(turn => { 
             const sender = turn.role === 'user' ? 'user' : 'bot'; 
             appendMessage(sender, turn.content, turn.links || []); 
         }); 
     } 
 
     /* ── Send to API ── */ 
     async function ask(userMessage) { 
         // Build context window: last CONTEXT_TURNS messages 
         const contextHistory = conversationHistory.slice(-CONTEXT_TURNS).map(t => ({ 
             role:    t.role, 
             content: t.content 
         })); 
 
         try { 
             const res = await fetch('mcbot_api.php', { 
                 method:  'POST', 
                 headers: { 'Content-Type': 'application/json' }, 
                 body: JSON.stringify({ 
                     message: userMessage, 
                     page:    currentPage, 
                     history: contextHistory   // ← send conversation context 
                 }) 
             }); 
             if (!res.ok) throw new Error('HTTP ' + res.status); 
             const data = await res.json(); 
             if (!data || typeof data.text !== 'string' || !data.text.trim()) throw new Error('empty'); 
             return { text: data.text, links: Array.isArray(data.links) ? data.links : [] }; 
         } catch (_) { 
             return { 
                 text:  "Sorry, I'm having trouble connecting right now. Please try again in a moment.", 
                 links: [] 
             }; 
         } 
     } 
 
     /* ── Handle a user message end-to-end ── */ 
     async function sendMessage(userText) { 
         if (!userText.trim()) return; 
 
         hideExtras(); 
         appendMessage('user', userText, []); 
 
         // Add to history immediately 
         conversationHistory.push({ role: 'user', content: userText }); 
 
         showTyping(); 
         const reply = await ask(userText); 
         hideTyping(); 
 
         appendMessage('bot', reply.text, reply.links); 
 
         // Add bot reply to history 
         conversationHistory.push({ role: 'assistant', content: reply.text, links: reply.links }); 
         saveHistory(); 
     } 
 
     /* ── FAQ data ── */ 
     const faqSets = { 
         employee: [ 
             { label: 'System Info',    question: 'What is MCPIL?' }, 
             { label: 'Who are you?',   question: 'Tell me about yourself' }, 
             { label: 'Clock In/Out',   question: 'How do I clock in or out?' }, 
             { label: 'Attendance',     question: 'How do I check my attendance?' }, 
             { label: 'Inventory',      question: 'Where can I view inventory?' }, 
             { label: 'Messaging',      question: 'How do I message another user?' } 
         ], 
         store: [ 
             { label: 'System Info',    question: 'What is MCPIL?' }, 
             { label: 'Who are you?',   question: 'Tell me about yourself' }, 
             { label: 'Attendance',     question: 'How do I check my attendance?' }, 
             { label: 'Clock In/Out',   question: 'How do I clock in or out?' }, 
             { label: 'Inventory',      question: 'Where can I view inventory?' }, 
             { label: 'Purchase Order', question: 'How do I create a purchase order?' }, 
             { label: 'Invoices',       question: 'Where can I check invoices?' } 
         ] 
     }; 
 
     function renderFaq() { 
         if (!faqList) return; 
         faqList.innerHTML = ''; 
         (faqSets[role] || faqSets.employee).forEach(faq => { 
             const btn = document.createElement('button'); 
             btn.type = 'button'; 
             btn.className = 'mcbot-faq-item'; 
             btn.textContent = faq.label; 
             btn.dataset.question = faq.question; 
             faqList.appendChild(btn); 
         }); 
     } 
 
     /* ── Show / hide FAQ + chips ── */ 
     function hideExtras() { 
         if (faqSection)   faqSection.style.display   = 'none'; 
         if (quickActions) quickActions.style.display  = 'none'; 
     } 
 
     function showExtras() { 
         if (faqSection)   faqSection.style.display   = ''; 
         if (quickActions) quickActions.style.display  = ''; 
     } 
 
     /* ── Panel open/close ── */ 
     function open() { 
         widget.classList.add('open'); 
         panel.setAttribute('aria-hidden', 'false'); 
         input.focus(); 
         sessionStorage.setItem('mcbot_open', 'true'); 
         setTimeout(scrollToBottom, 60); 
     } 
 
     function close() { 
         widget.classList.remove('open'); 
         panel.setAttribute('aria-hidden', 'true'); 
         showExtras(); 
         sessionStorage.setItem('mcbot_open', 'false'); 
     } 
 
     function resetConversation() { 
         if (!confirm('Reset the conversation?')) return; 
         conversationHistory = []; 
         localStorage.removeItem(HISTORY_KEY); 
         messages.innerHTML = `<div class="mcbot-message bot"><p>Hello, ${esc(userName)}! How can I help you today?</p></div>`; 
         showExtras(); 
     } 
 
     function toggleSize() { 
         const maximized = panel.classList.toggle('maximized'); 
         localStorage.setItem('mcbot_maximized', maximized ? 'true' : 'false'); 
         sizeIcon.className = maximized ? 'fas fa-compress-alt' : 'fas fa-expand-alt'; 
         sizeBtn.title      = maximized ? 'Minimize' : 'Maximize'; 
         setTimeout(scrollToBottom, 350); 
     } 
 
     /* ── Events ── */ 
     toggle.addEventListener('click',   () => widget.classList.contains('open') ? close() : open()); 
     closeBtn.addEventListener('click', close); 
     resetBtn.addEventListener('click', resetConversation); 
     sizeBtn.addEventListener('click',  toggleSize); 
 
     form.addEventListener('submit', e => { 
         e.preventDefault(); 
         const val = input.value.trim(); 
         if (!val) return; 
         input.value = ''; 
         sendMessage(val); 
     }); 
 
     if (faqList) { 
         faqList.addEventListener('click', e => { 
             const btn = e.target.closest('.mcbot-faq-item'); 
             if (!btn || !btn.dataset.question) return; 
             sendMessage(btn.dataset.question); 
         }); 
     } 
 
     quickActions.addEventListener('click', e => { 
         const chip = e.target.closest('.mcbot-chip'); 
         if (!chip) return; 
         const link   = chip.dataset.link; 
         const label  = chip.dataset.label; 
         const prompt = chip.dataset.prompt; 
         if (link && label) { window.open(link, '_blank'); return; } 
         if (prompt) sendMessage(prompt); 
     }); 
 
     messages.addEventListener('click', e => { 
         const btn = e.target.closest('.mcbot-nav-btn'); 
         if (!btn) return; 
         const prompt = btn.dataset.prompt; 
         if (prompt) { 
             e.preventDefault(); 
             sendMessage(prompt); 
         } 
     }); 
 
     /* ── Init ── */ 
     (function init() { 
         const hadHistory = loadHistory(); 
         if (hadHistory) { 
             restoreBubbles(); 
             hideExtras(); 
         } 
 
         const isMaximized = localStorage.getItem('mcbot_maximized') === 'true'; 
         if (isMaximized) { 
             panel.classList.add('maximized'); 
             sizeIcon.className = 'fas fa-compress-alt'; 
             sizeBtn.title      = 'Minimize'; 
         } else { 
             sizeIcon.className = 'fas fa-expand-alt'; 
             sizeBtn.title      = 'Maximize'; 
         } 
 
         if (sessionStorage.getItem('mcbot_open') === 'true') open(); 
         renderFaq(); 
     })(); 
 
     /* ── Typing indicator CSS (injected once) ── */ 
     if (!document.getElementById('mcbot-typing-style')) { 
         const style = document.createElement('style'); 
         style.id = 'mcbot-typing-style'; 
         style.textContent = ` 
             .mcbot-typing { 
                 display: flex !important; 
                 align-items: center; 
                 gap: 5px; 
                 padding: 12px 16px !important; 
             } 
             .mcbot-typing span { 
                 width: 7px; height: 7px; 
                 border-radius: 50%; 
                 background: #aaa; 
                 animation: mcbotBounce 1.2s infinite ease-in-out; 
             } 
             .mcbot-typing span:nth-child(2) { animation-delay: 0.2s; } 
             .mcbot-typing span:nth-child(3) { animation-delay: 0.4s; } 
             @keyframes mcbotBounce { 
                 0%, 80%, 100% { transform: scale(0.7); opacity: 0.5; } 
                 40%           { transform: scale(1);   opacity: 1;   } 
             } 
         `; 
         document.head.appendChild(style); 
     } 
 
 })(); 
</script>