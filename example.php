<?

# What to benchmark idea from http://www.phpbench.com/

set_time_limit(-1);
error_reporting(-1);
ini_set('memory_limit', '256M');

set_error_handler(function ($no, $str, $file, $line) {
	throw new ErrorException($str, 0, $no, $file, $line);
});

require_once __DIR__ . '/Benchy.php';


function print_table ($rows)
{
	$final = array();
	$fields = null;
	foreach ($rows as $i=>$row) {
		$fields = array_keys($row);
		break;
	}

	foreach ($fields as $i=>$name) {
		$final[$i][] = $name;

		foreach ($rows as $row) {
			$final[$i][] = $row[$name];
		}
	}

	# calculate the max length per square printed
	$lengths = array();
	for ($i = 0; $i <= count($rows); $i++) {
		$max = 0;
		foreach ($final as $o) {
			$max = max($max, strlen($o[$i]));
		}

		$lengths[$i] = $max;
	}

	$padding = 2;

	$line = function ($length) use ($padding) {
		return '+' . str_repeat('-', $length + $padding);
	};

	# create the line separator
	$separator = '';
	foreach ($lengths as $l) {
		$separator .= $line($l);
	}
	$separator .= "+\n";

	foreach ($fields as $i=>$name) {
		echo $separator;
		
		foreach ($final[$i] as $o=>$val) {
			$length = $lengths[$o];

			$val = ' ' . $val . ' ';

			if ($o === 0) {
				echo '|' . str_pad($val, $length + $padding, ' ', STR_PAD_LEFT) . '|';
			} else {
				echo str_pad($val, $length + $padding) . '|';
			}
		}

		echo "\n";
	}

	echo $separator;
}


echo "setting up benchmark data\n";

$str = 'hello world';
$array = array_fill(0, 10000, $str);

# run the benchmarks
$all = array();

echo "running first \n";
$all[] = Benchy::create('array for loop 1', function () use ($array) {
	$size = count($array);
	for ($index = 0; $index < $size; $index++) {
		$val = $array[$index];
	}
})->run();



echo "running second \n";
$all[] = Benchy::create('array for loop 1', function () use ($array) {
	foreach ($array as $index=>$val) {
		
	}
})->run();



echo "running third \n";
$all[] = Benchy::create('array for loop 1', function () use ($array) {
	foreach ($array as $index=>&$val) {
		
	}
})->run();



# compare

if (php_sapi_name() != 'cli') {
	echo '<pre>';
}

$results = array();
foreach ($all as $benchmark) {
	$stats = $benchmark->getStats();
	$stats['included_files'] = count($stats['included_files']);
	$stats = array('name'=>$benchmark->getName()) + $stats;

	foreach ($stats as $o=>$v) {
		$stats[$o] = is_int($v) ? number_format($v) : (is_float($v) ? number_format($v, 2) : $v);
	}

	$results[] = $stats;
}

print_table($results);