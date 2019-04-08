<?php

class embedremoteimages extends phplistPlugin
{
    public $name = 'Embed remote images, sendformat';
    public $coderoot = '';
    public $version = '0.1';
    public $authors = 'Michiel Dethmers';
    public $enabled = 1;
    public $description = 'Allow choosing embed remote images per campaign';
    public $documentationUrl = 'https://resources.phplist.com/plugin/embedremoteimages';
    public $settings = array(
    );

    public function adminmenu()
    {
        return array();
    }

    public function sendFormats()
    {
        return array('embedremoteimages' => s('HTML, with remote images embedded'));
    }

    public function parseFinalMessage($sendformat, $htmlmessage, $textmessage, &$mail, $messageid)
    {
        if ($sendformat != 'embedremoteimages') return 0;

        global $cached;
        $mail->add_html($htmlmessage, $textmessage, $cached[$messageid]['templateid']);

        ## taken from class.phplistmailer.php
        $external_images = array();
        $extensions = implode('|', array_keys($mail->image_types));
        $matched_images = array();
        $pattern = sprintf(
            '~="(https?://(?!%s)([^"]+\.(%s))([\\?/][^"]+)?)"~Ui',
            preg_quote(getConfig('website')),
            $extensions
        );
        preg_match_all($pattern, $mail->Body, $matched_images);

        for ($i = 0; $i < count($matched_images[1]); ++$i) {
            if ($mail->external_image_exists($matched_images[1][$i])) {
                $external_images[] = $matched_images[1][$i].'~^~'.basename($matched_images[2][$i]).'~^~'.strtolower($matched_images[3][$i]);
            }
        }

        if (!empty($external_images)) {
            $external_images = array_unique($external_images);

            for ($i = 0; $i < count($external_images); ++$i) {
                $external_image = explode('~^~', $external_images[$i]);

                if ($image = $mail->get_external_image($external_image[0])) {
                    $content_type = $mail->image_types[$external_image[2]];
                    $cid = $mail->add_html_image($image, $external_image[1], $content_type);

                    if (!empty($cid)) {
                        $mail->Body = str_replace($external_image[0], 'cid:'.$cid, $mail->Body);
                    }
                }
            }
        }
        return 1;
    }

    public function dependencyCheck() {
        global $plugins;
        return array(
            'EMBEDEXTERNALIMAGES should not be enabled' => empty(EMBEDEXTERNALIMAGES),
        );
    }

}
