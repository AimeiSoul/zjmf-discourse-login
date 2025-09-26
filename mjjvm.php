<?php
namespace oauth\mjjvm;

class mjjvm
{
    public function meta()
    {
        return [
            'name'        => 'MJJBox SSO 登录',
            'description' => '通过 Discourse SSO Provider 快速登录 mjjvm',
            'author'      => 'Aimei',
            'logo_url'    => 'mjjbox.svg',
        ];
    }

    public function config()
    {
        return [
            'Discourse Secret' => [
                'type' => 'text',
                'name' => 'discourse_secret',
                'desc' => 'Discourse Connect Provider 设置的 secret'
            ],
            'Discourse URL' => [
                'type' => 'text',
                'name' => 'discourse_url',
                'desc' => 'Discourse 安装地址，例如 https://mjjbox.com'
            ],
        ];
    }

    /**
     * 生成 SSO 登录 URL
     */
    public function url($params)
    {
        if (empty($params['discourse_secret']) || empty($params['discourse_url'])) {
            throw new \Exception("缺少配置参数");
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // 固定回调地址
        $callback = 'https://www.mjjvm.com/oauth/callback/mjjvm';

        // 生成随机 nonce
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['sso_nonce'] = $nonce;

        // 构造 payload
        $payload = http_build_query([
            'nonce' => $nonce,
            'return_sso_url' => $callback,
        ]);

        // base64 编码
        $sso = base64_encode($payload);

        // 签名
        $sig = hash_hmac('sha256', $sso, $params['discourse_secret']);

        // 拼接登录 URL
        $loginUrl = rtrim($params['discourse_url'], '/') . "/session/sso_provider?sso={$sso}&sig={$sig}";

        return $loginUrl;
    }

    /**
     * 处理 SSO 回调
     */
    public function callback($params)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sso = $_GET['sso'] ?? '';
        $sig = $_GET['sig'] ?? '';

        if (empty($sso) || empty($sig)) {
            throw new \Exception("回调缺少 sso 或 sig 参数，请先登录 MJJBox");
        }

        // 验证签名
        $expectedSig = hash_hmac('sha256', $sso, $params['discourse_secret']);
        if (!hash_equals($expectedSig, $sig)) {
            throw new \Exception("SSO 验证失败：签名不匹配，请先登录 MJJBox");
        }

        // 解码 payload
        $decoded = base64_decode($sso, true);
        if ($decoded === false) {
            throw new \Exception("SSO 解码失败，请先登录 MJJBox");
        }

        parse_str($decoded, $data);

        // 验证 nonce
        if (empty($data['nonce']) || $data['nonce'] !== ($_SESSION['sso_nonce'] ?? '')) {
            throw new \Exception("SSO 验证失败：nonce 不匹配，请先登录 MJJBox");
        }

        // 判断用户是否已登录 Discourse
        if (empty($data['external_id']) && empty($data['username'])) {
            throw new \Exception("请先在 MJJBox 登录后再尝试");
        }

        // 强制使用 Box 提供的邮箱（必须存在）
        $email = $data['email'] ?? '';
        if (empty($email)) {
            throw new \Exception("Box 未返回邮箱，无法绑定账号");
        }

        // 构建用户信息
        $userInfo = [
            'username' => $data['username'] ?? 'Discourse 用户',
            'email'    => $data['email'],
            'avatar'   => $data['avatar_url'] ?? null,
        ];

        // 把 oauth 用户信息保存到 session（供 bind 页面和后续逻辑使用）
        $_SESSION['oauth_user'] = $userInfo;
        $_SESSION['oauth_user_email'] = $email;

        $openid = $data['external_id'] ?? $data['username'];

        return [
            'openid'       => $openid,
            'data'         => $userInfo,
            'callbackBind' => 'bind_email',
            'email' => $email,
        ];
    }
}
