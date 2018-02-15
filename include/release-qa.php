<?php /* $Id$ */

/*
What this file does:
	- Generates the download links found at qa.php.net
	- Determines which test results are emailed to news.php.net/php.qa.reports
	- Defines $QA_RELEASES for internal and external (api.php) use, contains all qa related information for future PHP releases

Documentation:
	$QA_RELEASES documentation:
		Configuration:
		- Key is future PHP version number
			- Example: If 5.3.6 is the latest stable release, then use 5.3.7 because 5.3.7-dev is our qa version
			- Typically, this is the only part needing changed
		- active (bool): 
			- It's active and being tested here 
			- Meaning, the version will be reported to the qa.reports list, and be linked at qa.php.net
			- File extensions .tar.gz and .tar.bz2 are assumed to be available
		- release (array):
			- type: RC, alpha, and beta are examples (case should match filename case)
			- version: 0 if no such release exists, otherwise an integer of the rc/alpha/beta number
			- sha256_bz2: sha256 checksum of this downloadable .tar.bz2 file
			- sha256_gz:  sha256 checksum of this downloadable .tar.gz file
			- sha256_xz: sha256 checksum of this downloadble .xz file
			- date: date of release e.g., 21 May 2011
			- baseurl: base url of where these downloads are located
			- Multiple checksums can be available, see the $QA_CHECKSUM_TYPES array below
		Other variables within $QA_RELEASES are later defined including:
			- reported: versions that make it to the qa.reports mailing list
			- release: all current qa releases, including paths to dl urls (w/ sha256 info)
			- dev_version: dev version
			- $QA_RELEASES is made available at qa.php.net/api.php

TODO:
	- Save all reports (on qa server) for all tests, categorize by PHP version (see buildtest-process.php)
	- Consider storing rc downloads at one location, independent of release master
	- Determine best way to handle rc baseurl, currently assumes .tar.gz/tar.bz2 will exist
	- Determine if $QA_RELEASES is compatible with all current, and most future configurations
	- Determine if $QA_RELEASES can be simplified
	- Determine if alpha/beta options are desired
	- Unify then create defaults for most settings
	- Add option to allow current releases (e.g., retrieve current release info via daily cron, cache, check, configure ~ALLOW_CURRENT_RELEASES)
*/

$QA_RELEASES = array(
	'5.6.32' => array(
		'active'		=> true,
		'release'		=> array(
			'type'	    	=> 'RC',
			'number'    	=> 0,
			'sha256_bz2'	=> '',
			'sha256_gz'	=> '',
			'sha256_xz'	=> '',
			'date'      	=> '05 January 2017',
			'baseurl'   	=> 'http://downloads.php.net/tyrael/',
		),
	),

        '7.0.28' => array(
                'active'                => true,
                'release'               => array(
                        'type'      	=> 'RC',
                        'number'    	=> 0,
                        'sha256_bz2'	=> '',
                        'sha256_gz'     => '',
                        'sha256_xz'     => '',
                        'date'      	=> '07 December 2017',
                        'baseurl'   	=> 'http://downloads.php.net/ab/',
                ),
        ),

        '7.1.15' => array(
                'active'                => true,
                'release'		=> array(
                        'type'          => 'RC',
                        'number'        => 1,
                        'sha256_bz2'    => 'db898ed849db819181e9dfdb7b82750a496bf40e64d6657077e3cf464996a568',
                        'sha256_gz'     => '1590014036c9a957ec00f7166f11ad0042133b72fe7c474dbc862dca971275b6',
                        'sha256_xz'     => '9d6198178ed5ef17a4d5657aca39274e5a68756e25928ab3581f5238ab780497',
                        'date'          => '15 February 2018',
                        'baseurl'       => 'http://downloads.php.net/ab/',
                ),
	),

        '7.2.3' => array(
                'active'                => true,
                'release'		=> array(
                        'type'          => 'RC',
                        'number'        => 1,
                        'sha256_bz2'    => '0de8c1c9cb9d4e709a0d09e20634c13735f34db2ba19ec017855ece662bc0b57',
                        'sha256_gz'     => '07445cedd0f1fee49429192f3b924b038f7072bf35fce6121bbeb9229fea61d7',
                        'sha256_xz'     => '8d7e2523db9f718fd95d2682f0c38bbda523ec8809b293d757c79f5e2d759c19',
                        'date'          => '15 February 2018',
                        'baseurl'       => 'https://downloads.php.net/~pollita/',
                ),
        ),
);

// This is a list of the possible checksum values that can be supplied with a QA release. Any 
// new algorithm is read from the $QA_RELEASES array under the 'release' index for each version 
// in the form of "$algorithm_$filetype".
//
// For example, if SHA512 were to be supported, the following indices would have to be added:
//
// 'sha512_bz2' => 'xxx',
// 'sha512_gz'	=> 'xxx',
// 'sha512_xz'	=> 'xxx',

