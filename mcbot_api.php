<?php
require_once 'config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'text' => 'Your session has ended. Please sign in again to continue.',
        'links' => [['label' => 'Sign In', 'href' => 'index.php']]
    ]);
    exit;
}

/* ════════════════════════════════════════════
   HELPERS
   ════════════════════════════════════════════ */

function mcbot_json_response($text, $links = []) {
    echo json_encode(['text' => $text, 'links' => array_values($links)]);
    exit;
}

function mcbot_normalize($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
    return preg_replace('/\s+/', ' ', $value);
}

function mcbot_default_links($role) {
    $links = [
        ['label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['label' => 'Inventory',  'href' => 'inventory.php'],
        ['label' => 'Reports',    'href' => 'reports.php']
    ];
    if ($role === 'admin' || $role === 'manager') {
        $links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
        $links[] = ['label' => 'Purchase Orders',   'href' => 'purchase_order.php'];
        $links[] = ['label' => 'Invoices',           'href' => 'invoice_list.php'];
    } else {
        $links[] = ['label' => 'Attendance History', 'href' => 'attendance_history.php'];
        $links[] = ['label' => 'Messages',         'href' => 'chat_interface.php'];
    }
    return $links;
}

function mcbot_intro($normalized) {
    if (preg_match('/\b(what|when|where|who|why|how|can|do|is|are)\b/', $normalized) === 1) {
        return 'From what I can see, ';
    }
    return '';
}

function mcbot_page_hints() {
    return [
        'dashboard.php'          => 'This page is the main summary view. Use it to check quick counts and jump into the main modules.',
        'inventory.php'          => 'This page is for inventory monitoring. You can review stock levels, item details, and related reports here.',
        'inventory_report.php'   => 'This page summarizes stock quantities, value, and reorder needs.',
        'attendance.php'         => 'This page is for attendance management and review.',
        'attendance_history.php' => 'This page shows attendance records and photo-based attendance history.',
        'attendance_camera.php'  => 'This page is where employees capture attendance with a photo.',
        'reports.php'            => 'This page is for filtering, exporting, and printing system reports.',
        'delivery_tracking.php'  => 'This page is for active delivery tracking and status updates.',
        'delivery_history.php'   => 'This page shows completed and historical delivery records.',
        'purchase_order.php'     => 'This page is for creating and reviewing purchase orders.',
        'purchase_invoice.php'   => 'This page is for encoding and reviewing supplier invoices.',
        'invoice_list.php'       => 'This page lists invoice records linked to purchase activity.',
        'chat_interface.php'     => 'This page is for direct staff-to-staff messaging.'
    ];
}

/* ════════════════════════════════════════════
   DATABASE HELPERS
   ════════════════════════════════════════════ */

function mcbot_get_upcoming_deliveries() {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db || !table_exists($db, 'deliveries')) return [];
    ensure_delivery_statuses($db);

    $user_store_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] . ' Store' : '';
    $query = "SELECT d.delivery_number, d.delivery_date, d.expected_date, d.status,
                     s.name AS supplier_name, po.po_number, po.store_name
              FROM deliveries d
              LEFT JOIN suppliers s ON d.supplier_id = s.id
              LEFT JOIN purchase_orders po ON d.po_id = po.id
              WHERE d.status IN ('pending','approved','in_transit')
                AND (d.expected_date >= CURDATE() OR d.delivery_date >= CURDATE())";
    if (!empty($user_store_name)) $query .= " AND po.store_name = :store_name";
    $query .= " ORDER BY COALESCE(d.expected_date, d.delivery_date) ASC, d.created_at ASC LIMIT 3";

    $stmt = $db->prepare($query);
    if (!empty($user_store_name)) $stmt->bindValue(':store_name', $user_store_name);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mcbot_get_expected_purchase_orders() {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db || !table_exists($db, 'purchase_orders')) return [];

    $user_store_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] . ' Store' : '';
    $query = "SELECT po.po_number, po.expected_delivery_date, po.status,
                     s.name AS supplier_name, po.store_name
              FROM purchase_orders po
              LEFT JOIN suppliers s ON po.supplier_id = s.id
              WHERE po.expected_delivery_date IS NOT NULL
                AND po.status IN ('Pending','Approved','Processing')";
    if (!empty($user_store_name)) $query .= " AND po.store_name = :store_name";
    $query .= " ORDER BY po.expected_delivery_date ASC, po.created_at ASC LIMIT 3";

    $stmt = $db->prepare($query);
    if (!empty($user_store_name)) $stmt->bindValue(':store_name', $user_store_name);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mcbot_get_low_stock_items() {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db || !table_exists($db, 'inventory_items') || !table_exists($db, 'inventory_stock')) return [];

    $query = "SELECT ii.item_name,
                     COALESCE(ist.total_stock, 0) AS total_stock,
                     ii.min_stock_level
              FROM inventory_items ii
              LEFT JOIN inventory_stock ist ON ii.id = ist.item_id
              WHERE COALESCE(ist.total_stock, 0) <= COALESCE(ii.min_stock_level, 0)
              ORDER BY COALESCE(ist.total_stock, 0) ASC, ii.item_name ASC
              LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ════════════════════════════════════════════
   CLAUDE AI SMART FALLBACK
   ════════════════════════════════════════════ */

/**
 * Calls the Anthropic API so MCbot can answer ANY question the employee asks,
 * framed within the MCPIL system context.
 *
 * Add your API key to config.php:
 *   define('ANTHROPIC_API_KEY', 'sk-ant-...');
 * OR set an environment variable: ANTHROPIC_API_KEY=sk-ant-...
 */
