<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connect.php';

class AuditLogger {

    public static function log(
        ?int    $userId,
        ?string $username,
        ?string $patientUuid,
        string  $operation,
        ?string $dataCategory,
        string  $status = 'success',
        ?string $keyId = null,
        ?string $details = null
    ): void {
        $pdo = db();

        // Get last chain hash
        $last = $pdo->query('SELECT chain_hash FROM audit_logs ORDER BY id DESC LIMIT 1')->fetchColumn();
        $prev = $last ?: str_repeat('0', 64);

        $patientHash = $patientUuid ? hash('sha256', $patientUuid) : null;
        $ip          = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua          = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $ts          = date('Y-m-d H:i:s');

        // Chain hash: SHA-256(prev_hash | user_id | operation | status | timestamp)
        $chainInput = $prev . ($userId ?? '') . $operation . $status . $ts;
        $chainHash  = hash('sha256', $chainInput);

        $pdo->prepare(
            'INSERT INTO audit_logs
             (user_id,username,patient_id_hash,operation,data_category,status,ip_address,user_agent,key_id,details,chain_hash)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $userId, $username, $patientHash,
            $operation, $dataCategory, $status,
            $ip, $ua, $keyId, $details, $chainHash
        ]);
    }

    /**
     * Verify chain integrity from a given log ID onward.
     * Returns ['valid'=>bool, 'broken_at'=>int|null]
     */
    public static function verifyChain(int $fromId = 1): array {
        $pdo  = db();
        $rows = $pdo->query(
            'SELECT id,user_id,operation,status,chain_hash,created_at FROM audit_logs ORDER BY id ASC'
        )->fetchAll();

        $prev = str_repeat('0', 64);
        foreach ($rows as $row) {
            $expected = hash('sha256', $prev . ($row['user_id'] ?? '') . $row['operation'] . $row['status'] . $row['created_at']);
            if (!hash_equals($expected, $row['chain_hash'])) {
                return ['valid' => false, 'broken_at' => (int)$row['id']];
            }
            $prev = $row['chain_hash'];
        }
        return ['valid' => true, 'broken_at' => null];
    }
}
