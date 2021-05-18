<?php

class ParentController extends ModuleFrontController
{

	public function setRenderTemplate($type = 'front', $template = 'file.tpl', $same_file = false)
	{
		if($same_file)
		{
			$ver = '/';
		}
		else
		{
			$ver = '/1_7/';
		}

		if($this->module->is17)
		{
        	$this->setTemplate('module:ahojplatby/views/templates/'.$type.$ver.$template);
		}
		else
		{
			return $this->setTemplate('views/templates/'.$front.$ver.$template);
		}
	}

	
}
