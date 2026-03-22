<?php
/**
 * SafeHaven - PhilSMS Service
 * Implementation copied from SilentSignal (same PhilSMS account).
 *
 * Endpoint:  https://dashboard.philsms.com/api/v3/sms/send
 * Auth:      Authorization: Bearer {PHILSMS_TOKEN}
 * Format:    +639XXXXXXXXX  (one request per number)
 */

class PhilSmsService {

    private const API_URL   = 'https://dashboard.philsms.com/api/v3/sms/send';
    private const SENDER_ID = 'PhilSMS';

    /**
     * Send SMS to one or more recipients.
     * Mirrors SilentSignal's sendPhilSms() — one cURL call per number.
     *
     * @param string|array $recipients  Any PH phone format
     * @param string       $message     SMS body
     * @return array ['ok'=>bool, 'sent'=>int, 'failed'=>int, 'errors'=>array]
     */
    public static function send($recipients, string $message): array {
        if (!is_array($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        $recipients = array_filter($recipients); // remove empty

        if (empty($recipients)) {
            error_log('[PhilSMS] No recipients provided.');
            return ['ok' => false, 'sent' => 0, 'failed' => 0, 'errors' => ['No recipients provided.']];
        }

        $token  = defined('PHILSMS_TOKEN') ? PHILSMS_TOKEN : '';
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $phone) {
            $normalized = self::formatNumber(trim($phone));

            if (empty($normalized)) {
                $failed++;
                $errors[] = "Invalid number: {$phone}";
                continue;
            }

            $payload = json_encode([
                'recipient' => $normalized,
                'sender_id' => self::SENDER_ID,
                'type'      => 'plain',
                'message'   => $message,
            ]);

            error_log("[PhilSMS] Sending to: {$normalized}");

            $ch = curl_init(self::API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token,
                ],
                CURLOPT_TIMEOUT => 15,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            error_log("[PhilSMS] HTTP {$httpCode} for {$normalized}: {$response}");

            if ($curlErr) {
                $failed++;
                $errors[] = "cURL error for {$phone}: {$curlErr}";
                error_log("[PhilSMS] cURL error: {$curlErr}");
                continue;
            }

            $result = json_decode($response, true);

            // PhilSMS returns { "status": "success" } on success (same check as SilentSignal)
            if ($httpCode === 200 && (
                (isset($result['status']) && $result['status'] === 'success') ||
                isset($result['data']) ||
                isset($result['message_id'])
            )) {
                $sent++;
            } else {
                $failed++;
                $errMsg   = $result['message'] ?? $response;
                $errors[] = "Failed for {$phone}: {$errMsg}";
                error_log("[PhilSMS] API error for {$phone}: {$errMsg}");
            }
        }

        return [
            'ok'     => $sent > 0,
            'sent'   => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Normalize a Philippine number to +639XXXXXXXXX
     * (Same logic as SilentSignal's UserController::sendPhilSms)
     *
     * 09XXXXXXXXX  → +639XXXXXXXXX
     * 9XXXXXXXXX   → +639XXXXXXXXX
     * 639XXXXXXXXX → +639XXXXXXXXX
     * +639XXXXXXXXX → +639XXXXXXXXX (already correct)
     */
    public static function formatNumber(string $phone): string {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+63' . substr($phone, 1);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            return '+63' . $phone;
        } elseif (preg_match('/^639\d{9}$/', $phone)) {
            return '+' . $phone;
        } elseif (preg_match('/^\+639\d{9}$/', $phone)) {
            return $phone; // already correct
        }

        // Return as-is for unrecognised formats
        return $phone;
    }

    // ── Message builders ─────────────────────────────────────────────────

    public static function buildEvacuationMessage(array $request, array $center): string {
        $code    = $request['confirmation_code'] ?? 'N/A';
        $cName   = $center['name']           ?? 'TBD';
        $cAddr   = $center['address']        ?? '';
        $cPhone  = $center['contact_number'] ?? 'N/A';
        $members = $request['family_members'] ?? 1;

        $msg  = "[SafeHaven] EVACUATION REQUEST\n";
        $msg .= "Code: {$code}\n";
        $msg .= "Status: PENDING approval\n";
        $msg .= "Center: {$cName}\n";
        if ($cAddr) $msg .= "Addr: {$cAddr}\n";
        $msg .= "Tel: {$cPhone}\n";
        $msg .= "Family: {$members} person(s)\n";
        $msg .= "Show code on arrival.";
        return $msg;
    }

    public static function buildApprovalMessage(array $request, array $center): string {
        $code   = $request['confirmation_code'] ?? 'N/A';
        $cName  = $center['name']           ?? 'TBD';
        $cAddr  = $center['address']        ?? '';
        $cPhone = $center['contact_number'] ?? 'N/A';

        $msg  = "[SafeHaven] EVACUATION APPROVED\n";
        $msg .= "Code: {$code}\n";
        $msg .= "Your request is APPROVED.\n";
        $msg .= "Go to: {$cName}\n";
        if ($cAddr) $msg .= "Addr: {$cAddr}\n";
        $msg .= "Tel: {$cPhone}\n";
        $msg .= "Show code on arrival.";
        return $msg;
    }

    public static function buildAlertMessage(array $alert): string {
        $severity = strtoupper($alert['severity'] ?? 'INFO');
        $title    = $alert['title']   ?? '';
        $message  = $alert['message'] ?? '';
        $location = $alert['location'] ?? '';

        $msg  = "[SafeHaven] {$severity} ALERT\n";
        $msg .= "{$title}\n";
        $msg .= $message;
        if ($location) $msg .= "\nArea: {$location}";
        return $msg;
    }

    /**
     * Collect all phone numbers (primary + family) from user rows.
     */
    public static function collectAlertNumbers(array $users): array {
        $numbers = [];
        foreach ($users as $u) {
            $primary = trim($u['phone_number'] ?? '');
            if ($primary) $numbers[] = $primary;

            $familyJson = $u['family_numbers'] ?? '';
            if ($familyJson) {
                $family = json_decode($familyJson, true);
                if (is_array($family)) {
                    foreach ($family as $fn) {
                        $fn = trim($fn);
                        if ($fn) $numbers[] = $fn;
                    }
                }
            }
        }
        // Return raw numbers — formatNumber() is called per-number inside send()
        return array_values(array_unique(array_filter($numbers)));
    }
}
