<?php

class MenuItem {
	private $name;
	private $link;
	private $sub_items = array();
	private $active = false;

	function __construct($name, $link, $active=false) {
		$this->name = $name;
		if(is_array($link)) {
			$link = array("module" => $link);
			$this->link = URI::MakeURL("admin/index", $link, true);
		} else
			$this->link = $link;
		$this->active = $active;
	}

	function SetActive($active=true) {
		$this->active = $active;
	}

	function IsActive() {
		return $this->active;
	}

	function AddMenu($menuItem) {
		$this->sub_items[] = $menuItem;
	}

	function GetMenuItems() {
		return $this->sub_items;
	}

	public function SetName($name) {
		$this->name = $name;
	}

	public function GetName() {
		return $this->name;
	}

	public function SetLink($link) {
		$this->link = $link;
	}

	public function GetLink() {
		return $this->link;
	}
}

class ColumnSorter {
	static function Make($function, $column) {
		if(URI::GetNamedParam("oa") == $column) {
			$direction = array("asc" => "desc", "desc" => "asc");
			$as = URI::GetNamedParam("ob", "asc");
			$src = "/images_admin/icons/icon_sort_$as.gif";
			$link = ModuleHelper::GetFunctionLink($function, array("oa" => $column, "ob" => $direction[$as]));
		} else {
			$src = "/images_admin/icons/icon_sort.gif";
			$link = ModuleHelper::GetFunctionLink($function, array("oa" => $column, "ob" => "asc"));
		}

		//<a href=""><img src="/images_admin/icons/icon_sort.gif" alt="Rendez"/></a>
		return HtmlBuilder::HtmlBuilder("a")->href($link)->html(HtmlBuilder::HtmlBuilder("img")->src($src)->alt(Localization::_("Rendez")));
	}
}

class ModuleHelper {

	static $admin_menu = false;
	static $cur_path = array();
	static $module_enable_list = false;

	static function SetBreadcrumb($path) {
		self::$cur_path = $path;
	}

	static function GetBreadcrumb() {
		return self::$cur_path;
	}

	static function IsEnabled($module_name) {
		
		//print_r(self::$module_enable_list);
		
		if(self::$module_enable_list == false) {
			// cache it in the memory for the run
			$subsite_id = Subsites::GI()->GetSubsiteId();
			$modules_qb = "SELECT modules FROM enabled_modules WHERE enabled_modules.subsites_id='$subsite_id'";
			//$modules_qb = QueryBuilder::SelectFrom("modules", "enabled_modules");
			$modules = Db::GetInstance()->Query($modules_qb);
			self::$module_enable_list = unserialize($modules->modules);
		}
		if(count(self::$module_enable_list) == 0 || !is_array(self::$module_enable_list)) return false;
		return in_array($module_name, (array)self::$module_enable_list);
	}

	static function GetModuleLink($name, $function="index", $named_array=array(), $force_own_params=false) {
		$uri = Sputnik::GetRunningInstance()->uri_helper->current_uri;
		$module_array = array($name);
		if(strpos($function, "/") !== false) {
			$function = explode("/", $function);
			foreach($function as $func) {
				$module_array[] = $func;
			}
		} else {
			$module_array[] = $function;
		}
		$new_named_array = array_merge($named_array, array("module" => $module_array));
		return URI::MakeURL($uri, $new_named_array, $force_own_params);
	}

	static function GetFunctionLink($function, $named_array=array(), $force_own_params=false) {
		return self::GetModuleLink(self::GetActiveModule(), $function, $named_array, $force_own_params);
	}

	static function GetActiveModule() {
		$module_param = URI::GetNamedParam("module");
		if(is_array($module_param)) return $module_param[0];
		return $module_param;
	}

	static function GetActiveFunction() {
		$module_param = URI::GetNamedParam("module");
		if(is_array($module_param)) {
			if(isset($module_param[2])) return $module_param[2];
			else "index"; 
		}
		return $module_param;
	}
	

	static function GetAdminMenu() {
		if(self::$admin_menu == false) self::$admin_menu = new MenuItem("_root", "_root");
		return self::$admin_menu;
	}
}
