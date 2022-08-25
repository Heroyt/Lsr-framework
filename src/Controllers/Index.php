<?php

namespace App\Controllers;

use Lsr\Core\Controller;
use Lsr\Core\Routing\Attributes\Get;

class Index extends Controller
{

	#[Get('/', 'index')]
	public function index() : void {
		$this->view('pages/index/index');
	}

}