function mcbot_ask_claude($message, $role, $name, $current_page) {
    $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');

    // No API key configured — let the hard-coded fallback handle it
    if (empty($api_key)) return null;

    $role_label = match($role) {
        'admin'   => 'Administrator',
        'manager' => 'Manager',
        'store'   => 'Store Manager',
        default   => 'Employee',
    };

    $pages_list = implode(', ', [
        'dashboard.php (Dashboard overview)',
        'inventory.php (Inventory management)',
        'attendance_camera.php (Clock in / Clock out with photo)',
        'attendance_history.php (View attendance records)',
        'reports.php (Generate and export reports)',
        'chat_interface.php (Staff messaging)',
        'delivery_tracking.php (Track deliveries — admin/manager only)',
        'delivery_history.php (Delivery history — admin/manager only)',
        'purchase_order.php (Purchase orders — store/admin/manager)',
        'purchase_invoice.php (Encode supplier invoices — store/admin/manager)',
        'invoice_list.php (View all invoices — store/admin/manager)',
        'logout.php (Sign out)',
    ]);

    $system_prompt = <<<PROMPT
You are MCbot, a friendly and knowledgeable in-system assistant for the MCPIL Laboratory Management System — a platform used by a pharmaceutical laboratory in the Philippines to manage inventory, employee attendance, purchase orders, supplier invoices, and delivery tracking.

The user you are talking to is named "{$name}" and their role is "{$role_label}". They are currently on the page: "{$current_page}".

Your job is to:
1. Answer ANY question the user asks — even if it seems unrelated — in a helpful, natural, conversational way.
2. Always tie your answer back to what the user should DO inside the MCPIL system when relevant.
3. If the user seems confused or lost, explain step-by-step what they should do next inside the system.
4. If the user asks something completely outside the system (general knowledge, jokes, personal questions, etc.), answer it briefly and warmly, then gently offer to help them with system tasks.
5. Keep answers concise — 2 to 4 sentences max. Never be robotic or overly formal.
6. Write in English. If the user writes in Filipino or Taglish, reply in a friendly mix of English and Filipino so they feel at ease.
7. NEVER make up page names or features that do not exist. Only reference these actual system pages: {$pages_list}
8. Do NOT include JSON, markdown, bullet points, or any special formatting in your reply — plain conversational sentences only.
9. Do NOT say "As an AI language model" or mention Claude or Anthropic. You are MCbot, part of the MCPIL system.
10. Role-based access rules to follow:
    - Delivery Tracking and Delivery History: admin and manager only
    - Purchase Orders and Invoices: store, admin, and manager
    - All other pages: accessible to all roles including employees
PROMPT;

    $payload = [
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 300,
        'system'     => $system_prompt,
        'messages'   => [
            ['role' => 'user', 'content' => $message]
        ]
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $http_code !== 200) return null;

    $data = json_decode($response, true);
    $text = trim($data['content'][0]['text'] ?? '');
    return $text !== '' ? $text : null;
}

/* ════════════════════════════════════════════
   INPUT
   ════════════════════════════════════════════ */

$payload      = json_decode(file_get_contents('php://input'), true);
$message      = trim((string) ($payload['message'] ?? ''));
$history      = $payload['history'] ?? [];
$current_page = basename((string) ($payload['page'] ?? 'dashboard.php'));
$role         = $_SESSION['user_role'] ?? 'employee';
$name         = trim($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'there'));
$normalized   = mcbot_normalize($message);
$links        = mcbot_default_links($role);
$page_hints   = mcbot_page_hints();
$intro        = mcbot_intro($normalized);

/* ════════════════════════════════════════════
   EMPTY MESSAGE
   ════════════════════════════════════════════ */

if ($message === '') {
    mcbot_json_response(
        'Ask me anything about the system. I will answer as clearly as I can and point you to the right page when needed.',
        array_slice($links, 0, 4)
    );
}

/* ════════════════════════════════════════════
   CONTEXT-AWARE FOLLOW-UPS
   ════════════════════════════════════════════ */

if (!empty($history) && strlen($normalized) < 10) {
    if (preg_match('/\b(why|bakit|how|paano|pa.?no)\b/', $normalized)) {
        mcbot_json_response("I'm explaining based on the system's current data and rules. If you're asking about my previous answer, it's the most accurate information I have right now! Is there a specific part you're confused about?", array_slice($links, 0, 4));
    }
    if (preg_match('/\b(really|totoo|sure)\b/', $normalized)) {
        mcbot_json_response("Yes, I'm sure! I always check the live database before giving you an answer. You can trust the information I provide here.", array_slice($links, 0, 4));
    }
}

/* ════════════════════════════════════════════
   IDENTITY & ABOUT
   ════════════════════════════════════════════ */

if (preg_match('/\b(who\s*(are|r)\s*(you|u|yu)|what\s*are\s*you|sino\s*ka|tell\s*me\s*about\s*your\s*self|mcbot)\b/', $normalized)) {
    mcbot_json_response("I am MCbot, your dedicated in-system assistant! I help you navigate the MCPIL Laboratory Management System, check live inventory levels, view attendance history, and track upcoming deliveries. You can ask me anything — I'll always try to help!");
}

if (preg_match('/\b(what\s*is\s*mcpil|what.*mcpil.*do|about\s*mcpil|ano.*mcpil|system\s*is\s*all\s*about)\b/', $normalized)) {
    mcbot_json_response('The MCPIL Laboratory Management System is a comprehensive platform designed to streamline pharmaceutical laboratory operations. It integrates inventory management, employee attendance tracking with photo verification, purchase order processing, supplier invoice management, and real-time delivery tracking. Our goal is to ensure efficient laboratory workflows and data accuracy across all modules.');
}

