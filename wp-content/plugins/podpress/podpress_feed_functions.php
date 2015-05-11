<?php
/*
License:
 ==============================================================================
    Copyright 2006  Dan Kuykendall  (email : dan@kuykendall.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-107  USA
*/
	/*************************************************************/
	/* feed generation functions                                 */
	/*************************************************************/

	function podPress_feedSafeContent($input, $aggressive = false)
	{
		GLOBAL $podPress;
		if ( ('no' == strtolower($podPress->settings['protectFeed']) OR FALSE === $podPress->settings['protectFeed']) AND !$aggressive) {
			return $input;
		}
		$enc = mb_detect_encoding ($input); //BB Dev:  try fixing "htmlentities(): Invalid multibyte sequence in argument" warning
		if($enc == 'ASCII') $enc = 'iso-8859-1'; //BB Dev
		$result = htmlentities($input, ENT_NOQUOTES, $enc ? $enc : "UTF-8", FALSE); //BB Dev: replaced param 'get_bloginfo('charset')'
		if($aggressive) {
			$result = str_replace(array('&amp;', '&lt;', '&gt;', '&'), '', $result);
		}
		return $result;
	}

	function podPress_rss2_ns() {
		echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"'."\n";
		//echo '	xmlns:dtvmedia="http://participatoryculture.org/RSSModules/dtv/1.0"'."\n";
		echo '	xmlns:media="http://search.yahoo.com/mrss/"'."\n";
	}

	function podPress_rss2_head() {
		GLOBAL $podPress, $post, $post_meta_cache, $blog_id;
		if($podPress->settings['enablePremiumContent']) {
			podPress_reloadCurrentUser();
		}
		if(is_array($post_meta_cache[$blog_id])) {
			foreach($post_meta_cache[$blog_id] as $key=>$val) {
				if(isset($post_meta_cache[$blog_id][$key]['enclosure']) && isset($post_meta_cache[$blog_id][$key]['podPressMedia'])) {
					$post_meta_cache[$blog_id][$key]['enclosure_podPressHold'] = $post_meta_cache[$blog_id][$post->ID]['enclosure'];
					unset($post_meta_cache[$blog_id][$key]['enclosure']);
				}
			}
		}

		if(!isset($podPress->settings['category_data'])) {
			podPress_feed_getCategory();
		}
		$data = $podPress->settings['iTunes'];

		$data['podcastFeedURL'] = $podPress->settings['podcastFeedURL'];

		$data['rss_image'] = get_option('rss_image');
		$data['admin_email'] = stripslashes(get_option('admin_email'));

		if($podPress->settings['category_data']['categoryCasting'] == 'true') {
			$data['podcastFeedURL'] = $podPress->settings['category_data']['podcastFeedURL'];

			if($podPress->settings['category_data']['iTunesNewFeedURL'] != '##Global##') {
				$data['new-feed-url'] = $podPress->settings['category_data']['iTunesNewFeedURL'];
			}

			if($podPress->settings['category_data']['iTunesSummaryChoice'] == 'Custom') {
				$data['summary'] = $podPress->settings['category_data']['iTunesSummary'];
			}

			if($podPress->settings['category_data']['iTunesSubtitleChoice'] == 'Custom') {
				$data['subtitle'] = $podPress->settings['category_data']['iTunesSubtitle'];
			}

			if($podPress->settings['category_data']['iTunesKeywordsChoice'] == 'Custom') {
				$data['keywords'] = $podPress->settings['category_data']['iTunesKeywords'];
			}

			if($podPress->settings['category_data']['iTunesAuthorChoice'] == 'Custom' && !empty($podPress->settings['category_data']['iTunesAuthor'])) {
				$data['author'] = $podPress->settings['category_data']['iTunesAuthor'];
			}
			if($podPress->settings['category_data']['iTunesAuthorEmailChoice'] == 'Custom') {
				$data['admin_email'] = $podPress->settings['category_data']['iTunesAuthorEmail'];
			}

			if($podPress->settings['category_data']['iTunesBlock'] != '##Global##' && !empty($podPress->settings['category_data']['iTunesBlock'])) {
				$data['block'] = $podPress->settings['category_data']['iTunesBlock'];
			}
			if($podPress->settings['category_data']['iTunesExplicit'] != '##Global##' && !empty($podPress->settings['category_data']['iTunesExplicit'])) {
				$data['explicit'] = $podPress->settings['category_data']['iTunesExplicit'];
			}
			if($podPress->settings['category_data']['iTunesImageChoice'] == 'Custom') {
				$data['image'] = $podPress->settings['category_data']['iTunesImage'];
			}
			if($podPress->settings['category_data']['rss_imageChoice'] == 'Custom') {
				$data['rss_image'] = $podPress->settings['category_data']['rss_image'];
			}
			if($podPress->settings['category_data']['rss_copyrightChoice'] == 'Custom') {
				$data['rss_copyright'] = $podPress->settings['category_data']['rss_copyright'];
			}
			if($podPress->settings['category_data']['rss_rss_license_urlChoice'] == 'Custom') {
				$data['rss_copyright'] = $podPress->settings['category_data']['rss_license_url'];
			}
		} else {
			$data['rss_copyright'] = $podPress->settings['rss_copyright'];
			if (0 >= strlen(trim($data['author']))) {
				if (0 < strlen($podPress->settings['iTunesAuthor'])) {
					$data['author'] = $podPress->settings['iTunesAuthor'];
				} else {
					$data['author'] = get_option('blogname');
				}
			}
		}
		if (TRUE == empty($podPress->settings['rss_category'])) {
			$rss_category = 'posts';
		} else {
			$rss_category = $podPress->settings['rss_category'];
		}
		
		$data['rss_ttl'] = get_option('rss_ttl');
		if(!empty($data['rss_ttl']) && $data['rss_ttl'] < 1440) {
			$data['rss_ttl'] = 1440;
		}
		echo '	<!-- podcast_generator="podPress/'.PODPRESS_VERSION.'" - maintenance_release="'.PODPRESS_MAINTENANCE_VERSION.'" -->'."\n";
		if (empty($data['rss_copyright'])) {
			echo '		<copyright>'.podPress_feedSafeContent(__('Copyright', 'podpress').' &#xA9; '. date('Y',time())).' '.get_bloginfo('blogname').' '.$podPress->settings['rss_license_url'].'</copyright>'."\n";
		} else {
			echo '		<copyright>'.podPress_feedSafeContent($data['rss_copyright']).' '.$podPress->settings['rss_license_url'].'</copyright>'."\n";
		}
		if($data['new-feed-url'] == 'Enable') {
			if(!empty($data['podcastFeedURL']) && !strpos(strtolower($data['podcastFeedURL']), 'phobos.apple.com') && !strpos(strtolower($data['podcastFeedURL']), 'itpc://')) {
				echo '		<itunes:new-feed-url>'.podPress_feedSafeContent($data['podcastFeedURL']).'</itunes:new-feed-url>'."\n";
			}
		}
		echo '		<managingEditor>'.podPress_feedSafeContent(stripslashes(get_option('admin_email'))).' ('.podPress_feedSafeContent($data['author']).')</managingEditor>'."\n";
		echo '		<webMaster>'.podPress_feedSafeContent(get_option('admin_email')).' ('.podPress_feedSafeContent($data['author']).')</webMaster>'."\n";
		echo '		<category>'.podPress_feedSafeContent($rss_category).'</category>'."\n";
		if(!empty($data['rss_ttl'])) {
			echo '		<ttl>'.$data['rss_ttl'].'</ttl>'."\n";
		}
		echo '		<itunes:keywords>'.podPress_stringLimiter(podPress_feedSafeContent($data['keywords'], true), 255).'</itunes:keywords>'."\n";
		echo '		<itunes:subtitle>'.podPress_stringLimiter(podPress_feedSafeContent($data['subtitle'], true), 255).'</itunes:subtitle>'."\n";
		echo '		<itunes:summary>'.podPress_stringLimiter(podPress_feedSafeContent($data['summary'], true), 4000).'</itunes:summary>'."\n";
		echo '		<itunes:author>'.podPress_feedSafeContent($data['author']).'</itunes:author>'."\n";
		echo '		' .podPress_getiTunesCategoryTags();
		echo '		<itunes:owner>'."\n";
		echo '			<itunes:name>'.stripslashes(podPress_feedSafeContent($data['author'])).'</itunes:name>'."\n";
		echo '			<itunes:email>'.podPress_feedSafeContent($data['admin_email']).'</itunes:email>'."\n";
		echo '		</itunes:owner>'."\n";
		if(empty($data['block'])) {
			$data['block'] = 'No';
		}
		echo '		<itunes:block>'.$data['block'].'</itunes:block>'."\n";
		echo '		<itunes:explicit>'.podPress_feedSafeContent(strtolower($data['explicit'])).'</itunes:explicit>'."\n";
		echo '		<itunes:image href="'.$data['image'].'" />'."\n";
		echo '		<image>'."\n";
		echo '			<url>'.podPress_feedSafeContent($data['rss_image']).'</url>'."\n";
		echo '			<title>'; bloginfo_rss('name'); echo '</title>'."\n";
		echo '			<link>'; bloginfo_rss('url'); echo '</link>'."\n";
		echo '			<width>144</width>'."\n";
		echo '			<height>144</height>'."\n";
		echo '		</image>'."\n";
	}

	function podPress_rss2_item() {
		GLOBAL $podPress, $post, $post_meta_cache, $blog_id;
		$enclosureTag = podPress_getEnclosureTags();
		if($enclosureTag != '') // if no enclosure tag, no need for iTunes tags
		{
			echo "\t" . $enclosureTag;

			if($post->podPressPostSpecific['itunes:subtitle'] == '##PostExcerpt##') {
				ob_start();
				the_content_rss('', false, 0, 25);
				$data = ob_get_contents();
				ob_end_clean();
				$post->podPressPostSpecific['itunes:subtitle'] = substr(ltrim($data), 0, 254);
			}
			if(empty($post->podPressPostSpecific['itunes:subtitle'])) {
				$post->podPressPostSpecific['itunes:subtitle'] = get_the_title_rss();
			}
			echo '		<itunes:subtitle>'.podPress_feedSafeContent($post->podPressPostSpecific['itunes:subtitle'], true).'</itunes:subtitle>'."\n";

			if($post->podPressPostSpecific['itunes:summary'] == '##Global##') {
				$post->podPressPostSpecific['itunes:summary'] = $podPress->settings['iTunes']['summary'];
			}
			if(empty($post->podPressPostSpecific['itunes:summary']) || $post->podPressPostSpecific['itunes:summary'] == '##PostExcerpt##') {
				ob_start();
				the_content_rss('', false, 0, '', 2);
				$data = ob_get_contents();
				ob_end_clean();
				$post->podPressPostSpecific['itunes:summary'] = substr(ltrim($data), 0, 4000);
			}
			if(empty($post->podPressPostSpecific['itunes:summary'])) {
				$post->podPressPostSpecific['itunes:summary'] = $podPress->settings['iTunes']['summary'];
			}
			echo '		<itunes:summary>'.podPress_stringLimiter(podPress_feedSafeContent($post->podPressPostSpecific['itunes:summary'], true), 4000).'</itunes:summary>'."\n";

			if($post->podPressPostSpecific['itunes:keywords'] == '##WordPressCats##') {
				$categories = get_the_category();
				$post->podPressPostSpecific['itunes:keywords'] = '';
				if(is_array($categories)) {
					foreach ($categories as $category) {
						$category->cat_name = $category->cat_name;
						if($post->podPressPostSpecific['itunes:keywords'] != '') {
							$post->podPressPostSpecific['itunes:keywords'] .= ', ';
						}
						$post->podPressPostSpecific['itunes:keywords'] .= $category->cat_name;
					}
					$post->podPressPostSpecific['itunes:keywords'] = trim($post->podPressPostSpecific['itunes:keywords']);
				}
			} elseif($post->podPressPostSpecific['itunes:keywords'] == '##Global##') {
				$post->podPressPostSpecific['itunes:keywords'] = $podPress->settings['iTunes']['keywords'];
			}
			echo '		<itunes:keywords>'.podPress_stringLimiter(podPress_feedSafeContent(str_replace(' ', ',', $post->podPressPostSpecific['itunes:keywords']), true), 255).'</itunes:keywords>'."\n";

			if($post->podPressPostSpecific['itunes:author'] == '##Global##') {
				$post->podPressPostSpecific['itunes:author'] = $podPress->settings['iTunes']['author'];
				if(empty($post->podPressPostSpecific['itunes:author'])) {
					$post->podPressPostSpecific['itunes:author'] = stripslashes(get_option('admin_email'));
				}
			}
			echo '		<itunes:author>'.podPress_feedSafeContent($post->podPressPostSpecific['itunes:author'], true).'</itunes:author>'."\n";

			if($post->podPressPostSpecific['itunes:explicit'] == 'Default') {
				$post->podPressPostSpecific['itunes:explicit'] = $podPress->settings['iTunes']['explicit'];
				if(empty($post->podPressPostSpecific['itunes:explicit'])) {
					$post->podPressPostSpecific['itunes:explicit'] = 'No';
				}
			}
			echo '		<itunes:explicit>'.podPress_feedSafeContent(strtolower($post->podPressPostSpecific['itunes:explicit'])).'</itunes:explicit>'."\n";

			if($post->podPressPostSpecific['itunes:block'] == 'Default') {
				$post->podPressPostSpecific['itunes:block'] = $podPress->settings['iTunes']['block'];
				if(empty($post->podPressPostSpecific['itunes:block'])) {
					$post->podPressPostSpecific['itunes:block'] = 'No';
				}
			}
			if(empty($post->podPressPostSpecific['itunes:block'])) {
				$post->podPressPostSpecific['itunes:block'] = 'No';
			}
			echo '		<itunes:block>'.podPress_feedSafeContent($post->podPressPostSpecific['itunes:block']).'</itunes:block>'."\n";
			//echo '<comments>'. get_comments_link() .'</comments>'."\n";
		}
		$episodeLicenseTags = podPress_getEpisodeLicenseTags();
		if ($episodeLicenseTags != '')
		{
			echo "\t" . $episodeLicenseTags;
		}
		if(isset($post_meta_cache[$blog_id][$post->ID]['enclosure_podPressHold'])) {
			$post_meta_cache[$blog_id][$post->ID]['enclosure'] = $post_meta_cache[$blog_id][$post->ID]['enclosure_podPressHold'];
			unset($post_meta_cache[$blog_id][$post->ID]['enclosure_podPressHold']);
		}
	}


	function podPress_atom_head() {
		GLOBAL $podPress;
		if(!isset($podPress->settings['category_data'])) {
			podPress_feed_getCategory();
		}
		echo '<!-- podcast_generator="podPress/'.PODPRESS_VERSION.'" - maintenance_release="'.PODPRESS_MAINTENANCE_VERSION.'" -->'."\n";
		if ($podPress->settings['category_data']['categoryCasting'] == 'true' && $podPress->settings['category_data']['rss_imageChoice'] == 'Custom') {
			echo "\t".'<logo>'.podPress_feedSafeContent($podPress->settings['category_data']['rss_image']).'</logo>'."\n";
		} else {
			echo "\t".'<logo>'.podPress_feedSafeContent(get_option('rss_image')).'</logo>'."\n";
		}
		if (empty($data['rss_copyright'])) {
			echo '		<rights>'.podPress_feedSafeContent(__('Copyright', 'podpress').' &#xA9; '. date('Y',time())).' '.get_bloginfo('blogname').'</rights>'."\n";
		} else {
			echo '		<rights>'.podPress_feedSafeContent($data['rss_copyright']).'</rights>'."\n";
		}
		if ( !empty($podPress->settings['rss_license_url']) ) {
			echo "\t".'<link rel="license" type="text/html" href="'.$podPress->settings['rss_license_url'].'" />'."\n";
		}
	}

	function podPress_atom_entry() {
		$enclosureTag = podPress_getEnclosureTags('atom');
		if ($enclosureTag != '') // if no enclosure tag, no need for iTunes tags
		{
			echo "\t" . $enclosureTag;
		}
		$episodeLicenseTags = podPress_getEpisodeLicenseTags('atom');
		if ($episodeLicenseTags != '')
		{
			echo "\t" . $episodeLicenseTags;
		}
	}

	function podPress_xspf_playlist() {
		GLOBAL $podPress, $more, $posts, $post, $m;
		header('HTTP/1.0 200 OK');
		header('Content-type: application/xspf+xml; charset=' . get_settings('blog_charset'), true);
		header('Content-Disposition: attachment; filename="playlist.xspf"');
		$more = 1;
		echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'" ?'.">\n";
		echo '<playlist version="1" xmlns="http://xspf.org/ns/0/">'."\n";
		echo "\t".'<title>'. get_bloginfo('blogname') . '</title>'."\n";
		echo "\t".'<annotation><![CDATA['. $podPress->settings['iTunes']['summary'].']]></annotation>'."\n";
		if (empty($podPress->settings['iTunes']['author'])) {
			$creator = get_bloginfo('blogname');
		} else {
			$creator = $podPress->settings['iTunes']['author'];
		}
		echo "\t".'<creator>'. $creator. '</creator>'."\n";
		echo "\t".'<location>'.get_feed_link('playlist.xspf').'</location>'."\n";
		if ( !empty($podPress->settings['rss_license_url']) ) {
			echo "\t".'<license>'.$podPress->settings['rss_license_url'].'</license>'."\n";
		}
		echo "\t".'<trackList>'."\n";
		if (isset($posts)) {
			foreach ($posts as $post) {
				start_wp(); /* This is a call to a very very old function and it seems to be not necessary if $post is global. */
				$enclosureTag = podPress_getEnclosureTags('xspf');
				if ($enclosureTag != '') // if no enclosure tag, no need for track tags
				{
					echo "\t\t".'<track>'."\n";
					echo $enclosureTag;
					echo "\t\t".'</track>'."\n";
				}
			}
		}
		echo "\t".'</trackList>'."\n";
		echo '</playlist>'."\n";
		exit;
	}
	
	function podPress_getEpisodeLicenseTags($feedtype = 'rss2') {
		GLOBAL $podPress, $post, $wpdb;
		$result = '';
		$hasMediaFileAccessible = false;
		if (is_array($post->podPressMedia)) {
			$foundPreferred = false;
			reset($post->podPressMedia);
			while (list($key, $val) = each($post->podPressMedia)) {
				// get the post_meta 
				$querystring = 'SELECT meta_key, meta_value  FROM '.$wpdb->postmeta." WHERE post_id='".$post->ID."' and (meta_key='podcast_episode_license_url' or meta_key='podcast_episode_license_name')";
				$episode_license_infos = $wpdb->get_results($querystring);
				$license = array();
				if ( 0 < count($episode_license_infos) ) {
					foreach ($episode_license_infos as $episode_license_info) {
						$license[$episode_license_info->meta_key] = $episode_license_info->meta_value;
					}
				} 
				if (TRUE == isset($license['podcast_episode_license_url'])) {
					switch ($feedtype) {
						case 'rss2' :
						case 'rss' :
						case 'rdf' : // license tags for entries with the help of the Dublin Core
							if (TRUE == isset($license['podcast_episode_license_url']) AND FALSE == isset($license['podcast_episode_license_name'])) {
								$result = "\t".'<dc:rights>'.$license['podcast_episode_license_url'].'</dc:rights>'."\n";
							} elseif (TRUE == isset($license['podcast_episode_license_name']) AND TRUE == isset($license['podcast_episode_license_name'])) {
								$result = "\t".'<dc:rights>'.$license['podcast_episode_license_name'].' - '.$license['podcast_episode_license_url'].'</dc:rights>'."\n";
							}
						break;
						case 'atom' : // Atom License Extension -  http://tools.ietf.org/html/rfc4946
							if (TRUE == isset($license['podcast_episode_license_url']) AND FALSE == isset($license['podcast_episode_license_name'])) {
								$result = "\t".'<rights>'.$license['podcast_episode_license_url'].'</rights>'."\n";
								$result .= "\t".'<link rel="license" type="text/html" href="'.$license['podcast_episode_license_url'].'" />'."\n";
							} elseif (TRUE == isset($license['podcast_episode_license_name']) AND TRUE == isset($license['podcast_episode_license_name'])) {
								$result = "\t".'<rights>'.$license['podcast_episode_license_name'].'</rights>'."\n";
								$result .= "\t".'<link rel="license" type="text/html" href="'.$license['podcast_episode_license_url'].'" />'."\n";
							}
						break;
						default : // no entry license tags for all other feed types like xspf
							$result = '';
						break;
					}
				}
			}
		}
		return $result;
	}
	
	function podPress_getEnclosureTags($feedtype = 'rss2') {
		GLOBAL $podPress, $post;
		$result = '';
		$hasMediaFileAccessible = false;
		$same_enclosure_URL_in_postmeta_exists = false;
		if(is_array($post->podPressMedia)) {
			$foundPreferred = false;
			reset($post->podPressMedia);
			while (list($key, $val) = each($post->podPressMedia)) {
				$preferredFormat = false;
				if(!$post->podPressMedia[$key]['authorized']) {
					if($podPress->settings['premiumContentFakeEnclosure']) {
						$post->podPressMedia[$key]['URI'] = 'podPress_Protected_Content.mp3';
						} else {
						continue;
					}
				}
				if(defined('PODPRESS_TORRENTCAST') && !empty($post->podPressMedia[$key]['authorized']['URI_torrent'])) {
					$post->podPressMedia[$key]['URI'] = $post->podPressMedia[$key]['URI_torrent'];
				}
				$hasMediaFileAccessible = true;

				if(isset($_GET['onlyformat']) && $_GET['onlyformat'] != $post->podPressMedia[$key]['ext']) {
					continue;
				}

				if(isset($_GET['format']) && $_GET['format'] == $post->podPressMedia[$key]['ext']) {
					$preferredFormat = true;
				}
				if ($post->podPressMedia[$key]['rss'] == 'on' || $post->podPressMedia[$key]['atom'] == 'on' || $preferredFormat == true) {
					if ($feedtype == 'atom' && $post->podPressMedia[$key]['atom'] == 'on') {
						$post->podPressMedia[$key]['URI'] = $podPress->convertPodcastFileNameToWebPath($post->ID, $key, $post->podPressMedia[$key]['URI'], 'feed');
						global $wp_version;
						if (TRUE == version_compare('2.3', $wp_version,'<=')) { // only if it is a newer WP version (when the ATOM feed template of WP is used)
							// check if the URL is stored in postmeta as an enclosure (This is for the case that the wp ATOM template is in use)
							// TRUE: don't put the same enclosure tag into the feed (WP includes the postmeta enclosures since WP 1.5 viw rss_enlosure)
							// FALSE: ok, put the enclosure tag with the data from podPressMedia into the feed
							$same_enclosure_URL_in_postmeta_exists = podPress_meta_data_enclosure_exists($post->ID, $post->podPressMedia[$key]['URI']);
						}
						if ( FALSE === $same_enclosure_URL_in_postmeta_exists ) {
							$result .= '<link rel="enclosure" type="'.$post->podPressMedia[$key]['mimetype'].'" href="'.$post->podPressMedia[$key]['URI'].'" length="'.$post->podPressMedia[$key]['size'].'" />'."\n";
						}
					} elseif ($feedtype == 'xspf') {
						$post->podPressMedia[$key]['URI'] = $podPress->convertPodcastFileNameToValidWebPath($post->podPressMedia[$key]['URI']);
						if (podPress_getFileExt($post->podPressMedia[$key]['URI']) == 'mp3') {
							$result .= "\t"."\t"."\t".'<location>'.$post->podPressMedia[$key]['URI']."</location>\n";
							if (!empty($post->podPressMedia[$key]['title'])) {
								$result .= "\t"."\t"."\t".'<annotation>'.podPress_feedSafeContent($post->podPressMedia[$key]['title'])."</annotation>\n";
								$result .= "\t"."\t"."\t".'<title>'.podPress_feedSafeContent($post->podPressMedia[$key]['title'])."</title>\n";
							} else {
								$result .= "\t"."\t"."\t".'<annotation>'.podPress_feedSafeContent($post->post_title)."</annotation>\n";
								$result .= "\t"."\t"."\t".'<title>'.podPress_feedSafeContent($post->post_title)."</title>\n";
							}
							if ( '##Global##' == $post->podPressPostSpecific['itunes:author']) {
								if (empty($podPress->settings['iTunes']['author'])) {
									$creator = get_bloginfo('blogname');
								} else {
									$creator = $podPress->settings['iTunes']['author'];
								}
								$result .= "\t"."\t"."\t".'<creator>'.$creator.'</creator>'."\n";
							} else {
								$result .= "\t"."\t"."\t".'<creator>'.$post->podPressPostSpecific['itunes:author'].'</creator>'."\n";
							}
							if ( 'UNKNOWN' != $post->podPressMedia[$key]['duration']) {
								$result .= "\t"."\t"."\t".'<duration>'.$post->podPressMedia[$key]['duration'].'</duration>'."\n";
							} 
							if(!empty($post->podPressMedia[$key]['previewImage'])) {
								$result .= "\t"."\t"."\t".'<image>'.$post->podPressMedia[$key]['previewImage']."</image>\n";
							}
						}
					} elseif ($feedtype == 'rss2') {
						$post->podPressMedia[$key]['URI'] = $podPress->convertPodcastFileNameToWebPath($post->ID, $key, $post->podPressMedia[$key]['URI'], 'feed');
						if(!isset($post->podPressMedia[$key]['duration']) || !preg_match("/([0-9]):([0-9])/", $post->podPressMedia[$key]['duration'])) {
							$post->podPressMedia[$key]['duration'] = '00:01:01';
						}
						$durationTag = '<itunes:duration>'.$post->podPressMedia[$key]['duration'].'</itunes:duration>'."\n";
						
						// check if the URL is stored in postmeta as an enclosure
						// TRUE: don't put the same enclosure tag into the feed (WP includes the postmeta enclosures since WP 1.5 viw rss_enlosure)
						// FALSE: ok, put the enclosure tag with the data from podPressMedia into the feed
						$same_enclosure_URL_in_postmeta_exists = podPress_meta_data_enclosure_exists($post->ID, $post->podPressMedia[$key]['URI']);
						
						if($post->podPressMedia[$key]['rss'] == 'on' AND FALSE === $same_enclosure_URL_in_postmeta_exists ) {
							if(!$preferredFormat && $foundPreferred) {
								continue;
							} elseif($preferredFormat) {
								$foundPreferred = true;
							}
							$result = '<enclosure url="'.$post->podPressMedia[$key]['URI'].'" length="'.$post->podPressMedia[$key]['size'].'" type="'.$post->podPressMedia[$key]['mimetype'].'"/>'."\n";
							$result .= $durationTag;
						} elseif($preferredFormat && !$foundPreferred  AND FALSE === $same_enclosure_URL_in_postmeta_exists) {
							$result = '<enclosure url="'.$post->podPressMedia[$key]['URI'].'" length="'.$post->podPressMedia[$key]['size'].'" type="'.$post->podPressMedia[$key]['mimetype'].'"/>'."\n";
							$result .= $durationTag;
							$foundPreferred = true;
						}
					}
				}
			}
		}
		if ($hasMediaFileAccessible && $result == '' && $feedtype != 'xspf' ) {
			if ( FALSE == $same_enclosure_URL_in_postmeta_exists ) {
				echo "<!-- Media File exists for this post, but its not enabled for this feed -->\n";
			} 
		}
		return $result;
	}
	
	function podPress_meta_data_enclosure_exists($post_id, $podPressMedia_enclosure_url='') {
		if (empty($post_id) or empty($podPressMedia_enclosure_url)) {
			return FALSE;
		}
		$exists = FALSE;
		$enclosures = (Array) get_post_meta($post_id, 'enclosure', FALSE);
		foreach ($enclosures as $enclosure) {
			if ( FALSE !== stristr($enclosure, $podPressMedia_enclosure_url) ) {
				$exists = TRUE;
			}		
		}
		return $exists;
	}

	function podPress_getiTunesCategoryTags() {
		GLOBAL $podPress, $post;
		$result = '';
		$data = array();
		if($podPress->settings['category_data']['categoryCasting'] == 'true' && is_array($podPress->settings['category_data']['iTunesCategory'])) {
			foreach ($podPress->settings['category_data']['iTunesCategory'] as $key=>$value) {
				if($value == '##Global##') {
					if(!empty($podPress->settings['iTunes']['category'][$key])) {
						$data[] = $podPress->settings['iTunes']['category'][$key];
					}
				} else {
					$data[] = $value;
				}
			}
		}
		if(empty($data)) {
			$data = $podPress->settings['iTunes']['category'];
		}
		if(is_array($data)) {
			foreach($data as $thiscat) {
				if(strstr($thiscat, ':')) {
					list($cat, $subcat) = explode(":", $thiscat);
					$result .= '<itunes:category text="'.str_replace('&', '&amp;', $cat).'">'."\n";
					$result .= "\t".'<itunes:category text="'.str_replace('&', '&amp;', $subcat).'"/>'."\n";
					$result .= '</itunes:category>'."\n";
				}
				elseif(!empty($thiscat))
				{
					$result .= '<itunes:category text="'.str_replace('&', '&amp;', $thiscat).'"/>'."\n";
				}
			}
		}
		if(empty($result)) {
			$result .= '<itunes:category text="Society &amp; Culture"/>'."\n";
		}
		return $result;
	}

	function podPress_feed_getCategory() {
		GLOBAL $podPress, $wpdb, $wp_query;
		if(!is_category()) {
			$podPress->settings['category_data'] = false;
			return $podPress->settings['category_data'];
		}
		$current_catid = $wp_query->get('cat');
		$category = get_category($current_catid);

		$data = podPress_get_option('podPress_category_'.$category->cat_ID);
		$data['id'] = $category->cat_ID;
		$data['blogname'] = $category->cat_name;
		$data['blogdescription'] = $category->category_description;
		$podPress->settings['category_data'] = $data;
		return $podPress->settings['category_data'];

		// old version of this function
		if(!is_category()) {
			//return false;
		}
		$byName = single_cat_title('', false);

		$categories = get_the_category();
		if(is_array($categories)) {
			foreach ($categories as $category) {
				$thisisit = false;
				if($byName == $category->cat_name) {
					$thisisit = true;
				}

				if($thisisit) {
					$data = podPress_get_option('podPress_category_'.$category->cat_ID);
					$data['id'] = $category->cat_ID;
					$data['blogname'] = $category->cat_name;
					$data['blogdescription'] = $category->category_description;
					$podPress->settings['category_data'] = $data;
					return $podPress->settings['category_data'];
				}
			}
		}
		$podPress->settings['category_data'] = false;
		return $podPress->settings['category_data'];
	}

	function podPress_getCategoryCastingFeedData ($selection, $input) {
		GLOBAL $podPress, $feed;
		if(!isset($podPress->settings['category_data'])) {
			podPress_feed_getCategory();
		}

		if(empty($feed) || $podPress->settings['category_data'] === false) {
			return $input;
		} else {
			if(empty($podPress->settings['category_data']['categoryCasting'])) {
				$podPress->settings['category_data']['categoryCasting'] = 'true';
			}

			switch($selection) {
				case 'blogname':
					switch($podPress->settings['category_data']['blognameChoice']) {
						case 'CategoryName':
							if(empty($podPress->settings['category_data']['blogname'])) {
								return $input;
							} else {
								return $podPress->settings['category_data']['blogname'];
							}
							break;
						case 'Append':
							if(empty($podPress->settings['category_data']['blogname'])) {
								return $input;
							} else {
								return $input.' : '.$podPress->settings['category_data']['blogname'];
							}
							break;
						default:
							return $input;
							break;
					}
					break;
				case 'blogdescription':
					if($podPress->settings['category_data']['blogdescriptionChoice'] == 'CategoryDescription' && !empty($podPress->settings['category_data']['blogdescription'])) {
						return $podPress->settings['category_data']['blogdescription'];
					}
					return $input;
					break;
				case 'rss_language':
					if($podPress->settings['category_data']['rss_language'] == '##Global##' || empty($podPress->settings['category_data']['rss_language'])) {
						return $input;
					} else {
						return $podPress->settings['category_data']['rss_language'];
					}
					break;
					case 'rss_image':
					if($podPress->settings['category_data']['rss_imageChoice'] == 'Global' || empty($podPress->settings['category_data']['rss_image'])) {
						return $input;
					} else {
						return $podPress->settings['category_data']['rss_image'];
					}
					break;
				default:
					return $input;
					break;
			}
		}
	}
	
	function podPress_feedBlogName ($input) {
		return podPress_getCategoryCastingFeedData('blogname', $input);
	}

	function podPress_feedBlogDescription ($input) {
		return podPress_getCategoryCastingFeedData('blogdescription', $input);
	}

	function podPress_feedBlogRssLanguage ($input) {
		return podPress_getCategoryCastingFeedData('rss_language', $input);
	}

	function podPress_feedBlogRssImage ($input) {
		return podPress_getCategoryCastingFeedData('rss_image', $input);
	}
?>
