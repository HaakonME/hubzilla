<?php /** @file */

namespace Zotlabs\Daemon;

class Importfile {

	static public function run($argc,$argv){

		logger('Importfile: ' . print_r($argv,true));

		if($argc < 3)
			return;

		$channel = channelx_by_n($argv[1]);
		if(! $channel)
			return;

		$srcfile = $argv[2];
		$folder  = (($argc > 3) ? $argv[3] : '');
		$dstname = (($argc > 4) ? $argv[4] : '');

		$hash = random_string();

		$arr = [
			'src'               => $srcfile,
			'filename'          => (($dstname) ? $dstname : basename($srcfile)),
			'hash'              => $hash,
			'allow_cid'         => $channel['channel_allow_cid'],
			'allow_gid'         => $channel['channel_allow_gid'],
			'deny_cid'          => $channel['channel_deny_cid'],
			'deny_gid'          => $channel['channel_deny_gid'],
			'preserve_original' => true,
			'replace'           => true
		];

		if($folder)
			$arr['folder'] = $folder;

		attach_store($channel,$channel['channel_hash'],'import',$arr);

		$sync = attach_export_data($channel,$hash);
		if($sync)
			build_sync_packet($channel['channel_id'],array('file' => array($sync)));
	
		return;
	}
}
