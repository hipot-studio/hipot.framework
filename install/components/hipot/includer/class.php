<?php
namespace Hipot\Components;

class Includer extends \CBitrixComponent
{
	public function executeComponent()
	{
		$this->includeComponentTemplate();
	}
}
