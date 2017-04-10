<?php
require "./SVNAccessFile.php";

use ezsvnaccfile\SVNAccessFile;
use ezsvnaccfile\Group;
use ezsvnaccfile\ACLItem;
use ezsvnaccfile\ACLItemMember;

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