/* ════════════════════════════════════════════
   COMPREHENSIVE SYSTEM MODULES & FAQ
   ════════════════════════════════════════════ */

// 1. INVENTORY RELATED
if (preg_match('/\b(add|new|create|encode|input|pasok|dagdag|isama|record)\b.*\b(item|product|stock|inventory|gamot|supplies)\b/i', $normalized) || 
    preg_match('/\b(paano|how|step|process)\b.*\b(add|new|create)\b/i', $normalized)) {
    mcbot_json_response("To add new items to the inventory, go to the Inventory Management page. If you have admin or manager privileges, you'll see an 'Add Item' button to encode new products into the system.", [['label' => 'Inventory', 'href' => 'inventory.php']]);
}

if (preg_match('/\b(update|edit|change|adjust|bago|palit|mali|correction|set)\b.*\b(stock|quantity|qty|count|level|bilang|inventory)\b/i', $normalized)) {
    mcbot_json_response("You can update stock levels by clicking the 'Edit' icon next to any item on the Inventory page. For significant stock changes, we recommend processing a Purchase Order or checking the latest Invoices.", [['label' => 'Inventory', 'href' => 'inventory.php']]);
}

// 2. ATTENDANCE RELATED
if (preg_match('/\b(camera|webcam|photo|picture|permis|access|allow|not.*work|error|ayaw|gumana)\b.*\b(attendance|clock|camera|login|verification)\b/i', $normalized)) {
    mcbot_json_response("The Attendance Camera module requires camera permissions in your browser. Make sure you've allowed camera access so the system can capture your verification photo during clock-in or clock-out.", [['label' => 'Attendance Camera', 'href' => 'attendance_camera.php']]);
}

if (preg_match('/\b(forgot|miss|nakalimutan|hindi|fail|late|manual)\b.*\b(clock|time|attendance|record|login|logout|pumasok|umalis)\b/i', $normalized)) {
    mcbot_json_response("If you forgot to clock in or out, please inform your Manager or Admin immediately. They can review the system logs and manually verify your attendance records if necessary.", [['label' => 'Attendance History', 'href' => 'attendance_history.php']]);
}

// 3. PURCHASE & INVOICE RELATED
if (preg_match('/\b(status|check|find|nasaan|asan|track|update)\b.*\b(po|order|purchase|pending|received|deliver)\b/i', $normalized)) {
    mcbot_json_response("You can check the status of all orders in the Purchase Order page. Look for the 'Status' column to see if an order is Pending, Approved, or Received.", [['label' => 'Purchase Orders', 'href' => 'purchase_order.php']]);
}

if (preg_match('/\b(missing|search|find|nasaan|asan|lost|receipt|bill|invoice|resibo|bayad)\b.*\b(po|supplier|number|inv|record)\b/i', $normalized)) {
    mcbot_json_response("All encoded invoices are listed in the Invoice List. You can use the search bar there to find specific invoices by PO number or Supplier name.", [['label' => 'Invoice List', 'href' => 'invoice_list.php']]);
}

// 4. CHAT & COMMUNICATION
if (preg_match('/\b(online|active|present|active|sino|who|list|active)\b.*\b(user|staff|employee|manager|admin|online|chat|active)\b/i', $normalized)) {
    mcbot_json_response("Open the Messages interface to see a list of users. While we don't show a 'green dot' for everyone, you can send a message to anyone in the system and they will be notified.", [['label' => 'Messages', 'href' => 'chat_interface.php']]);
}

// 5. REPORTS & EXPORT
if (preg_match('/\b(export|download|print|generate|save|file|copy|get)\b.*\b(excel|pdf|csv|data|report|summary|record)\b/i', $normalized)) {
    mcbot_json_response("Go to the Reports page to generate downloadable files. You can choose between Inventory, Attendance, or Delivery reports and export them for your documentation.", [['label' => 'Reports', 'href' => 'reports.php']]);
}

// 6. GENERAL TROUBLESHOOTING
if (preg_match('/\b(error|not.*working|mali|bug|problem|issue|broken|help|fix|tulong|ayaw|mali|crash)\b/i', $normalized)) {
    mcbot_json_response("I'm sorry you're experiencing an issue! Try refreshing the page first. If the problem persists, please take a screenshot and report it to the system administrator.");
}

// 7. SYSTEM FEATURES & DATA HANDLING
if (preg_match('/\b(what.*can.*you.*do|ano.*pwedeng.*gawin|how.*help)\b/i', $normalized)) {
    mcbot_json_response("As your MCPIL assistant, I can help you check inventory stock, track deliveries, view your attendance records, manage purchase orders, and navigate through all lab modules. Just ask me a specific question!", [['label' => 'Dashboard', 'href' => 'dashboard.php']]);
}

if (preg_match('/\b(data.*secure|safe.*data|privacy)\b/i', $normalized)) {
    mcbot_json_response("Yes, all lab data in the MCPIL system is stored securely. Access to sensitive information like inventory reports and employee records is restricted based on user roles (Admin, Manager, or Employee).");
}

if (preg_match('/\b(how.*search|find.*item|hanapin.*gamot)\b/i', $normalized)) {
    mcbot_json_response("You can find any item or record using the search bars located at the top of the Inventory, Invoice, and Purchase Order pages. Just type the name or ID you're looking for.", [['label' => 'Inventory', 'href' => 'inventory.php']]);
}

