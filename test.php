<?php
$s = '<strong><img src="http://blogdev.kbb1.com//wp-content/gallery/interview/thumbs/thumbs_aleksandr_rappoport_70.jpg" alt="aleksandr_rappoport_70.jpg" title="????????? ?????????" align="right" height="70" hspace="5" width="70" /><img src="http://blogdev.kbb1.com//wp-content/gallery/ml_wpblog_userpics/thumbs/thumbs_smech_ot_dushi_100_wp.gif" alt="smech_ot_dushi_100_wp.gif" title="???? ?? ????" class="userpic" />????????, ???????????? ? ?????? ????????? ?????????</strong>

 Description

This function returns the values of the custom fields with the specified key from the specified post. See also update_post_meta(), delete_post_meta() and add_post_meta().
Usage

[media 1]

 <?php $meta_values = get_post_meta($post_id, $key, $single); ?> 
Examples
Default Usage

[media  2]

<?php $key_1_values = get_post_meta(76, key_1); ?>

Other Example 
[media  2]
<a href="http://www.kabbalah.info/nightkab/uroki/Uroki_Rus/rus_RAV_Video_Interview_Aleksandr_Rapoport_2005_12_01_edited.html" target="_blank" title="??????" class="html-link">????? ??????</a>';

echo sprintf('/\[media\s*%s\]/','\d*');
echo "<hr>";
echo preg_match(sprintf('/\[media\s*%s\]/','\d*'), $s);
echo "<hr>";
//echo preg_replace(sprintf('/\[media\s*%s\]/','\d*'), '<h1>PASHA MEDIA 1</h1>', $s);
echo preg_replace('/\[media\s*1\]/', '<h1>PASHA MEDIA 1</h1>', $s);
?>