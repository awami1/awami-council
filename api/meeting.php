<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = getPDO();

// Ensure next_meeting table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `next_meeting` (
  `id` INT NOT NULL DEFAULT 1,
  `date` DATETIME DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL DEFAULT 'الجلسة العمومية للمجلس',
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$method = $_SERVER['REQUEST_METHOD'];

// GET — return current meeting
if ($method === 'GET') {
    $row = $pdo->query("SELECT * FROM next_meeting WHERE id = 1 LIMIT 1")->fetch();
    if (!$row) {
        respond(200, ['nextMeeting' => null]);
    }
    respond(200, [
        'nextMeeting' => [
            'date'    => $row['date'],
            'title'   => $row['title'],
            'visible' => (bool)$row['visible'],
        ]
    ]);
}

// POST — save/update meeting
if ($method === 'POST') {
    $data  = bodyJson();
    $date  = $data['date']    ?? null;
    $title = trim($data['title']  ?? 'الجلسة العمومية للمجلس');
    $vis   = isset($data['visible']) ? (int)(bool)$data['visible'] : 1;

    if (!$date) respond(400, ['error' => 'date is required']);

    $exists = $pdo->query("SELECT id FROM next_meeting WHERE id = 1 LIMIT 1")->fetch();
    if ($exists) {
        $pdo->prepare("UPDATE next_meeting SET `date`=:d, title=:t, visible=:v WHERE id=1")
            ->execute([':d' => $date, ':t' => $title, ':v' => $vis]);
    } else {
        $pdo->prepare("INSERT INTO next_meeting (id,`date`,title,visible) VALUES (1,:d,:t,:v)")
            ->execute([':d' => $date, ':t' => $title, ':v' => $vis]);
    }

    respond(200, ['ok' => true, 'nextMeeting' => ['date' => $date, 'title' => $title, 'visible' => (bool)$vis]]);
}

// DELETE — remove meeting
if ($method === 'DELETE') {
    $pdo->exec("DELETE FROM next_meeting WHERE id = 1");
    respond(200, ['ok' => true]);
}

respond(405, ['error' => 'Method not allowed']);
