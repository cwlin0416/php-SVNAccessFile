<?php
namespace ezsvnaccfile;

use \Exception;

class SVNAccessFile {
	public $groups = array();
	public $aclItems = array();

	public function addGroup(Group $group) {
		$this->groups[$group->groupName] = $group;
	}
	public function removeGroup($groupName) {
		unset($this->groups[$groupName]);
	}
	public function getGroup($groupName) {
		if( isset($this->groups[$groupName]) ) {
			return $this->groups[$groupName];
		}
		return null;
	}
	public function addACLItem(ACLItem $item) {
		$path = $item->branch;
		if( !empty($item->repository) ) {
			$path = sprintf("%s:%s", $item->repository, $item->branch);
		}
		if( empty($path) ) {
			throw new Exception("ACL item branch required.");
		}
		if( isset($this->aclItems[$path]) ) {
			throw new Exception("ACL item exist: $path");
		}
		foreach($item->getMembers() as $itemMember) {
			if( $itemMember->type == ACLItemMember::TYPE_GROUP ) {
				if( empty($this->getGroup($itemMember->id)) ) {
					throw new Exception("Group not exist: $itemMember->id");
				}
			}
		}
		$this->aclItems[$path] = $item;
	}
	public function removeACLItem(ACLItem $item) {
		$path = $item->branch;
		if( !empty($item->repository) ) {
			$path = sprintf("%s:%s", $item->repository, $item->branch);
		}
		if( !isset($this->aclItems[$path]) ) {
			throw new Exception("ACL item not exist: $path");
		}
		unset($this->aclItems[$path]);
	}
	public function getACLItem($branch, $repository = null) {
		$path = $branch;
		if( !empty($repository) ) {
			$path = sprintf("%s:%s", $repository, $branch);
		}
		return $this->aclItems[$path];
	}
	public function getGroups() {
		return array_values($this->groups);
	}
	public function getAclItems() {
		return array_values($this->aclItems);
	}
	public function load($filename) {
		$ini_array = @parse_ini_file($filename, true);
		foreach($ini_array as $secName => $configItems) {
			if( $secName == 'groups' ) {
				// Groups
				foreach($configItems as $configName => $configValue) {
					$group = new Group($configName);
					$group->setMembers(explode(',', $configValue));
					$this->addGroup($group);
				}
			} else {
				// ACL
				$pathArr = explode(":", $secName);
				if( count($pathArr) == 2 ) {
					list($repository, $branch) = $pathArr;
					$item = new ACLItem($branch, $repository);
				} else {
					$item = new ACLItem($secName);
				}
				foreach($configItems as $configName => $configValue) {
					$itemMember = new ACLItemMember();
					if( substr($configName, 0, 1) == '@' ) {
						// Specify Group
						$itemMember->id = substr($configName, 1);
						$itemMember->type = ACLItemMember::TYPE_GROUP;
					} else if( $configName == '*' ) {
						$itemMember->id = null;
						$itemMember->type = ACLItemMember::TYPE_ALL;
					} else {
						// Specify User
						$itemMember->id = $configName;
						$itemMember->type = ACLItemMember::TYPE_USER;
					}
					if( $configValue == 'r' ) {
						$itemMember->permission = ACLItemMember::PERM_READONLY;
					} else if( $configValue == 'rw' ) {
						$itemMember->permission = ACLItemMember::PERM_READWRITE;
					}
					$item->addMember($itemMember);
				}
				$this->addACLItem($item);
			}
		}
	}
	private function _saveIniFile($filename, $ini_array) {
		$output = '';
		foreach($ini_array as $secName => $configItems) {
			$output .= sprintf("[%s]\n", $secName);
			foreach($configItems as $configName => $configValue) {
				$output .= sprintf("%s = %s\n", $configName, $configValue);
			}
			$output .= "\n";
		}
		return file_put_contents($filename, $output); 
	}
	public function save($filename) {
		$ini_array = array();
		// Groups
		$groupConfigItems = array();
		foreach($this->getGroups() as $group) {
			$groupConfigItems[$group->groupName] = implode(", ", $group->groupMembers);
		}
		$ini_array['groups'] = $groupConfigItems;

		// ACLs
		foreach($this->getAclItems() as $item) {
			$pathName = $item->branch;
			if( !empty($item->repository) && !empty($item->branch) ) {
				$pathName = sprintf("%s:%s", $item->repository, $item->branch);
			}
			$aclConfigItems = array();
			foreach($item->members as $itemMember) {
				$id = $itemMember->id;
				if( $itemMember->type == ACLItemMember::TYPE_ALL ) {
					$id = '*';
				} else if( $itemMember->type == ACLItemMember::TYPE_GROUP ) {
					$id = '@'. $id;
				}
				$aclConfigItems[$id] = $itemMember->permission;
			}

			$ini_array[$pathName] = $aclConfigItems;
		}
		return $this->_saveIniFile($filename, $ini_array);
	}
}
class Group {
	public $groupName;
	public $groupMembers = array();

