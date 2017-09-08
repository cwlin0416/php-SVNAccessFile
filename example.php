<?php
require "./SVNAccessFile.php";

use ezsvnaccfile\SVNAccessFile;
use ezsvnaccfile\Group;
use ezsvnaccfile\ACLItem;
use ezsvnaccfile\ACLItemMember;

try {
	$file = new SVNAccessFile();
	$file->load("/home/svn/svn-access-file");

	$group = "group1";
	$members = array(
		"user1", 
		"user2",
		"user3",
	);
	$branch = "/branches/ftslp";
	$repository = "proedu_core";


	$group = $file->getGroup($group);
	if( empty($group) ) {
		$group = new Group($group, $members);
		$file->addGroup($group);
	} else {
		// Append member
		foreach($members as $member) {
			$group->addMember($member);
		}
	}

	$aclItem = $file->getACLItem($branch, $repository);
	if( empty($aclItem) ) {
		$aclItem = new ACLItem($branch, $repository); 
		$aclItemMember = ACLItemMember::withGroup($group, ACLItemMember::PERM_READWRITE);
		$aclItem->addMember($aclItemMember);
		$file->addACLItem($aclItem);
	} else {
		$aclItemMember = ACLItemMember::withGroup($group, ACLItemMember::PERM_READWRITE);
		$aclItem->addMember($aclItemMember);
	}
	$file->save("/home/svn/svn-access-file.new");
} catch(Exception $exc) {
	echo $exc->getMessage(). "\n";
}
