<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @Createdate 2-2-2010 12:55
 */

if( ! defined( 'NV_IS_FILE_ADMIN' ) )
	die( 'Stop!!!' );

$per_page = 5;

$pathimg = nv_check_path_upload( $nv_Request->get_string( 'path', 'get', NV_UPLOADS_DIR ) );
$check_allow_upload_dir = nv_check_allow_upload_dir( $pathimg );

if( isset( $check_allow_upload_dir['view_dir'] ) AND isset( $array_dirname[$pathimg] ) )
{
	if( $nv_Request->isset_request( 'refresh', 'get' ) )
	{
		nv_filesListRefresh( $pathimg );
	}

	$page = $nv_Request->get_int( 'page', 'get', 0 );
	$type = $nv_Request->get_string( 'type', 'get', 'file' );
	$q = nv_string_to_filename( htmlspecialchars( trim( $nv_Request->get_string( 'q', 'get' ) ), ENT_QUOTES ) );

	$selectfile = htmlspecialchars( trim( $nv_Request->get_string( 'imgfile', 'get', '' ) ), ENT_QUOTES );
	$selectfile = basename( $selectfile );

	$base_url = NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&amp;" . NV_OP_VARIABLE . "=" . $op . "&amp;path=" . $pathimg . "&amp;type=" . $type;

	if( empty( $q ) )
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . NV_UPLOAD_GLOBALTABLE . "_file` WHERE `did` = " . $array_dirname[$pathimg];
		if( $type == "image" OR $type == "flash" )
		{
			$sql .= " AND `type`='" . $type . "'";
		}
		if( $nv_Request->isset_request( 'author', 'get' ) )
		{
			$sql .= " AND `userid`=" . $admin_info['userid'];
			$base_url .= "&amp;author";
		}
		$sql .= "  ORDER BY `title` ASC LIMIT " . $page . "," . $per_page;
	}
	else
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.dirname FROM `" . NV_UPLOAD_GLOBALTABLE . "_file` AS t1 INNER JOIN `" . NV_UPLOAD_GLOBALTABLE . "_dir` AS t2 ON t1.`did` = t2.`did`";
		$sql .= " WHERE (t2.`dirname` = '" . $pathimg . "' OR t2.`dirname` LIKE '" . $pathimg . "/%')";
		$sql .= " AND `title` LIKE '%" . $db->dblikeescape( $q ) . "%'";
		$sql .= "  ORDER BY t1.`title` ASC LIMIT " . $page . "," . $per_page;
		$base_url .= "&amp;q=" . $q;
	}
	$result = $db->sql_query( $sql );
	$result_all = $db->sql_query( "SELECT FOUND_ROWS()" );
	list( $all_page ) = $db->sql_fetchrow( $result_all );

	if( $all_page )
	{
		$xtpl = new XTemplate( "listimg.tpl", NV_ROOTDIR . "/themes/" . $global_config['module_theme'] . "/modules/" . $module_file );
		$xtpl->assign( "NV_BASE_SITEURL", NV_BASE_SITEURL );

		while( $file = $db->sql_fetch_assoc( $result ) )
		{
			$file['data'] = $file['size'];
			if( $file['type'] == "image" or $file['ext'] == "swf" )
			{
				$file['size'] = str_replace( "|", " x ", $file['size'] ) . " pixels";
			}
			else
			{
				$file['size'] = nv_convertfromBytes( $file['filesize'] );
			}

			$file['data'] .= "|" . $file['ext'] . "|" . $file['type'] . "|" . nv_convertfromBytes( $file['filesize'] ) . "|" . $file['userid'] . "|" . nv_date( "l, d F Y, H:i:s P", $file['mtime'] ) . "|";
			$file['data'] .= ( empty( $q )) ? '' : $file['dirname'];

			$file['sel'] = ($selectfile == $file['title']) ? " imgsel" : "";
			$file['src'] = NV_BASE_SITEURL . $file['src'] . '?' . $file['mtime'];

			$xtpl->assign( "IMG", $file );
			$xtpl->parse( 'main.loopimg' );
		}
		

		if( ! empty( $selectfile ) )
		{
			$xtpl->assign( "NV_CURRENTTIME", NV_CURRENTTIME );
			$xtpl->parse( 'main.imgsel' );
		}
		if( $all_page > $per_page )
		{
			$generate_page = nv_generate_page( $base_url, $all_page, $per_page, $page, true, true, 'nv_urldecode_ajax', 'imglist' );
			$xtpl->assign( 'GENERATE_PAGE', $generate_page );
			$xtpl->parse( 'main.generate_page' );
		}
		$xtpl->parse( 'main' );
		$contents = $xtpl->text( 'main' );

		include (NV_ROOTDIR . "/includes/header.php");
		echo $contents;
		include (NV_ROOTDIR . "/includes/footer.php");
	}
}

exit( );
?>