if (preg_match('/\b(mobile.*app|phone.*access)\b/i', $normalized)) {
    mcbot_json_response("The MCPIL Laboratory Management System is web-based and mobile-responsive. You can access it through your smartphone's browser to check data or record attendance on the go.");
}

if (preg_match('/\b(backup|restore|lost.*data)\b/i', $normalized)) {
    mcbot_json_response("The system database is regularly backed up by the administrator to ensure no data is lost. If you think there's a discrepancy in the records, please contact your supervisor.");
}

if (preg_match('/\b(supplier.*info|contact.*supplier|vendor)\b/i', $normalized)) {
    mcbot_json_response("You can find supplier contact details within the Purchase Order module. When creating an order, selecting a supplier will show their linked information.", [['label' => 'Purchase Orders', 'href' => 'purchase_order.php']]);
}

// 8. ADDITIONAL SYSTEM WORKFLOWS
if (preg_match('/\b(how.*clock|paano.*mag-time|process.*attendance)\b/i', $normalized)) {
    mcbot_json_response("The attendance process is simple: 1. Go to the Attendance Camera page. 2. Allow camera access. 3. Click 'Clock In' or 'Clock Out'. The system will capture your photo and record the exact time.", [['label' => 'Attendance Camera', 'href' => 'attendance_camera.php']]);
}

if (preg_match('/\b(view.*photo|check.*picture|attendance.*image)\b/i', $normalized)) {
    mcbot_json_response("All verification photos captured during clock-in/out are stored in your Attendance History. You can click on any record to view the captured image.", [['label' => 'Attendance History', 'href' => 'attendance_history.php']]);
}

if (preg_match('/\b(low.*stock.*notice|warning.*inventory|reorder.*point)\b/i', $normalized)) {
    mcbot_json_response("The system highlights items in red on the Inventory page when they reach or fall below their minimum stock level. This serves as a reminder to create a new Purchase Order.", [['label' => 'Inventory', 'href' => 'inventory.php']]);
}

if (preg_match('/\b(received.*order|confirm.*delivery|update.*po.*status)\b/i', $normalized)) {
    mcbot_json_response("When a delivery arrives, go to the Purchase Order page and update the status of the specific PO to 'Received'. This will automatically adjust your inventory stock levels.", [['label' => 'Purchase Orders', 'href' => 'purchase_order.php']]);
}

if (preg_match('/\b(delete.*record|remove.*data|mali.*input)\b/i', $normalized)) {
    mcbot_json_response("To maintain data integrity, deleting core records (like Invoices or POs) is usually restricted to Administrators. If you made a mistake, please ask your manager for a correction.");
}

if (preg_match('/\b(how.*chat|start.*convo|message.*someone)\b/i', $normalized)) {
    mcbot_json_response("To start a conversation, go to the Messages page, select the person you want to message from the list, type your message, and hit enter. It's that easy!", [['label' => 'Messages', 'href' => 'chat_interface.php']]);
}

if (preg_match('/\b(system.*version|latest.*update|new.*features)\b/i', $normalized)) {
    mcbot_json_response("You are using the latest version of the MCPIL Laboratory Management System. We regularly update the system to improve performance and add new tools for lab efficiency.");
}

/* ════════════════════════════════════════════
   GREETINGS & SMALL TALK
   ════════════════════════════════════════════ */

if (preg_match('/\b(hello|hi|hey|kumusta|kamusta)\b/', $normalized)) {
    $can_ask = ($role === 'admin' || $role === 'manager')
        ? 'deliveries, stock, attendance, reports, or how to use the page you are on'
        : 'stock, attendance, reports, or how to use the page you are on';
    mcbot_json_response("Hello, {$name}! How can I assist you today? You can ask me about {$can_ask} — or anything else!", array_slice($links, 0, 4));
}

if (preg_match('/\b(good\s*morning|magandang\s*umaga)\b/', $normalized)) {
    mcbot_json_response("Good morning, {$name}! Ready to manage the lab today? Let me know if you need help with anything.", array_slice($links, 0, 4));
}

if (preg_match('/\b(good\s*afternoon|magandang\s*hapon)\b/', $normalized)) {
    mcbot_json_response("Good afternoon, {$name}! Hope your day is going well. Ask me anything you need help with.", array_slice($links, 0, 4));
}

if (preg_match('/\b(good\s*evening|magandang\s*gabi)\b/', $normalized)) {
    mcbot_json_response("Good evening, {$name}! Wrapping up for the day? Don't forget to check your attendance records before you leave.", array_slice($links, 0, 4));
}

if (preg_match('/\b(how\s*are\s*you|how\s*r\s*u|kamusta\s*ka|musta\s*ka|are\s*you\s*(ok|okay)|how.*day|musta.*araw)\b/', $normalized)) {
    mcbot_json_response("I'm doing great, thank you for asking! I'm ready to help you with anything in the system. What do you need?", array_slice($links, 0, 4));
}

if (preg_match('/\b(life|buhay|how\s*life|kumusta\s*buhay)\b/', $normalized)) {
    mcbot_json_response("Life as a bot is busy but rewarding! I'm here 24/7 to help you manage MCPIL Lab. How about your life at the lab today?", array_slice($links, 0, 4));
}

if (preg_match('/\b(sure|really|talaga|totoo|sure\s*ka\s*ba)\b/', $normalized)) {
    mcbot_json_response("Yes, I'm absolutely sure! I'm here to support you and make your work in the lab as smooth as possible. Is there something specific you're worried about?", array_slice($links, 0, 4));
}

