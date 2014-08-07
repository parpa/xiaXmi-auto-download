// http://www.xiami.com/song/playlist/id/3398036
<?php
$a = 'h2fmF95F373_169ee4E-tFii91E362%k77fa-5%nt%l.1373_33e6fe2165up2ec3%291_Fyecdd4%El%F.o%2685la%2d43%5-l3mxm2F3%6.u3a%b65E%A5i%F3%52mtDc5afE%5%.a26%2E7phafEcc75E';
$len = ceil(strlen($a)/8);
$tmp = [];
for ($j=0,$i=0; $i < strlen($a); $i++,$j++) {
    if (!isset($tmp[$j])) {
        $tmp[$j] = '';
    }
    if ($i == 110) {
        $len -=1;
    }
    if ($j == $len) {
        $j = 0;
        echo "\n";
    }
    $tmp[$j] .= $a{$i};
    echo $a{$i};
}
$output = join('', $tmp);
$output = urldecode($output);
$output = str_replace('^', '0', $output);
echo "\n";
var_dump($output);

