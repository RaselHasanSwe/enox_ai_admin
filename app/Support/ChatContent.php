<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

class ChatContent
{
    public static function uploadUrl(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://') || str_starts_with($imagePath, 'data:')) {
            return $imagePath;
        }

        return rtrim((string) config('enox.api_url'), '/').'/chat/uploads/'.ltrim($imagePath, '/');
    }

    public static function productImageUrl(mixed $image): ?string
    {
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (! is_string($image) && ! is_numeric($image)) {
            return null;
        }

        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, 'data:')) {
            return $image;
        }

        $cdn = rtrim((string) config('enox.product_cdn', 'https://images.enorsia.com'), '/');
        $file = ltrim($image, '/');
        if (str_contains($file, '/pdrelimg')) {
            return $cdn.'/'.$file;
        }

        return $cdn.'/'.$file.'/pdrelimg';
    }

    public static function senderLabel(?string $sender, ?string $agentName = null): string
    {
        return match (strtolower((string) $sender)) {
            'ai', 'assistant', 'bot' => 'AI',
            'user' => 'User',
            'agent' => $agentName ?: 'Agent',
            'system' => 'System',
            default => $sender ? Str::headline($sender) : 'AI',
        };
    }

    public static function formatTimestamp(?string $iso): string
    {
        if (! $iso) {
            return '';
        }

        $dt = self::parseDate($iso);
        if (! $dt) {
            return $iso;
        }

        $now = Carbon::now($dt->timezone);
        $time = $dt->format('H:i');
        if ($dt->isSameDay($now)) {
            return 'Today, '.$time;
        }
        if ($dt->isSameDay($now->copy()->subDay())) {
            return 'Yesterday, '.$time;
        }
        if ($dt->year === $now->year) {
            return $dt->format('j M, H:i');
        }

        return $dt->format('j M Y, H:i');
    }

    public static function relativeTime(?string $iso): string
    {
        if (! $iso) {
            return '—';
        }

        $dt = self::parseDate($iso);
        if (! $dt) {
            return $iso;
        }

        $seconds = max(0, $dt->diffInSeconds(now()));
        if ($seconds < 8) {
            return 'Just now';
        }
        if ($seconds < 60) {
            return $seconds.'s ago';
        }
        if ($seconds < 3600) {
            $m = intdiv($seconds, 60);
            $s = $seconds % 60;

            return $s ? $m.'m '.$s.'s ago' : $m.'m ago';
        }
        if ($seconds < 86400) {
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);

            return $m ? $h.'h '.$m.'m ago' : $h.'h ago';
        }

        return $dt->format('d M Y H:i');
    }

    public static function summaryText(?string $raw): string
    {
        $parsed = self::parseSummary($raw);
        $user = collect($parsed['messages'])->reverse()->firstWhere('sender', 'user');
        $ai = collect($parsed['messages'])->reverse()->first(fn ($m) => in_array($m['sender'], ['ai', 'bot', 'assistant'], true));
        $text = $user['text'] ?? $ai['text'] ?? '';

        return $text !== '' ? Str::limit($text, 140) : '—';
    }

    public static function parseSummary(?string $raw): array
    {
        $raw = trim((string) $raw);
        $intent = null;
        $tools = [];

        if (preg_match('/Detected intent:\s*([a-z_]+)/i', $raw, $m)) {
            $intent = strtolower($m[1]);
        }
        if (preg_match('/Tools used:\s*(.+)$/im', $raw, $m)) {
            $tools = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
        }

        $messages = [];
        if (preg_match_all(
            '/\[(ai|user|agent|bot|assistant|system)\]\s*(.*?)(?=\s*\[(?:ai|user|agent|bot|assistant|system)\]|\s*Tools used:|\s*$)/is',
            $raw,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $row) {
                $sender = strtolower($row[1]) === 'assistant' ? 'ai' : strtolower($row[1]);
                $text = self::readableLine($row[2]);
                if ($text === '') {
                    continue;
                }
                $last = $messages[array_key_last($messages)] ?? null;
                if ($last && $last['sender'] === $sender && strcasecmp($last['text'], $text) === 0) {
                    continue;
                }
                $messages[] = ['sender' => $sender, 'text' => $text];
            }
        }

        if (! $messages && $raw !== '') {
            $stripped = preg_replace('/Detected intent:.*?(?:Recent messages:)?/is', '', $raw) ?? $raw;
            $stripped = preg_replace('/Tools used:.*$/is', '', $stripped) ?? $stripped;
            $text = self::readableLine($stripped);
            if ($text !== '') {
                $messages[] = ['sender' => 'ai', 'text' => $text];
            }
        }

        return [
            'intent' => $intent,
            'intent_label' => self::intentLabel($intent),
            'tools' => $tools,
            'messages' => $messages,
        ];
    }

    public static function intentLabel(?string $intent): string
    {
        return match ($intent) {
            'product_inquiry' => 'Product inquiry',
            'order_inquiry' => 'Order inquiry',
            'returns' => 'Returns',
            'complaint' => 'Complaint',
            'billing' => 'Billing',
            'general_support' => 'General support',
            default => $intent ? Str::headline($intent) : 'Support',
        };
    }

    public static function inquiryCategoryLabel(?string $category): string
    {
        return match ($category) {
            'order_cancel_request' => 'Order cancellation',
            'live_support_request' => 'Live support',
            'general_support' => 'General support',
            'order_not_placed' => 'Order not placed',
            'lost_or_damaged' => 'Lost or damaged',
            'delivery_failed' => 'Delivery failed',
            'delivery_delay' => 'Delivery delay',
            default => $category ? Str::headline(str_replace('_', ' ', $category)) : 'Inquiry',
        };
    }

    public static function inquiryStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected',
            default => $status ? Str::headline($status) : 'Unknown',
        };
    }

    public static function inquiryStatusBadge(?string $status): string
    {
        return match ($status) {
            'pending' => 'queued',
            'in_progress' => 'info',
            'resolved' => 'resolved',
            'rejected' => 'danger',
            default => 'inactive',
        };
    }

    public static function readableLine(string $text): string
    {
        $text = preg_replace('/data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+\/=\s]+/', '[Image attached]', $text) ?? $text;
        $text = trim($text);

        $best = self::extractBestMessage($text);
        if ($best !== null) {
            $text = $best;
        } else {
            for ($i = 0; $i < 4; $i++) {
                $next = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));
                $decoded = json_decode($next, true);
                if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message']) && $decoded['message'] !== '') {
                    $text = $decoded['message'];
                    continue;
                }
                $text = $next;
                break;
            }
        }

        $text = str_replace(['\\n', '\\t', '\\r', '\\"'], ["\n", ' ', '', '"'], $text);
        $text = preg_replace('/[A-Za-z0-9+\/=]{80,}/', '', $text) ?? $text;
        $text = preg_replace('/```(?:json)?/i', '', $text) ?? $text;
        $text = preg_replace('/^\s*[{}\[\],]+\s*/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text, " \t\n\r\"'`");

        if (in_array($text, ['[JSON payload]', '[binary data omitted]', 'json'], true) || str_starts_with($text, '{')) {
            return '';
        }

        return $text;
    }

    protected static function extractBestMessage(string $text): ?string
    {
        $candidates = [];
        $offset = 0;
        while (preg_match('/"message"\s*:\s*"/', $text, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1] + strlen($m[0][0]);
            $out = self::readJsonStringFrom($text, $start);
            $offset = $m[0][1] + 1;
            if ($out === null || trim($out) === '') {
                continue;
            }
            $trimmed = trim($out);
            if (str_starts_with($trimmed, '```') || str_starts_with($trimmed, '{')) {
                continue;
            }
            $candidates[] = $trimmed;
        }

        return $candidates ? $candidates[array_key_last($candidates)] : null;
    }

    protected static function readJsonStringFrom(string $text, int $start): ?string
    {
        $out = '';
        $len = strlen($text);
        for ($i = $start; $i < $len; $i++) {
            $ch = $text[$i];
            if ($ch === '\\' && $i + 1 < $len) {
                $next = $text[$i + 1];
                $out .= match ($next) {
                    'n' => "\n",
                    't' => ' ',
                    'r' => '',
                    '"' => '"',
                    '\\' => '\\',
                    default => $next,
                };
                $i++;
                continue;
            }
            if ($ch === '"') {
                break;
            }
            $out .= $ch;
        }

        return $out !== '' ? $out : null;
    }

    public static function cleanText(?string $raw): string
    {
        $parsed = self::parseSummary($raw);
        $lines = [];
        foreach ($parsed['messages'] as $message) {
            $lines[] = '['.$message['sender'].'] '.$message['text'];
        }

        return $lines ? implode("\n", $lines) : 'No summary available.';
    }

    public static function parse(array $message): array
    {
        $sender = $message['sender_type'] ?? $message['role'] ?? 'ai';
        $raw = (string) ($message['message'] ?? '');
        $images = [];

        if (! empty($message['image_path'])) {
            $url = self::uploadUrl($message['image_path']);
            if ($url) {
                $images[] = $url;
            }
        }

        $raw = self::extractDataImages($raw, $images);
        $raw = self::extractMarkdownImages($raw, $images);
        $raw = trim($raw);

        $products = [];
        $jsonPretty = null;
        $plain = $raw;

        $envelope = self::tryProductEnvelope($raw);
        if ($envelope) {
            $plain = self::readableLine($envelope['text']);
            $products = $envelope['products'];
        } elseif (str_contains($raw, '"message"') || self::looksLikeJson($raw)) {
            $plain = self::readableLine($raw);
            if ($plain === '') {
                $decoded = json_decode(self::stripJsonFences($raw), true);
                if (is_array($decoded)) {
                    $jsonPretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }
        }

        $plain = self::stripHugeBase64($plain);
        $agentName = $message['agent_name']
            ?? data_get($message, 'metadata.agent_name')
            ?? null;

        return [
            'sender' => $sender,
            'plain' => $plain,
            'html' => $plain !== '' ? self::markdown($plain) : '',
            'images' => array_values(array_unique($images)),
            'products' => $products,
            'json_pretty' => $jsonPretty,
            'timestamp' => self::formatTimestamp($message['timestamp'] ?? null),
            'sender_label' => self::senderLabel($sender, is_string($agentName) ? $agentName : null),
            'tool_calls' => $message['tool_calls'] ?? [],
            'latency_ms' => $message['latency_ms'] ?? null,
        ];
    }

    public static function markdown(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    protected static function extractDataImages(string $raw, array &$images): string
    {
        return preg_replace_callback(
            '/data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+\/=\s]+/',
            function (array $m) use (&$images) {
                $images[] = preg_replace('/\s+/', '', $m[0]);

                return '';
            },
            $raw
        ) ?? $raw;
    }

    protected static function extractMarkdownImages(string $raw, array &$images): string
    {
        return preg_replace_callback(
            '/!\[[^\]]*]\(([^)]+)\)/',
            function (array $m) use (&$images) {
                $src = trim($m[1], " \t\"'");
                if ($src !== '') {
                    $images[] = $src;
                }

                return '';
            },
            $raw
        ) ?? $raw;
    }

    protected static function stripJsonFences(string $text): string
    {
        $text = trim($text);

        return trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));
    }

    protected static function looksLikeJson(string $text): bool
    {
        $stripped = self::stripJsonFences($text);

        return $stripped !== '' && str_starts_with($stripped, '{') && str_ends_with($stripped, '}');
    }

    protected static function parseDate(?string $iso): ?Carbon
    {
        if (! $iso) {
            return null;
        }

        try {
            $tz = (string) config('enox.display_timezone', 'Europe/London');

            return Carbon::parse($iso, 'UTC')->timezone($tz);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function decodeJsonObject(string $raw): ?array
    {
        $stripped = self::stripJsonFences($raw);
        $decoded = json_decode($stripped, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $stripped, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected static function tryProductEnvelope(string $raw): ?array
    {
        $decoded = self::decodeJsonObject($raw);
        if (! is_array($decoded) || (! isset($decoded['message']) && ! isset($decoded['products']) && ! isset($decoded['product_data']))) {
            return null;
        }

        $products = [];
        foreach (['product_data', 'products'] as $key) {
            if (! empty($decoded[$key]) && is_array($decoded[$key])) {
                foreach ($decoded[$key] as $item) {
                    if (! is_array($item) || empty($item['product_name'])) {
                        continue;
                    }
                    $item['product_image'] = self::productImageUrl($item['product_image'] ?? null);
                    $products[] = $item;
                }
            }
        }

        $text = is_string($decoded['message'] ?? null) ? trim($decoded['message']) : '';
        if ($text === '' && $products) {
            $text = 'I found these products: '.collect($products)->pluck('product_name')->filter()->implode(', ');
        }

        return ['text' => $text, 'products' => $products];
    }

    protected static function stripHugeBase64(string $text): string
    {
        $text = preg_replace('/[A-Za-z0-9+\/=]{200,}/', '[binary data omitted]', $text) ?? $text;

        return trim($text);
    }
}