$QA_CHECKSUM_TYPES = [ 'sha256' ];

/*** End Configuration *******************************************************************/

// $QA_RELEASES eventually contains just about everything, also for external use
// release  : These are encouraged for use (e.g., linked at qa.php.net)
// reported : These are allowed to report @ the php.qa.reports mailing list

$qa_releases_process = function () use (&$QA_RELEASES, $QA_CHECKSUM_TYPES) {
	foreach ($QA_RELEASES as $pversion => $info) {

		if (isset($info['active']) && $info['active']) {
	
			// Allow -dev versions of all active types
			// Example: 5.3.6-dev
			$QA_RELEASES['reported'][] = "{$pversion}-dev";
			$QA_RELEASES[$pversion]['dev_version'] = "{$pversion}-dev";
		
			// Allow -dev version of upcoming qa releases (rc/alpha/beta)
			// @todo confirm this php version format for all dev versions
			if ((int)$info['release']['number'] > 0) {
				$QA_RELEASES['reported'][] = "{$pversion}{$info['release']['type']}{$info['release']['number']}";
				if (!empty($info['release']['baseurl'])) {
				
					// php.net filename format for qa releases
					// example: php-5.3.0RC2
					$fn_base = 'php-' . $pversion . $info['release']['type'] . $info['release']['number'];

					$QA_RELEASES[$pversion]['release']['version'] = $pversion . $info['release']['type'] . $info['release']['number'];
					foreach ([ 'bz2', 'gz', 'xz' ] as $file_type) {
						foreach ($QA_CHECKSUM_TYPES as $algo) {
							if (isset($info['release'][$algo . '_' . $file_type])) {
								$QA_RELEASES[$pversion]['release']['files'][$file_type][$algo] = $info['release'][$algo . '_' . $file_type];
							}
						}
						if (!empty($QA_RELEASES[$pversion]['release']['files'][$file_type])) {
							$QA_RELEASES[$pversion]['release']['files'][$file_type]['path']= $info['release']['baseurl'] . $fn_base . '.tar.' . $file_type;
						}
					}

					if (empty($QA_RELEASES[$pversion]['release']['files'])) {
						$QA_RELEASES[$pversion]['release']['enabled'] = false;
					}
				}
			} else {
				$QA_RELEASES[$pversion]['release']['enabled'] = false;
			}

		}
	}

	// Sorted information for later use
	// @todo need these?
	// $QA_RELEASES['releases']   : All current versions with active qa releases
	foreach ($QA_RELEASES as $pversion => $info) {
		if (isset($info['active']) && $info['active'] && !empty($info['release']['number'])) {
			$QA_RELEASES['releases'][$pversion] = $info['release'];
		}
	}

};
$qa_releases_process();

/* Content */
function show_release_qa($QA_RELEASES) {
	// The checksum configuration array
	global $QA_CHECKSUM_TYPES;

	echo "<!-- RELEASE QA -->\n";
	
	if (!empty($QA_RELEASES['releases'])) {
		
		$plural = count($QA_RELEASES['releases']) > 1 ? 's' : '';
		
		// QA Releases
		echo "<span class='lihack'>\n";
		echo "Providing QA for the following <a href='/rc.php'>test release{$plural}</a>:<br> <br>\n";
		echo "</span>\n";
		echo "<table>\n";

		foreach ($QA_RELEASES['releases'] as $pversion => $info) {

			echo "<tr>\n";
			echo "<td colspan=\"" . (sizeof($QA_CHECKSUM_TYPES) + 1) . "\">\n";
			echo "<h3 style=\"margin: 0px;\">{$info['version']}</h3>\n";
			echo "</td>\n";
			echo "</tr>\n";

			foreach ($info['files'] as $file_type => $file_info) {
				echo "<tr>\n";
				echo "<td width=\"20%\"><a href=\"{$file_info['path']}\">php-{$info['version']}.tar.{$file_type}</a></td>\n";

				foreach ($QA_CHECKSUM_TYPES as $algo) {
					echo '<td>';
					echo '<strong>' . strtoupper($algo) . ':</strong> ';

					if (isset($file_info[$algo]) && strlen($file_info[$algo])) {
						echo $file_info[$algo];
					} else {
						echo '(<em><small>No checksum value available</small></em>)&nbsp;';
					}

					echo "</td>\n";
				}

				echo "</tr>\n";
			}
		}

		echo "</table>\n";
	} else {
		echo "<span class='lihack'>There are no QA releases available at the moment to test.</span>";
	}

	echo "<!-- END -->\n";
}
