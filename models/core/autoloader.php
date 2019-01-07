<?php 
/*
 * @Author: Slash Web Design
 */

class Autoloader 
{
    static public function loader($className)
	{
		//echo "separator = " . DIRECTORY_SEPARATOR . "<br />";
		//$className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
		
		$path = array('models' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR, 'models' . DIRECTORY_SEPARATOR, '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR, '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR);
	
		//echo "trying to load [{$className}] class<br />";
		
		foreach ($path as $p)
		{
			$file = str_replace("\\", DIRECTORY_SEPARATOR, $p . $className . ".php");
			$is = file_exists($file);
			$label = $is ? 'true' : 'false';
			
			//echo "&nbsp;&nbsp;&nbsp;&nbsp;trying [ {$file} ] = {$label}<br />";
			
			if ($is)
			{
				require $file;
				$label = class_exists($className) ? 'true' : 'false';
				
				//echo "&nbsp;&nbsp;&nbsp;&nbsp;class_exists = {$label}<br />";
				
	            if (class_exists($className) || interface_exists($className))
				{
					//echo "&nbsp;&nbsp;&nbsp;&nbsp;loaded successfully<br />";
					return true;
				}
			}
			
			if (file_exists(strtolower($file)))
			{
				require strtolower($file);
	            if (class_exists($className) || interface_exists($className))
				{			
					//echo "&nbsp;&nbsp;&nbsp;&nbsp;loaded successfully<br />";
					return true;
				}
			}
		}
		
		echo "Unable to load object [ {$className} ]";
		die();
	}
}

spl_autoload_register('Autoloader::loader');
?>