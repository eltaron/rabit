<?php
use App\Models\Center;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$d = json_decode(file_get_contents('C:/Users/intel/AppData/Local/Temp/opencode/kafr_boundary.json'), true);
$ring = $d[0]['coords'][0];

function inside($lat, $lon, $ring) {
    $in = false;
    $n = count($ring);
    $j = $n - 1;
    for ($i = 0; $i < $n; $i++) {
        $xi = $ring[$i][0]; $yi = $ring[$i][1];
        $xj = $ring[$j][0]; $yj = $ring[$j][1];
        if ((($yi > $lat) != ($yj > $lat)) && ($lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
            $in = !$in;
        }
        $j = $i;
    }
    return $in;
}

$centers = Center::whereNotNull('latitude')->whereNotNull('longitude')->where('code','LIKE','LGOV%')->get();
$outIn = 0; $out = [];
foreach ($centers as $c) {
    $lat = (float) $c->latitude; $lon = (float) $c->longitude;
    if (inside($lat, $lon, $ring)) { $outIn++; }
    else { $out[] = $c->name . " (" . $lat . ", " . $lon . ")"; }
}
echo "Total centers: " . $centers->count() . ", inside: " . $outIn . "\n";
if ($out) echo "OUTSIDE:\n" . implode("\n", $out) . "\n";
