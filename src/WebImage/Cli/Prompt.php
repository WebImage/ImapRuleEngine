<?php

namespace WebImage\Cli;

class Prompt
{
	const ANSWER_ANY = '*';
	const ANSWER_BLANK = ' ';
	public static function prompt($header_text, $prompt, array $choices=null, $handler=null) 
	{
		if (null == $choices) $choices = array('*' => 'Anything');
		
		while (true)
		{
			if (is_callable($header_text))
			{
				$return = call_user_func($header_text);
				echo $return . PHP_EOL;
			}
			else if (!empty($header_text)) 
				echo $header_text . PHP_EOL;
			
			foreach($choices as $key => $val)
			{
				if ($key === self::ANSWER_ANY || (count($choices) == 1 && $val == self::ANSWER_ANY))
				{
					// Do nothing
					#if (strlen($val) > 0 && $val != self::ANSWER_ANY) echo $val . PHP_EOL;
				}
				else if ($key === self::ANSWER_BLANK)
				{
					$t = empty($val) ? '[ENTER] to continue' : $val;
					echo $t . PHP_EOL;
				}
				else
					echo $key . ') ' . $val . PHP_EOL;
			}
			
			echo $prompt;
			$answer = trim( fgets(STDIN) );
			
			if (
				(isset($choices[self::ANSWER_BLANK]) && strlen($answer) == 0) ||
				isset($choices[$answer]) || 
				(isset($choices[self::ANSWER_ANY]) || (isset($choices[0]) && $choices[0] == self::ANSWER_ANY))
			)
			{
				
				if (null !== $handler && is_callable($handler))
				{
					$returned = call_user_func($handler, $answer, $choices);
					// Only update answer if a value is returned from the callable

					if (null !== $returned) $answer = $returned;
					if (is_array($returned)) {
						$choices = $returned;
						continue;
					}
				}
				
				if (false !== $answer) break; // Allow call_user_func to return false and have loop continue
			}
			
			if ($answer == 'q') die('Quiting' . PHP_EOL);
		}
		return $answer;
	}
	
	public static function promptYesNo($prompt, $allow_empty=false)
	{
		while (true)
		{
			echo $prompt;
			$answer = strtolower(trim(fgets(STDIN)));
			if ($answer == 'y') {
				return true;
			} else if ($answer == 'n') {
				return false;
			} else if (strlen($answer) == 0 && $allow_empty) {
				return null;
			} else {
				echo 'Enter y or n' . PHP_EOL;
			}
		}
	}
}