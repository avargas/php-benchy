<?php

class Benchy
{
	private $name;
	private $callback;
	private $ticks = 0;

	private $beginFiles;
	private $beginTicks;
	private $finalFiles;
	private $finalTicks;
	private $finalXHProf;

	public function __construct ($name = null, $callback = null)
	{
		if ($name) {
			$this->setName($name);
		}

		if ($callback) {
			$this->setCallback($callback);
		}

		declare(ticks = 1);
		register_tick_function(array($this, 'ticker'));
	}

	public function setName ($name)
	{
		$this->name = $name;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function setCallback ($callback)
	{
		$this->callback = $callback;
	}

	public function getTicks ()
	{
		return $this->ticks;
	}

	public function ticker ()
	{
		$this->ticks++;
	}

	public function begin ()
	{
		xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
		$this->beginFiles = get_included_files();
		$this->beginTicks = $this->ticks;
	}

	public function end ()
	{
		$ticks = $this->ticks;

		$xhprof = xhprof_disable();
		$xhprof = end($xhprof);

		$xhprof = array(
			'ct'=>$xhprof['ct'],
			'walltime_micro'=>$xhprof['wt'],
			'cputime_micro'=>$xhprof['cpu'],
			'memory_b'=>$xhprof['mu'],
			'peak_memory_b'=>$xhprof['pmu']
		);

		foreach (array('walltime', 'cputime') as $a) {
			$k = $a . '_micro';
			$xhprof[$a . '_milli'] = $xhprof[$k] > 0 ? $xhprof[$k] / 1000 : 0;
			$xhprof[$a . '_sec'] = $xhprof[$k] > 0 ? $xhprof[$k] / 1000000 : 0;
		}

		foreach (array('memory', 'peak_memory') as $a) {
			$k = $a . '_b';
			$xhprof[$a . '_kb'] = $xhprof[$k] > 0 ? $xhprof[$k] / 1024 : 0;
			$xhprof[$a . '_mb'] = $xhprof[$k] > 0 ? $xhprof[$k] / 1024 / 1024 : 0;
		}

		ksort($xhprof);

		$this->finalTicks = $ticks - $this->beginTicks;
		$this->finalFiles = array_diff(get_included_files(), $this->beginFiles);
		$this->finalXHProf = $xhprof;
	}

	public function run ($callback = null)
	{
		if ($callback !== null) {
			$this->setCallback($callback);
		}

		$exception = null;

		$this->begin();

		try {
			call_user_func($this->callback);
		} catch (Exception $e) {
			$exception = $e;
		}

		$this->end();

		if ($exception) {
			throw $exception;
		}

		return $this;
	}

	public function getStats ()
	{
		return $this->finalXHProf + array(
			'included_files'=>$this->finalFiles,
			'ticks'=>$this->ticks
		);
	}

	public static function create ($name = null, $cb = null)
	{
		return new static($name, $cb);
	}
}