	public function __construct($name, $members = null) {
		$this->groupName = $name;
		$this->addMembers($members);
	}

	public function addMember($id) {
		$this->groupMembers[] = trim($id);
		$this->groupMembers = array_unique($this->groupMembers);
		sort($this->groupMembers);
	}
	public function addMembers($ids) {
		if( is_array($ids) ) {
			array_map(array($this, "addMember"), $ids);
		}
	}
	public function setMembers($ids) {
		$this->groupMembers = array_map('trim', $ids);
		$this->groupMembers = array_unique($this->groupMembers);
		sort($this->groupMembers);
	}
	public function removeMember($id) {
		if(($key = array_search($id, $this->groupMembers)) !== false) {
			unset($this->groupMembers[$key]);
		}
	}
}
class ACLItem {
	public $repository = null;
	public $branch = null;
	public $members = array();

	public function __construct($branch, $repository = null) {
		$this->repository = $repository;
		$this->branch = $branch;
	}

	public function addMember(ACLItemMember $itemMember) {
		$this->members[] = $itemMember;
	}
	public function removeMember(ACLItemMember $itemMember) {
		if(($key = array_search($itemMember, $this->members)) !== false) {
			unset($this->members[$key]);
		}
	}
	public function getMembers() {
		return $this->members;
	}
}
class ACLItemMember {
	const TYPE_ALL = 'all';
	const TYPE_USER = 'user';
	const TYPE_GROUP = 'group';
	const PERM_READONLY = 'r';
	const PERM_READWRITE = 'rw';

	public $id = null;
	public $type = null;
	public $permission = null;

	public static function withGroup(Group $group, $perm) {
		$itemMember = new ACLItemMember(ACLItemMember::TYPE_GROUP, $group->groupName, $perm);
		return $itemMember;	
	}
	public static function withId($userId, $perm) {
		$itemMember = new ACLItemMember(ACLItemMember::TYPE_USER, $userId, $perm);
		return $itemMember;	
	} 
	public function __construct($type = null, $id = null, $permission = null) {
		$this->id = $id;
		$this->type = $type;
		$this->permission = $permission;
	}
}

try {
	$file = new SVNAccessFile();
	$file->load("./svn-access-file");
	$group = new Group("new-group", array("cwlin", "loln"));
	$file->addGroup($group);

	$item = new ACLItem("/", "taian_erp2");
	$itemMember = ACLItemMember::withGroup($group, ACLItemMember::PERM_READWRITE);
	
	$item->addMember($itemMember);
	$file->addACLItem($item);
	$file->save("./svn-access-file.test");
} catch(Exception $exc) {
	echo $exc->getMessage(). "\n";
}
for($i=0; $i<10; $i++) {
	$file2 = new SVNAccessFile();
	$file2->load("./svn-access-file.test");
	$file2->save("./svn-access-file.test");
}