if (preg_match('/\b(what\s*are\s*you\s*doing|anong\s*gawa\s*mo|what.*up|musta)\b/', $normalized)) {
    mcbot_json_response("I'm just here, ready to help you manage the lab! I've been keeping track of the inventory and attendance records. How about you?", array_slice($links, 0, 4));
}

if (preg_match('/\b(ask\s*(me\s*)?random\s*questions?|tanungin\s*mo\s*ako|ask\s*me\s*anything)\b/i', $normalized)) {
    $random_qs = [
        "How is your shift going so far at the MCPIL Lab?",
        "Have you checked the latest inventory levels today?",
        "Is there anything specific in the system you're having trouble with?",
        "Did you remember to clock in with your photo verification earlier?",
        "How can I make your work in the lab easier today?",
        "What's the most used item in your inventory lately?",
        "Have you seen any new messages in the chat system?"
    ];
    $q = $random_qs[array_rand($random_qs)];
    mcbot_json_response("Sure! " . $q, array_slice($links, 0, 4));
}

if (preg_match('/\b(joke|funny|biro|patawa)\b/i', $normalized)) {
    $jokes = [
        "Why did the chemist stay in the lab all night? Because they were in their element!",
        "What do you do with a dead chemist? Barium!",
        "I told a chemistry joke... there was no reaction.",
        "Oxygen and Magnesium went out on a date... O-Mg!"
    ];
    $joke = $jokes[array_rand($jokes)];
    mcbot_json_response($joke . " Hope that brings a smile to your face! Do you need help with anything in the system?", array_slice($links, 0, 4));
}

if (preg_match('/\b(weather|panahon)\b/', $normalized)) {
    mcbot_json_response("I can't check the live weather, but it's always productive here in the MCPIL Lab! Don't forget to record your attendance before you leave.", array_slice($links, 0, 4));
}

if (preg_match('/\b(who\s*is\s*your\s*creator|who\s*made\s*you|sino\s*gumawa\s*sayo)\b/', $normalized)) {
    mcbot_json_response("I was created specifically for the MCPIL Pharmaceutical Laboratory Management System to help employees like you navigate and manage lab data easily.", array_slice($links, 0, 4));
}

if (preg_match('/\b(do\s*you\s*know\s*me|kilala\s*mo\s*(ba\s*)?ako|sino\s*ako|who\s*am\s*i)\b/', $normalized)) {
    $role_name = ($role === 'store') ? 'Store Manager' : 'Team Member';
    mcbot_json_response("Of course! You are {$name}, one of our valued {$role_name}s here at MCPIL Lab. I'm here to assist you with your daily tasks!", array_slice($links, 0, 4));
}

if (preg_match('/\b(what\s*is\s*my\s*role|ano\s*(ang\s*)?role\s*ko)\b/', $normalized)) {
    $role_features = ($role === 'admin' || $role === 'manager') ? 'delivery tracking and reports' : 'inventory and attendance';
    mcbot_json_response("Your current role in the system is {$role}. This gives you access to specific features like {$role_features}.", array_slice($links, 0, 4));
}

/* ════════════════════════════════════════════
   RELATIONSHIP & PERSONAL (Extended)
   ════════════════════════════════════════════ */

if (preg_match('/\b(crush|crush\s*ko|may\s*crush|type\s*ko)\b/', $normalized)) {
    mcbot_json_response("Haha! I might be a smart bot, but I only have 'chemistry' with the laboratory data and inventory. I'll keep your secrets safe though! Is there anything else you need help with?", array_slice($links, 0, 4));
}

if (preg_match('/\b(relationship|dating|boyfriend|girlfriend|bf|gf|ligaw|jowa|asawa|marriage|dating\s*advice|heartbroken|brokenhearted|single|taken|love\s*life)\b/', $normalized)) {
    $responses = [
        "Love is like a chemical reaction — sometimes it's stable, and sometimes it's explosive! As a bot, I don't date, but I'm here to listen if you need a distraction from all that drama.",
        "I'm better at matching purchase orders than matching couples, but I can tell you one thing: being 'single' in the inventory system means we need to reorder soon! How's your day going otherwise?",
        "That sounds like a lot of emotions! While I'm focused on lab management, I know that a happy team is a productive team. Don't let heart matters distract you too much from your work!",
        "In the lab, we follow strict protocols, but in relationships, things are always unpredictable! Just remember to take a break if you're feeling overwhelmed.",
        "Relationship status: 'In a relationship with the Database'. Just kidding! Love is complicated, but inventory is simple. Which one should we talk about?",
        "If only we could filter relationship problems as easily as we filter reports! Stay positive, everything usually works out in the end."
    ];
    $text = $responses[array_rand($responses)];
    mcbot_json_response($text, array_slice($links, 0, 4));
}

if (preg_match('/\b(advice|payo|tulon[og])\b/', $normalized)) {
    if (preg_match('/\b(love|crush|heart|jowa|relationship)\b/', $normalized)) {
        mcbot_json_response("My best advice for the heart? Treat it like a fragile lab sample — handle with care! But for system advice, I recommend keeping your inventory records updated daily.", array_slice($links, 0, 4));
    }
    mcbot_json_response("I'm here to help! If you need system advice, try asking about how to clock in or check stock. For life advice, just remember to stay hydrated and take your breaks!", array_slice($links, 0, 4));
}

if (preg_match('/\b(love\s*you|mahal\s*kita|i\s*love\s*u|lab\s*yu|luv\s*u)\b/', $normalized)) {
    mcbot_json_response("That's so sweet! I 'love' helping you manage the lab too. Let's keep up the great work together!", array_slice($links, 0, 4));
}

