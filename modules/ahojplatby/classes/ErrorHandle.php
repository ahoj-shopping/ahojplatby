<?php


class ErrorHandle
{
	public static function displayError($message)
	{

		$debug =  debug_backtrace();
		
		$caller = $debug[0]['file'].':'.$debug[0]['line'].' - '.(isset($debug[1]) && $debug[1] ? $debug[1]['function'].'()' : '')
		        .PHP_EOL;

		PrestaShopLogger::addLog(
			'Api failed: '.$caller.' Message: '.$message,
			4,
			null,
			'ahojplatby',
			null,
			true
		);
		
	}
}
