<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => '인증되지 않은 사용자입니다.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_stats':
        $total = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
        $used = $pdo->query("SELECT COUNT(*) FROM coupons WHERE status = 'USED'")->fetchColumn();
        $pending = $pdo->query("SELECT COUNT(*) FROM coupons WHERE status = 'ISSUED' AND expiration_date >= CURDATE()")->fetchColumn();

        $recent = $pdo->query("SELECT c.*, e.event_name FROM coupons c JOIN events e ON c.event_id = e.id ORDER BY c.issued_at DESC LIMIT 10")->fetchAll();

        echo json_encode(['success' => true, 'stats' => ['total' => $total, 'used' => $used, 'pending' => $pending], 'recent' => $recent]);
        break;

    case 'get_events':
        $events = $pdo->query("SELECT * FROM events ORDER BY created_at DESC")->fetchAll();
        echo json_encode(['success' => true, 'events' => $events]);
        break;

    case 'add_event':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO events (event_name, discount_type, discount_target, description, discount_value, valid_days, msg_template) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['type'], $data['target'], $data['description'], $data['value'], $data['valid_days'], $data['template']]);
        echo json_encode(['success' => true]);
        break;

    case 'update_event':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE events SET event_name = ?, discount_type = ?, discount_target = ?, description = ?, discount_value = ?, valid_days = ?, msg_template = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['type'], $data['target'], $data['description'], $data['value'], $data['valid_days'], $data['template'], $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'get_event':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        echo json_encode(['success' => true, 'event' => $event]);
        break;

    case 'get_coupons_by_event':
        $id = $_GET['id'] ?? 0;
        $q = $_GET['q'] ?? '';
        $sql = "SELECT * FROM coupons WHERE event_id = ?";
        $params = [$id];
        if ($q) {
            $sql .= " AND (customer_name LIKE ? OR phone_number LIKE ? OR coupon_code LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $sql .= " ORDER BY issued_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $coupons = $stmt->fetchAll();
        echo json_encode(['success' => true, 'coupons' => $coupons]);
        break;

    case 'issue_coupon':
        $data = json_decode(file_get_contents('php://input'), true);

        // 1. Generate unique code: 2 random letters + 6 random digits
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';

        $isUnique = false;
        $code = '';
        while (!$isUnique) {
            $code = $letters[rand(0, 25)] . $letters[rand(0, 25)] .
                $digits[rand(0, 9)] . $digits[rand(0, 9)] . $digits[rand(0, 9)] .
                $digits[rand(0, 9)] . $digits[rand(0, 9)] . $digits[rand(0, 9)];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE coupon_code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() == 0)
                $isUnique = true;
        }

        // 2. Fetch event for valid_days and calculate expiry
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$data['event_id']]);
        $event = $stmt->fetch();
        $expiryDate = date('Y-m-d', strtotime("+" . $event['valid_days'] . " days"));

        // 3. Save coupon
        $stmt = $pdo->prepare("INSERT INTO coupons (event_id, coupon_code, customer_name, phone_number, expiration_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['event_id'], $code, $data['customer_name'], $data['phone'], $expiryDate]);

        // 4. Prepare message using template
        $template = $event['msg_template'] ?? '';
        if (!$template) {
            $template = "[텐트깔끄미]\n안녕하세요 고객님!\n[이벤트명] 쿠폰이 발행되었습니다.\n\n■ 혜택: [할인대상] [할인수치][할인유형] 할인\n■ 번호: [쿠폰번호]\n■ 기한: [유효기간]";
        }

        $msg = str_replace(
            ['[이벤트명]', '[할인대상]', '[할인수치]', '[할인유형]', '[유효기간]', '[쿠폰번호]'],
            [$event['event_name'], $event['discount_target'], number_format($event['discount_value']), ($event['discount_type'] === 'PERCENT' ? '%' : '원'), $expiryDate, $code],
            $template
        );

        echo json_encode(['success' => true, 'code' => $code, 'message' => $msg]);
        break;

    case 'verify_coupon':
        $code = $_GET['code'] ?? '';
        $stmt = $pdo->prepare("SELECT c.*, e.event_name, e.description as event_desc FROM coupons c JOIN events e ON c.event_id = e.id WHERE c.coupon_code = ?");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            echo json_encode(['success' => true, 'coupon' => $coupon]);
        } else {
            echo json_encode(['success' => false, 'message' => '존재하지 않는 쿠폰번호입니다.']);
        }
        break;

    case 'use_coupon':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE coupons SET status = 'USED', used_at = NOW() WHERE coupon_code = ? AND status = 'ISSUED'");
        $stmt->execute([$data['code']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '이미 사용되었거나 사용 불가능한 쿠폰입니다.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '잘못된 접근입니다.']);
        break;
}
?>