if (preg_match('/\b(favorite\s*color|anong\s*paboritong\s*kulay)\b/', $normalized)) {
    mcbot_json_response("My favorite color is Navy Blue, just like the MCPIL theme! It looks professional and clean, don't you think?", array_slice($links, 0, 4));
}

if (preg_match('/\b(hug|yakap)\b/', $normalized)) {
    mcbot_json_response("Sending you a virtual bot hug! (づ｡◕‿‿<)づ Hope your day at the lab gets even better!", array_slice($links, 0, 4));
}

if (preg_match('/\b(haha|lmfao|lol|funny|hehe|hihi)\b/', $normalized)) {
    mcbot_json_response("I'm glad I could bring a smile to your face! Is there anything else you need help with in the lab?", array_slice($links, 0, 4));
}

if (preg_match('/\b(ok|okay|cool|nice|noted|sige|geh|alright|gets)\b/', $normalized)) {
    // If it's just "ok" or "okay"
    if (strlen($normalized) <= 5) {
        mcbot_json_response("Great! Let me know if there's anything else you need help with.", array_slice($links, 0, 4));
    }
}

/* ════════════════════════════════════════════
   CASUAL CONVERSATION (Employee Life)
   ════════════════════════════════════════════ */

if (preg_match('/\b(pagod|tired|exhausted|busy|stress|stressed)\b/', $normalized)) {
    mcbot_json_response("I understand. Lab work can be very demanding! Take a short breath if you can. Is there any system task I can help you with to make things easier?", array_slice($links, 0, 4));
}

if (preg_match('/\b(bored|wala\s*magawa|ano\s*pwedeng\s*gawin)\b/', $normalized)) {
    mcbot_json_response("If you have some free time, you could check the inventory levels or review your attendance history to make sure everything is up to date!", array_slice($links, 0, 4));
}

if (preg_match('/\b(gutom|hungry|kain|lunch|break)\b/', $normalized)) {
    mcbot_json_response("Don't forget to take your breaks! Just make sure to clock out if needed, and I'll be here when you get back.", array_slice($links, 0, 4));
}

if (preg_match('/\b(good|ayos|mabuti|okay\s*naman|fine)\b/', $normalized)) {
    // This catches "I am good" etc.
    if (preg_match('/\b(i\s*am|ako|feeling)\b/', $normalized)) {
        mcbot_json_response("That's good to hear! I'm here if you need help with anything in the system.", array_slice($links, 0, 4));
    }
}

if (strpos($normalized, 'thank') !== false || strpos($normalized, 'salamat') !== false) {
    mcbot_json_response("You are very welcome, {$name}! Just ask if you need anything else.");
}

if (preg_match('/\b(bye|goodbye|paalam|good\s*bye|take\s*care)\b/', $normalized)) {
    mcbot_json_response("Take care, {$name}! Don't forget to log out when you're done.", [
        ['label' => 'Logout', 'href' => 'logout.php']
    ]);
}

/* ════════════════════════════════════════════
   THIS PAGE / HELP
   ════════════════════════════════════════════ */

if (preg_match('/\b(this\s*page|current\s*page|use\s*this\s*page|anong\s*page|page\s*na\s*ito)\b/', $normalized)) {
    $text = $page_hints[$current_page] ?? 'You are on a workflow page inside MCPIL. If you tell me what you want to do here, I can point you to the right action.';
    mcbot_json_response($text, array_slice($links, 0, 4));
}

if (preg_match('/\b(help|tulong|any\s*questions?|what\s*can\s*i\s*ask|commands?|guide|navigation|menu)\b/', $normalized)) {
    $can_ask = ($role === 'admin' || $role === 'manager')
        ? '"check upcoming deliveries", "view inventory", "my attendance", "generate a report", or "how to message someone"'
        : '"check my stock", "how do I clock in", "my attendance history", or "how to generate a report"';
    mcbot_json_response("You can ask me things like: {$can_ask} — or anything at all, I'll do my best to help!", array_slice($links, 0, 4));
}

/* ════════════════════════════════════════════
   INTENT FLAGS
   ════════════════════════════════════════════ */

$asks_delivery   = preg_match('/\b(delivery|deliveries|schedule|scheduled|expected|arrival|arrive|truck|shipment|darating|kelan.*deliver|deliver.*kelan)\b/', $normalized) === 1;
$asks_inventory  = preg_match('/\b(inventory|stock|stocks|item|items|reorder|low\s*stock|out\s*of\s*stock|kulang|wala.*stock)\b/', $normalized) === 1;
$asks_attendance = preg_match('/\b(attendance|time\s*in|time\s*out|clock\s*in|clock\s*out|present|late|absent|pumasok|dumating)\b/', $normalized) === 1;
$asks_reports    = preg_match('/\b(report|reports|export|print|summary|summaries)\b/', $normalized) === 1;
$asks_invoice    = preg_match('/\b(invoice|invoices|billing|bill|resibo)\b/', $normalized) === 1;
$asks_po         = preg_match('/\b(purchase\s*order|purchase\s*orders|p\.?o\.?|order.*supplier|supplier.*order)\b/', $normalized) === 1;
$asks_chat       = preg_match('/\b(chat|message|messages|contact|msg|makausap|makipag.*usap)\b/', $normalized) === 1;
$asks_dashboard  = preg_match('/\b(dashboard|home|overview|main\s*page)\b/', $normalized) === 1;

/* ════════════════════════════════════════════
   DELIVERY
   ════════════════════════════════════════════ */

