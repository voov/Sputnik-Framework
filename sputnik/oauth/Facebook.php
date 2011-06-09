<?php
/**	
 * Facebook
 * Created by Daniel Fekete.
 * 2011 Copyright VOOV Ltd.
 * User: user
 * Date: 2011.06.07.
 * Time: 15:17
 */


require_once "sputnik/OAuth2.php";
 
class Facebook extends OAuth2 {
	
	public function __construct() {
		$params = array(
			"api_url" => "https://graph.facebook.com",
			//"permission_url" => "https://www.facebook.com/dialog/oauth",
			"permission_url" => "https://graph.facebook.com/oauth/authorize",
			"token_url" => "https://graph.facebook.com/oauth/access_token"
		);
		parent::__construct($params);
	}
}
