# php-SVNAccessFile

PHP Class to parse and generate SVN access file.

## Example code

try {
	$file = new SVNAccessFile();
	$file->load("./svn-access-file");
	$group = new Group("new-group", array("user1", "user2"));
	$file->addGroup($group);
	$item = new ACLItem("/", "reposName");
	$itemMember = ACLItemMember::withGroup($group, ACLItemMember::PERM_READWRITE);
	
	$item->addMember($itemMember);
	$file->addACLItem($item);
	$file->save("./svn-access-file.test");
} catch(Exception $exc) {
	echo $exc->getMessage(). "\n";
}
