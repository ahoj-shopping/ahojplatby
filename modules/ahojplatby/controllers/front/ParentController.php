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
			if($this->module->is17)
				$ver = '/1_7/';
			else
				$ver = '/1_6/';
		}

		if($this->module->is17)
		{
        	$this->setTemplate('module:ahojplatby/views/templates/'.$type.$ver.$template);
		}
		else
		{
			$this->setTemplate($ver.$template);
		}
	}

	
}
