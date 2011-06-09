<?php

if(!defined("ACL_ALLOW")) {
	define("ACL_ALLOW", 1);
	define("ACL_DENY", 0);
	define("ACL_NOACTION", -1); 

	define("ACL_AROTYPE_USER", 1);
	define("ACL_AROTYPE_GROUP", 2);
}
 
class ACL {

	private $db;
	private $action_cache = array();
	private static $instance=false;
	
	function __construct() {
		$this->db = Db::GetInstance();
	}

	function __destruct() {

	}

	/**
	 * @param  $gid
	 * @param  $where
	 * @param  $what
	 * @return void
	 */
	public function CheckGroupPermission($gid, $where, $what) {

		$action_id = $this->GetAction($where, $what);
		$query = $this->db->Query("SELECT * FROM acl_permissions WHERE member_id='$gid' AND type='" . ACL_AROTYPE_GROUP ."' AND action_id='$action_id'");
		if($query->Length() == 0) return ACL_NOACTION; // not found in the permission table
		return $query->allow; // ACL_ALLOW or ACL_DENY
	}

	/**
	 * This function queries if the given ARO (uid) can access a given AXO to do ACO on it
	 * @param  $where the AXO - Things to control access on / Resource
	 * @param  $what - the ACO - Actions that are requested / Action
	 * @param bool $uid The ARO / The Group or User
	 * @return void
	 */
	public function Query($where, $what, $uid=false) {
		$gid = 0;
		$permission = ACL_DENY;
		if($uid === false) {
			// If no uid was given, check the current user
			$uid = Auth::GetInstance()->GetUserSession()->GetUserId();
			$gid = Auth::GetInstance()->GetUserSession()->GetGroupId();
		} else {
			$gid = $this->db->Query("SELECT group_id FROM users WHERE id='$uid'")->group_id;
		}

		// first check if user has direct connection to a permission
		$action_id = $this->GetAction($where, $what);
		$user_permission = $this->db->Query("SELECT * FROM acl_permissions WHERE member_id='$uid' AND type='" . ACL_AROTYPE_USER . "' AND action_id='$action_id'");
		if($user_permission->Length() > 0) {
			return $user_permission->allow;
		}

		// check all groups in the path
		if($gid > 0) {
			$group_path = array();
			$this->GetGroupPath($gid, &$group_path);
			/*print_r($group_path);*/
			foreach($group_path as $gp_id) {
				$group_permission = $this->CheckGroupPermission($gp_id, $where, $what);
				if($group_permission != ACL_NOACTION) $permission = $group_permission; // if action found, overwrite existing
			}
		}
		return $permission;
	}


	//public function

	/**
	 * @param  $what
	 * @param  $where
	 * @param  $permission
	 * @param bool $uid
	 * @param int $aro_type
	 * @return void
	 */
	public function SetPermission($where, $what, $permission, $uid=false, $aro_type=ACL_AROTYPE_USER) {
		if($uid === false) {
			// If no uid was given, check the current user
			$uid = Auth::GetInstance()->GetUserSession()->GetUserId();
		}
		$row = new DbRow();
		$row->action_id = $this->GetAction($where, $what);
		$row->member_id = $uid;
		$row->type = $aro_type;
		$row->allow = $permission;
		$row->enabled = 1;
		$this->db->Insert("acl_permissions", $row);
	}


	public function ResetMember($uid, $aro_type=ACL_AROTYPE_USER) {
		$row = new DbRow();
		$row->member_id = $uid;
		$row->type = $aro_type;
		$this->db->Delete("acl_permissions", $row);
	}



	/**
	 * Get or set a group, with optionally selected parent
	 * @param  $name
	 * @param string $parent_id
	 * @return 
	 */
	public function GetGroup($name, $parent_id='0') {
		$group = $this->db->Query("SELECT * FROM acl_groups WHERE name='$name' and parent_id='$parent_id'");
		if($group->length() > 0) {
			// we have already have that group in the db
			return $group->id;
		}
		return $this->SaveGroup($name, $parent_id);
	}

	/**
	 * @param  $uid
	 * @param  $name
	 * @return void
	 */
	public function SetUserGroup($uid, $gid) {
		$this->db->Update("users", $uid, array("group_id" => $gid));
	}


	/**
	 *
	 * @param  $where
	 * @param  $what
	 * @return void
	 */
	public function GetAction($where, $what) {
		$where = str_replace("'", "", stripslashes($where));
		$what = str_replace("'", "", stripslashes($what));

		$hash = md5($where . $what);
		if(isset($this->action_cache[$hash])) return $this->action_cache[$hash];
		$resource = $this->db->Query("SELECT * FROM acl_resources WHERE name='$where'");
		if($resource->Length() == 0) {
			$res_id = $this->SaveResource($where);
		} else {
			$res_id = $resource->id;
		}
		$action = $this->db->Query("SELECT * FROM acl_actions WHERE name='$what' AND resource_id='$res_id'");
		if($action->Length() == 0) {
			$this->action_cache[$hash] = $this->SaveAction($res_id, $what);
		} else {
			$this->action_cache[$hash] = $action->id;
		}
		return $this->action_cache[$hash];
	}

	/**
	 * @param  $gid
	 * @param array $path
	 * @return array
	 */
	private function GetGroupPath($gid, &$path=array()) {
		$path[] = $gid;
		$parent_id = $this->db->Query("SELECT parent_id FROM acl_groups WHERE id='$gid'")->parent_id;
		if($parent_id > 0)
			$this->GetGroupPath($parent_id, $path);
		
		return array_reverse($path);
	}

	/**
	 * @param  $name
	 * @return
	 */
	private function SaveResource($name) {
		// TODO: Method needs to be overridden by adapter design
		$row = new DbRow();
		$row->parent_id = 0;
		$row->name = $name;
		return $this->db->Insert("acl_resources", $row);
	}

	/**
	 * @param  $res_id
	 * @param  $name
	 * @return
	 */
	private function SaveAction($res_id, $name) {
		// TODO: Method needs to be overridden by adapter design
		$row = new DbRow();
		$row->resource_id = $res_id;
		$row->name = $name;
		return $this->db->Insert("acl_actions", $row);
	}

	/**
	 * Save a group
	 * @param  $name
	 * @param  $parent_id
	 * @return void
	 */
	public function SaveGroup($name, $parent_id) {
		// TODO: Method needs to be overridden by adapter design
		$row = new DbRow();
		$row->name = $name;
		$row->parent_id = $parent_id;
		return $this->db->Insert("acl_groups", $row);
	}


	/**
	 * @static
	 * @return bool
	 */
	static function GetInstance() {
		if(self::$instance === false) self::$instance = new self;
		return self::$instance;
	}

}
