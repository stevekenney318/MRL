<?php
use ycd\AdminHelper;
global $YCD_TYPES;
$tutorialsTitles = $YCD_TYPES['tutorialsTitles'];

foreach ($YCD_TYPES['youtubeUrls'] as $videoKey => $url) {
    $title = 'Video Tutorial';

    if (!empty($tutorialsTitles[$videoKey])) {
        $title = $tutorialsTitles[$videoKey];
    }
    $embedUrl = AdminHelper::getYoutubeEmbedUrl($url);
    ?>
    <div class="current-video-section">
        <h3 style=" margin: 40px 0px 20px;"><?php echo esc_attr($title); ?></h3>
        <iframe class="current-iframe" src="<?php echo esc_attr($embedUrl);?>" style="width: 80%; height: 300px;"></iframe>
    </div>
    <?php
}