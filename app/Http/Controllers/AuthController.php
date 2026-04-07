<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        $data = $request->only(['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash']);

        if (!$this->verifyTelegramAuth($data)) {
            return redirect('/')->with('error', 'Invalid Telegram authentication');
        }

        $user = User::updateOrCreate(
            ['telegram_id' => $data['id']],
            [
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'username' => $data['username'] ?? '',
            ]
        );

        session(['telegram_id' => $data['id'], 'telegram_user' => [
            'id' => $data['id'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'username' => $data['username'] ?? '',
            'photo_url' => $data['photo_url'] ?? '',
        ]]);

        return redirect('/');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['telegram_id', 'telegram_user']);
        return redirect('/');
    }

    private function verifyTelegramAuth(array $data): bool
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        if (empty($botToken) || empty($data['hash'])) {
            return false;
        }

        $checkHash = $data['hash'];
        unset($data['hash']);

        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $dataCheckArr[] = "{$key}={$value}";
            }
        }
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (strcmp($hash, $checkHash) !== 0) {
            return false;
        }

        // Check auth_date is not too old (allow 1 day)
        if (isset($data['auth_date']) && (time() - (int)$data['auth_date']) > 86400) {
            return false;
        }

        return true;
    }
}
