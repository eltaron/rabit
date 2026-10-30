<?php

/**
 * Smoke test: يطلب مجموعة صفحات كمستخدم محدد ويطبع كود الاستجابة وأي استثناء.
 *   php tests/smoke.php admin@kfs.gov.eg /dashboard /map /admin/roles
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$email = $argv[1] ?? 'admin@kfs.gov.eg';
$urls = array_slice($argv, 2) ?: ['/dashboard'];

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where('email', $email)->firstOrFail();
// اختبار عرض الصفحات فقط: فرض المصادقة الثنائية يُختبر منفصلاً
config(['app.two_factor.required' => false]);

$fail = 0;
foreach ($urls as $url) {
    $app['auth']->guard('web')->setUser($user);
    $request = Illuminate\Http\Request::create($url, 'GET');
    $request->setLaravelSession($session = $app['session']->driver());
    $session->start();
    $session->put('2fa_verified', true); // صفحات الاختبار تفترض جلسة اجتازت المصادقة الثنائية
    try {
        $response = $kernel->handle($request);
        $code = $response->getStatusCode();
        $ex = $response->exception ?? null;
        $line = sprintf("%-4s %s", $code, $url);
        if ($ex) $line .= '  !! ' . get_class($ex) . ': ' . mb_substr($ex->getMessage(), 0, 300) . ' @ ' . basename($ex->getFile()) . ':' . $ex->getLine();
        if ($code >= 500 || $ex) $fail++;
        echo $line, PHP_EOL;
    } catch (Throwable $e) {
        $fail++;
        echo "EXC  $url  " . get_class($e) . ': ' . $e->getMessage(), PHP_EOL;
    }
}
exit($fail ? 1 : 0);
