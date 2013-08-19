<?php
ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$folder = dirname(__FILE__);
$handle = basename($folder);
$self = basename(__FILE__);
$zipFile = "$folder.zip";
$files = array();
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS)) as $fi) {
	if($fi->isDir()) {
		continue;
	}
	$absname = realpath($fi->getPathname());
	$relname = str_replace(DIRECTORY_SEPARATOR, '/', substr($absname, strlen($folder . DIRECTORY_SEPARATOR)));
	if(preg_match('%(^|/)\.%', $relname)) {
		continue;
	}
	switch(strtolower($relname)) {
		case 'icon.svg':
		case 'readme.md':
		case 'languages/messages.pot':
		case $self:
			$relname = '';
			break;
		default:
			if(preg_match('%languages/\\w+/LC_MESSAGES/messages.po%i', $relname)) {
				$relname = '';
			}
			break;
	}
	if(strlen($relname)) {
		$files[] = array('absolute' => $absname, 'relative' => "$handle/$relname");
	}
}
usort($files, function($a, $b) {
    return strcasecmp($a['absolute'], $b['absolute']);
});
$zip = new ZipArchive();
if(is_file($zipFile)) {
	unlink($zipFile);
}
$zip->open($zipFile, ZipArchive::CREATE);
foreach($files as $file) {
	$zip->addFile($file['absolute'], $file['relative']);
}
$zip->close();
die();