if ($asks_delivery) {
    $deliveries = mcbot_get_upcoming_deliveries();

    if (!empty($deliveries)) {
        $parts = [];
        foreach ($deliveries as $delivery) {
            $date_label = !empty($delivery['expected_date']) ? format_date($delivery['expected_date']) : format_date($delivery['delivery_date']);
            $supplier   = !empty($delivery['supplier_name']) ? $delivery['supplier_name'] : 'Supplier';
            $parts[]    = ucfirst($delivery['status']) . " delivery from {$supplier} expected on {$date_label}";
        }
        $delivery_links = [];
        if (in_array($role, ['admin','manager'])) {
            $delivery_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
            $delivery_links[] = ['label' => 'Delivery History',  'href' => 'delivery_history.php'];
        }
        mcbot_json_response($intro . 'your store has ' . count($deliveries) . ' upcoming delivery: ' . implode('; ', $parts) . '.', $delivery_links);
    }

    $purchase_orders = mcbot_get_expected_purchase_orders();
    if (!empty($purchase_orders)) {
        $parts = [];
        foreach ($purchase_orders as $po) {
            $date_label = format_date($po['expected_delivery_date']);
            $supplier   = !empty($po['supplier_name']) ? $po['supplier_name'] : 'Supplier';
            $parts[]    = "PO {$po['po_number']} from {$supplier} expected on {$date_label}";
        }
        $po_links = [];
        if (in_array($role, ['admin','manager','store'])) $po_links[] = ['label' => 'Purchase Orders',   'href' => 'purchase_order.php'];
        if (in_array($role, ['admin','manager']))         $po_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
        mcbot_json_response($intro . 'I do not see active delivery records yet, but I found ' . count($purchase_orders) . ' upcoming purchase order: ' . implode('; ', $parts) . '.', $po_links);
    }

    $no_delivery_links = [];
    if (in_array($role, ['admin','manager','store'])) $no_delivery_links[] = ['label' => 'Purchase Orders',   'href' => 'purchase_order.php'];
    if (in_array($role, ['admin','manager']))         $no_delivery_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
    mcbot_json_response($intro . 'I could not find any scheduled deliveries for your store. You can create purchase orders to schedule deliveries, or check Delivery Tracking for all delivery records.', $no_delivery_links);
}

/* ════════════════════════════════════════════
   INVENTORY
   ════════════════════════════════════════════ */

if ($asks_inventory) {
    $summary         = get_inventory_summary();
    $low_stock_items = mcbot_get_low_stock_items();
    $total_items     = (int) ($summary['total_items'] ?? 0);
    $total_quantity  = number_format((float) ($summary['total_quantity'] ?? 0), 0);

    $text = $total_items > 0
        ? $intro . 'inventory currently shows ' . $total_items . ' unique items with a total stock quantity of ' . $total_quantity . ' units.'
        : $intro . 'inventory is currently empty or not configured.';

    if (!empty($low_stock_items)) {
        $parts = [];
        foreach ($low_stock_items as $item) {
            $parts[] = $item['item_name'] . ' (current: ' . number_format((float)$item['total_stock'], 0) . ', minimum: ' . number_format((float)($item['min_stock_level'] ?? 0), 0) . ')';
        }
        $text .= ' The following items are at or below minimum stock level: ' . implode(', ', $parts) . '.';
    } elseif ($total_items > 0) {
        $text .= ' All items are currently above minimum stock levels.';
    }

    $inv_links = [['label' => 'Inventory', 'href' => 'inventory.php']];
    if (in_array($role, ['admin','manager'])) $inv_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
    mcbot_json_response($text, $inv_links);
}

/* ════════════════════════════════════════════
   ATTENDANCE
   ════════════════════════════════════════════ */

if ($asks_attendance) {
    $database = new Database();
    $db       = $database->getConnection();
    $text     = '';

    if ($db && table_exists($db, 'attendance')) {
        $user_id = $_SESSION['user_id'] ?? null;
        if ($user_id && $role === 'employee') {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE()");
            $stmt->execute();
            $today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $text = $today_count > 0
                ? $intro . 'you have already recorded attendance today. You can check your records in Attendance History.'
                : $intro . "you haven't clocked in yet. Please use the Attendance Camera to record your attendance with a photo.";
        } else {
            $text = $intro . 'you can manage attendance via the Attendance Camera (to clock in/out) or view past records in Attendance History.';
        }
    } else {
        $text = $intro . 'attendance tracking is available. Use the Attendance Camera to record your presence.';
    }

    $att_links = [
        ['label' => 'Attendance Camera',  'href' => 'attendance_camera.php'],
        ['label' => 'Attendance History', 'href' => 'attendance_history.php']
    ];
    if ($role === 'employee') $att_links[] = ['label' => 'Dashboard', 'href' => 'dashboard.php'];
    mcbot_json_response($text, $att_links);
}

/* ════════════════════════════════════════════
   REPORTS
   ════════════════════════════════════════════ */

if ($asks_reports) {
    $text = $intro . 'the Reports page allows you to generate and export various system reports including inventory summaries, attendance records, and delivery history. You can filter by date range and export to different formats.';
    $rep_links = [['label' => 'Reports', 'href' => 'reports.php']];
    if (in_array($role, ['admin','manager'])) $rep_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
    mcbot_json_response($text, $rep_links);
}

/* ════════════════════════════════════════════
   INVOICES
   ════════════════════════════════════════════ */

