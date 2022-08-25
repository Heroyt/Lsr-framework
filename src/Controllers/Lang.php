<?php

namespace App\Controllers;

use Lsr\Core\App;
use Lsr\Core\Controller;
use Lsr\Core\Requests\Request;
use Lsr\Core\Routing\Attributes\Get;

class Lang extends Controller
{

	#[Get('/lang/{lang}')]
	public function setLang(Request $request) : never {
		$_SESSION['lang'] = $request->params['lang'];
		App::redirect($request->get['redirect'] ?? []);
	}

}