# php-SVNAccessFile

PHP Class to parse and generate SVN access file.

## Example code
```
<?php
require "./SVNAccessFile.php";
use ezsvnaccfile\SVNAccessFile;
use ezsvnaccfile\Group;
use ezsvnaccfile\ACLItem;
use ezsvnaccfile\ACLItemMember;
try {
	$file = new SVNAccessFile();
	$file->load("./svn-access-file");
	$group = new Group("new-group", array("user1", "user2"));
	$file->addGroup($group);
	$item = new ACLItem("/", "repo1");
	$itemMember = ACLItemMember::withGroup($group, ACLItemMember::PERM_READWRITE);
	
	$item->addMember($itemMember);
	$file->addACLItem($item);
	$file->save("./svn-access-file");
} catch(Exception $exc) {
	echo $exc->getMessage(). "\n";
}
```