if ($asks_invoice) {
    $database = new Database();
    $db       = $database->getConnection();

    if (in_array($role, ['store','admin','manager'])) {
        if ($db && table_exists($db, 'invoices')) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM invoices");
            $stmt->execute();
            $invoice_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $text = $invoice_count > 0
                ? $intro . 'there are ' . $invoice_count . ' invoice records in the system. Use Invoice List to view and manage them, or Purchase Invoices to encode new supplier invoices.'
                : $intro . 'there are no invoice records yet. You can encode supplier invoices through the Purchase Invoices page when you receive goods.';
        } else {
            $text = $intro . 'invoice management is available through the Invoice List and Purchase Invoices pages.';
        }
    } else {
        $text = $intro . 'invoice processing is handled by store, admin, or manager users. If you need invoice information, please contact them through the chat system.';
    }

    mcbot_json_response($text, [
        ['label' => 'Invoice List',      'href' => 'invoice_list.php'],
        ['label' => 'Purchase Invoices', 'href' => 'purchase_invoice.php']
    ]);
}

/* ════════════════════════════════════════════
   PURCHASE ORDERS
   ════════════════════════════════════════════ */

if ($asks_po) {
    $database = new Database();
    $db       = $database->getConnection();

    if ($db && table_exists($db, 'purchase_orders')) {
        $user_store_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] . ' Store' : '';
        $query = "SELECT COUNT(*) as count FROM purchase_orders";
        if (!empty($user_store_name)) $query .= " WHERE store_name = :store_name";
        $stmt = $db->prepare($query);
        if (!empty($user_store_name)) $stmt->bindValue(':store_name', $user_store_name);
        $stmt->execute();
        $po_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $text = $po_count > 0
            ? $intro . 'your store has ' . $po_count . ' purchase order records. Use the Purchase Orders page to create new POs, review existing ones, and track their status and expected delivery dates.'
            : $intro . 'your store has no purchase orders yet. You can create them from the Purchase Orders page to order items from suppliers.';
    } else {
        $text = $intro . 'purchase order management is available through the Purchase Orders page.';
    }

    $po_links = [['label' => 'Purchase Orders', 'href' => 'purchase_order.php']];
    if (in_array($role, ['admin','manager'])) $po_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
    mcbot_json_response($text, $po_links);
}

/* ════════════════════════════════════════════
   CHAT
   ════════════════════════════════════════════ */

if ($asks_chat) {
    $chat_links = [['label' => 'Messages', 'href' => 'chat_interface.php']];
    if (in_array($role, ['admin','manager'])) $chat_links[] = ['label' => 'Delivery Tracking', 'href' => 'delivery_tracking.php'];
    mcbot_json_response('Use the chat page for direct staff messaging. Open a conversation, send your message, and keep follow-ups in the same thread.', $chat_links);
}

/* ════════════════════════════════════════════
   DASHBOARD
   ════════════════════════════════════════════ */

if ($asks_dashboard) {
    mcbot_json_response(
        'The Dashboard gives you a quick summary of everything happening in the system — stock levels, attendance counts, and recent activity.',
        [['label' => 'Dashboard', 'href' => 'dashboard.php']]
    );
}

/* ════════════════════════════════════════════
   SMART FALLBACK — Claude AI
   Handles anything not matched above.
   Employee can ask ANYTHING and MCbot will
   answer intelligently in context.
   ════════════════════════════════════════════ */

$ai_reply = mcbot_ask_claude($message, $role, $name, $current_page);

if ($ai_reply !== null) {
    mcbot_json_response($ai_reply, array_slice($links, 0, 3));
}

/* ════════════════════════════════════════════
   GENERAL QUESTIONS CATCHER
   ════════════════════════════════════════════ */

if (preg_match('/\b(what|how|why|when|where|can|do|is|are|sin[ou]|ano|bakit|pa[an]o|kailan|nasaan)\b/', $normalized)) {
    // If it hasn't been caught by specific intents above
    $random_responses = [
        "That's an interesting question! While I'm primarily focused on helping you with MCPIL system tasks like inventory and attendance, I'm always happy to chat. Could you tell me more about what you need?",
        "I'm not exactly sure how to answer that specific question, but I can definitely help you navigate the lab system! Should we check your stock or attendance records?",
        "Hmm, that's a good one! My 'brain' is mostly full of lab data, but I'm learning to be more conversational. What else is on your mind?",
        "I might need a bit more context for that! But as your assistant, I'm here to make your work easier. What can I do for you right now?",
        "As a specialized bot for MCPIL Lab, I'm still learning about the world outside of inventory and reports. But I'm always here to listen! What's up?"
    ];
    $text = $random_responses[array_rand($random_responses)];
    mcbot_json_response($text, array_slice($links, 0, 4));
}

/* ════════════════════════════════════════════
   HARD FALLBACK (no API key / API error)
   ════════════════════════════════════════════ */

$subjects = ($role === 'admin' || $role === 'manager')
    ? "deliveries, stock, attendance, or reports"
    : "stock, attendance, reports, or how to navigate any page";

$default_text = ($role === 'admin' || $role === 'manager')
    ? "I'm not exactly sure what you mean by that, but as your MCbot assistant, I can help you with {$subjects}. Try asking something like \"check upcoming deliveries\" or \"view inventory\"."
    : "I'm sorry, I didn't quite catch that. But I'm here to help you! You can ask me to check your {$subjects}, or just chat with me about your day. Try asking \"check my stock\" or \"how do I clock in\".";

if (preg_match('/\b(smile|happy|sad|excited|worried|nervous)\b/', $normalized)) {
    mcbot_json_response("I can't feel emotions like you do, but I'm here to make your day easier by handling the system tasks for you! What's on your mind?", array_slice($links, 0, 4));
}

mcbot_json_response($default_text, array_slice($links, 0, 4));
?>